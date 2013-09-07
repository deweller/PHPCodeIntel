import sublime
import sublime_plugin
import sys
import os
from os.path import join, exists
import re
import threading
import time
from pprint import pprint

import PHPCodeIntel.preferences as preferences
import PHPCodeIntel.phpdaemon as phpdaemon


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
        phpdaemon.runRemoteCommandInPHPDaemon(prefs, 'scanFile', [src_file, scan_dirs, prefs.exclude_patterns, db_file])



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
        phpdaemon.runAsyncRemoteCommandInPHPDaemon(prefs, 'scanProject', [scan_dirs, prefs.exclude_patterns, db_file])


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


# view.run_command('pci_test')
class PciTestCommand(PhpCodeIntelBase, sublime_plugin.TextCommand):
    def run(self, edit):
        prefs = preferences.load(self.view)
        # project = get_project(self.view)
        # debugMsg(prefs, 'poject is '+str(project))
        # db_file_path = prefs.getDBFilePath(self.view)
        # debugMsg(prefs, 'getDBFilePath = '+str(db_file_path))
        # folders = self.view.window().folders()
        # debugMsg(prefs, 'folders: '+str(folders))
        # self.get_expanded_region()



##############################################################################################################################
# Quick Panel Completions
##############################################################################################################################

class PhpCodeIntelShowCompletions(PhpCodeIntelBase, sublime_plugin.TextCommand):
    def run(self, edit):
        prefs = preferences.load(self.view)
        items = self.loadCompletions(prefs)
        if len(items) == 0:
            sublime.status_message("PHPCI: No completions found.")
            return

        def response_fn(chosen_offset):
            if chosen_offset < 0:
                return
            chosen_completion = items[chosen_offset][1]
            self.view.run_command('php_code_intel_insert_completion', {'completion': chosen_completion})

        self.view.window().show_quick_panel(items, response_fn)

    def loadCompletions(self, prefs):
        completions = []

        view = self.view
        sel = view.sel()[0]
        pos = sel.end()

        if view.score_selector(pos, "source.php") == 0:
            debugMsg(prefs, "not in a source.php scope")
            sublime.status_message("PHPCI: not in PHP scope")
            return []

        intel_db_filepath = prefs.getDBFilePath(view)
        content = view.substr(sublime.Region(0, view.size()))


        completions_array = phpdaemon.runRemoteCommandInPHPDaemon(prefs, 'autoComplete', [content, pos, intel_db_filepath])
        if completions_array == None:
            debugMsg(prefs, "completions_array was None");
            return

        return completions_array;


class PhpCodeIntelInsertCompletion(sublime_plugin.TextCommand):
    def run(self, edit, selection=False, encoding='utf-8', kill=False, completion=''):
        region = self.get_expanded_region()
        self.view.replace(edit, region, completion)

    def get_expanded_region(self):
        view = self.view
        sel = view.sel()
        region = sel[0]

        end_classification = view.classify(region.end())
        if sublime.CLASS_WORD_END & end_classification:
            # at the end of a word, add the whole word
            region = view.word(region)
        elif not (end_classification & (sublime.CLASS_WORD_START | sublime.CLASS_WORD_END | sublime.CLASS_LINE_START | sublime.CLASS_LINE_END | sublime.CLASS_PUNCTUATION_START | sublime.CLASS_PUNCTUATION_END | sublime.CLASS_SUB_WORD_START | sublime.CLASS_SUB_WORD_END | sublime.CLASS_EMPTY_LINE)):
            # in the middle of a word
            region = view.word(region)

        return region

##############################################################################################################################
# Event Listener
##############################################################################################################################

completionsCache = {}

class PhpCodeIntel(PhpCodeIntelBase, sublime_plugin.EventListener):

    ############################################
    # rescan file on save

    def on_post_save_async(self, view):
        prefs = preferences.load(view)
        if prefs.rescan_on_save == True:
            debugMsg(prefs, "Saving (async)")
            extension_types = prefs.php_extensions
            for extension in prefs.php_extensions:
                if view.file_name().endswith(extension):
                    self.rescanFile(prefs, view, view.file_name())





