import sublime
import sublime_plugin
import sys
import os
from os.path import join, exists
import re
import threading
import time

import threadq
import preferences
import phpdaemon

st_version = 2
if sublime.version() == '' or int(sublime.version()) > 3000:
    st_version = 3



##############################################################################################################################
# debug
##############################################################################################################################

def debugMsg(prefs, msg):
    if prefs.debug_enabled == True:
      print("[PHPCodeIntel] " + str(msg))

def warnMsg(msg):
    print("[PHPCodeIntel] WARN: " + str(msg))



##############################################################################################################################
# base plugin
##############################################################################################################################

class PhpCodeIntelBase:

    def rescanFile(self, prefs, view, src_file):
        scan_dirs = prefs.getProjectScanDirs(view)
        db_file = prefs.getDBFilePath(view)

        # scan as async command - we don't care about the results
        sublime.status_message("PHPCI: scan file")
        phpdaemon.runAsyncRemoteCommandInPHPDaemon(prefs, 'scanFile', [src_file, scan_dirs, db_file])



##############################################################################################################################
# Plugin Commands
##############################################################################################################################

# scans a single file
class PhpCodeIntelScanFileCommand(PhpCodeIntelBase, sublime_plugin.TextCommand):

    def run(self, edit):
        prefs = preferences.load(self.view)
        self.rescanFile(prefs, self.view, self.view.file_name())

# scans a project
class PhpCodeIntelScanProjectCommand(PhpCodeIntelBase, sublime_plugin.TextCommand):

    def run(self, edit):
        prefs = preferences.load(self.view)
        scan_dirs = prefs.getProjectScanDirs(self.view)
        db_file = prefs.getDBFilePath(self.view)
        sublime.status_message("PHPCI: scanning project")
        phpdaemon.runAsyncRemoteCommandInPHPDaemon(prefs, 'scanProject', [scan_dirs, db_file])


# tells the daemon to stop
class PhpCodeIntelShutdownDaemonCommand(PhpCodeIntelBase, sublime_plugin.TextCommand):

    def run(self, edit):
        prefs = preferences.load(self.view)
        phpdaemon.runRemoteCommandInPHPDaemon(prefs, 'quit', [])




# does a 3 second sleep in the daemon.  This is used to test async commands.
class PhpCodeIntelDebugSleepCommand(PhpCodeIntelBase, sublime_plugin.TextCommand):
    def run(self, edit):
        prefs = preferences.load(self.view)
        phpdaemon.runAsyncRemoteCommandInPHPDaemon(prefs, 'debugSleep', [3])


# view.run_command('pci_test_autocomplete')
class PciTestAutocomplete(PhpCodeIntelBase, sublime_plugin.TextCommand):
    def run(self, edit):
        prefs = preferences.load(self.view)
        view = self.view
        view.run_command('auto_complete', {
            'disable_auto_insert': True,
            'api_completions_only': True,
            'next_completion_if_showing': False,
        })

# view.run_command('pci_test')
class PciTestCommand(PhpCodeIntelBase, sublime_plugin.TextCommand):
    def run(self, edit):
        prefs = preferences.load(self.view)
        # project = get_project(self.view)
        # debugMsg(prefs, 'poject is '+str(project))
        db_file_path = prefs.getDBFilePath(self.view)
        debugMsg(prefs, 'getDBFilePath = '+str(db_file_path))
        # folders = self.view.window().folders()
        # debugMsg(prefs, 'folders: '+str(folders))


##############################################################################################################################
# Autocomplete
##############################################################################################################################

completionsCache = {}

class PhpCodeIntelAutoComplete(PhpCodeIntelBase, sublime_plugin.EventListener):

    def on_query_completions(self, view, prefix, locations):
        prefs = preferences.load(view)
        debugMsg(prefs, "on_query_completions triggered");

        # don't do anything if not in a PHP source code file
        sel = view.sel()[0]
        pos = sel.end()

        # debugMsg(prefs, "scope_name="+str(view.scope_name(pos)))
        if view.score_selector(locations[0], "source.php") == 0:
            debugMsg(prefs, "not in a source.php scope")
            return []

        if prefs.autocomplete_enabled == False:
            debugMsg(prefs, "autocomplete_enabled was off")
            return []

        id = view.id()
        debugMsg(prefs, "on_query_completions id="+str(id))
        if id in completionsCache:
            _completions = completionsCache[id]
            del completionsCache[id]
            debugMsg(prefs, "_completions="+str(_completions))
            return _completions

        return []


    def on_modified(self, view):
        if view.settings().get('syntax') != 'Packages/PHP/PHP.tmLanguage':
            return

        prefs = preferences.load(view)
        self.queueBackgroundAutocompletions(prefs, view)
        # self.triggerAutocomplete(view)

        # show autocompletions in one second if there are any to show
        def _callback():
            if view.id() in completionsCache:
                self.triggerAutocomplete(prefs, view)
        sublime.set_timeout(_callback, 1000)


    def queueBackgroundAutocompletions(self, prefs, view):
        threadq.add(view, self.populateAutocompletionsCache, [prefs])
        threadq.trigger()

    def triggerAutocomplete(self, prefs, view):
        view.run_command('auto_complete', {
            'disable_auto_insert': True,
            'api_completions_only': True,
            'next_completion_if_showing': False,
        })


    def populateAutocompletionsCache(self, view, prefs):
        global completionsCache

        for thread in threading.enumerate():
            if thread.isAlive() and thread.name == "populate autocompletions thread":
                debugMsg(prefs, "autocompletions already running...")
                return

        id = view.id()
        debugMsg(prefs, "view id is "+str(id))
        # threading.thread(target=self.buildAutocompletions, name="build autocompletions", args=[view]).start()
        def _do_populate_completions(prefs, content, pos, intel_db_filepath):
            completionsCache[id] = self.buildAutocompletions(prefs, view, content, pos, intel_db_filepath)

        intel_db_filepath = prefs.getDBFilePath(view)
        content = view.substr(sublime.Region(0, view.size()))
        sel = view.sel()[0]
        pos = sel.end()

        threading.Thread(target=_do_populate_completions, name="populate autocompletions thread", args=[prefs, content, pos, intel_db_filepath]).start()

        return



    def buildAutocompletions(self, prefs, view, content, pos, intel_db_filepath):
        # # DEBUG
        # time.sleep(1.5)
        # return [('testcompletion1\tsample','test1'),('testcompletion2\tsample','test2')]
        # # DEBUG

        completions_array = phpdaemon.runRemoteCommandInPHPDaemon(prefs, 'autoComplete', [content, pos, intel_db_filepath])
        if completions_array == None:
            debugMsg(prefs, "completions_array was None");
            return

        # convert completions array into tuples for python
        completions = []
        for item in completions_array:
            completions.append((item[0], item[1]))
        return completions


    ############################################
    # rescan file on save

    def on_post_save(self, view):
        prefs = preferences.load(view)
        if prefs.rescan_on_save == True:
            extension_types = prefs.php_extensions
            for extension in prefs.php_extensions:
                if view.file_name().endswith(extension):
                    self.rescanFile(prefs, view, view.file_name())






