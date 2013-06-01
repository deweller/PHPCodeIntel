import sublime
import sublime_plugin
import sys
import os
import re
import subprocess
import thread
import threading
import socket
import json
import time

import threadq
import preferences

st_version = 2
if sublime.version() == '' or int(sublime.version()) > 3000:
    st_version = 3


##############################################################################################################################
# prefs
##############################################################################################################################

prefs = None

if st_version == 2:
    prefs = preferences.init()

def plugin_loaded():
    prefs = preferences.init()


##############################################################################################################################
# debug
##############################################################################################################################

def debugMsg(msg):
    if prefs.debug_enabled == True:
      print("[PHPCodeIntel] " + str(msg))

def warnMsg(self, msg):
    print("[PHPCodeIntel] WARN: " + str(msg))


##############################################################################################################################
# base plugin
##############################################################################################################################

class PhpCodeIntelBase:

    # find the top level folder in sublime
    def getProjectRoot(self, view, filename):
        if prefs.project_root != None:
            return prefs.project_root

        folders = view.window().folders()
        path = os.path.dirname(filename)

        # We don't have any folders open, return the folder this file is in
        if len(folders) == 0:
            return path

        oldpath = ''
        while not self.reachedTopLevelFolder(folders, oldpath, path):
            oldpath = path
            path = os.path.dirname(path)
        return path

    # helper function for getProjectRoot
    def reachedTopLevelFolder(self, folders, oldpath, path):
        if oldpath == path:
            return True
        for folder in folders:
            if folder[:len(path)] == path:
                return True
            if path == os.path.dirname(folder):
                return True
        return False


    def getProjectScanDirs(self, view):
        src_file = view.file_name()
        if src_file == None:
            return

        project_root = self.getProjectRoot(view, src_file)

        scan_dirs = []
        scan_dirs.append(project_root)
        scan_dirs.extend(prefs.include_dirs)

        return scan_dirs

    def getDBFile(self, view):
        src_file = view.file_name()
        if src_file == None:
            return
        project_root = self.getProjectRoot(view, src_file)
        return project_root + '/.php_intel.sqlite3'

    # sends a remote command to the php daemon
    #  and returns the result
    def runRemoteCommandInPHPDaemon(self, command, args, aSync=False):
        payload = {}
        payload['cmd'] = command
        payload['args'] = args
        json_string = self.sendMessageToPHPDaemon(json.dumps(payload), aSync)
        if aSync:
            return

        if json_string == None or json_string == '':
            debugMsg("self.runRemoteCommandInPHPDaemon response: None")
            return
        response = json.loads(json_string)
        debugMsg("self.runRemoteCommandInPHPDaemon response: "+json.dumps(response['msg']))
        return response['msg']

    def runAsyncRemoteCommandInPHPDaemon(self, command, args):
        self.runRemoteCommandInPHPDaemon(command, args, True)


    # connects to the socket, sends the message and returns the result
    def sendMessageToPHPDaemon(self, message, aSync=False):
        sock = None
        try:
            sock = self.connectToSocket()
        except socket.error as e:
            debugMsg("e.errno="+str(e.errno))
            if e.errno == 61:
                # connection refused - try restarting daemon
                debugMsg("starting daemon")
                self.startPHPDaemon()

                # wait 250ms for daemon to start
                time.sleep(0.25)

                # connect again
                sock = self.connectToSocket()
        except Exception as e:
            debugMsg("error starting PHP daemon: %s" % e)

        if not sock:
            warnMsg("unable to connect to socket on port "+str(prefs.daemon_port))
            return

        netstring = str(len(message))+":"+message+","
        sent = sock.send(netstring)

        if aSync:
            thread.start_new_thread(self.processAsyncResponse, (sock,))
            return

        data = self.readDataFromSocket(sock)
        return data

    def processAsyncResponse(self, sock):
        json_string = self.readDataFromSocket(sock)
        if json_string == None or json_string == '':
            warnMsg("self.runRemoteCommandInPHPDaemon response (async): None")
            return
        response = json.loads(json_string)
        # print "response read: "+json.dumps(response['msg'])

        # do something with response['msg']

    def readDataFromSocket(self, sock):
        data = ''
        while True:
            chunk = sock.recv(1024)
            if not chunk:
                break
            data = data + chunk

        sock.close()
        return data

    # initiates a socket connection
    def connectToSocket(self):
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(5)
        sock.connect(('127.0.0.1', int(prefs.daemon_port)))
        return sock


    # starts the PHP daemon that processes commands
    def startPHPDaemon(self):
        debugMsg("startPHPDaemon")

        args = []
        args.append(prefs.php_path)
        args.append("daemon.php")
        args.append(str(prefs.daemon_port))

        # Hide the console window on Windows
        startupinfo = None
        if os.name == "nt":
            startupinfo = subprocess.STARTUPINFO()
            startupinfo.dwFlags |= subprocess.STARTF_USESHOWWINDOW

        proc_env = os.environ.copy()
        debugMsg("starting proc " + ' '.join(args))
        bin_path = sublime.packages_path() + '/PHPCodeIntel/PHP/bin'
        proc = subprocess.Popen(args, stdout=subprocess.PIPE, startupinfo=startupinfo, env=proc_env, cwd=bin_path)

    def rescanFile(self, view, src_file):
        scan_dirs = self.getProjectScanDirs(view)
        db_file = self.getDBFile(view)

        # scan as async command - we don't care about the results
        sublime.status_message("PHPCI: scan file")
        self.runAsyncRemoteCommandInPHPDaemon('scanFile', [src_file, scan_dirs, db_file])



##############################################################################################################################
# Plugin Commands
##############################################################################################################################

# scans a single file
class PhpCodeIntelScanFileCommand(PhpCodeIntelBase, sublime_plugin.TextCommand):

    def run(self, edit):
        self.rescanFile(self.view, self.view.file_name())

# scans a project
class PhpCodeIntelScanProjectCommand(PhpCodeIntelBase, sublime_plugin.TextCommand):

    def run(self, edit):
        scan_dirs = self.getProjectScanDirs(self.view)
        db_file = self.getDBFile(self.view)
        sublime.status_message("PHPCI: scanning project")
        self.runAsyncRemoteCommandInPHPDaemon('scanProject', [scan_dirs, db_file])


# tells the daemon to stop
class PhpCodeIntelShutdownDaemonCommand(PhpCodeIntelBase, sublime_plugin.TextCommand):

    def run(self, edit):
        self.runRemoteCommandInPHPDaemon('quit', [])

# does a 3 second sleep in the daemon.  This is used to test async commands.
class PhpCodeIntelDebugSleepCommand(PhpCodeIntelBase, sublime_plugin.TextCommand):

    def run(self, edit):
        self.runAsyncRemoteCommandInPHPDaemon('debugSleep', [3])

# view.run_command('pci_test_autocomplete')
class PciTestAutocomplete(PhpCodeIntelBase, sublime_plugin.TextCommand):
    def run(self, edit):
        view = self.view
        view.run_command('auto_complete', {
            'disable_auto_insert': True,
            'api_completions_only': True,
            'next_completion_if_showing': False,
        })

# view.run_command('pci_test_queue')
class PciTestQueueCommand(PhpCodeIntelBase, sublime_plugin.TextCommand):
    def run(self, edit):
        def myCallback(view, test_val):
            debugMsg("myCallback was called "+str(test_val))

        debugMsg("running")
        threadq.add(self.view, myCallback, ["Hi world"])

##############################################################################################################################
# Autocomplete
##############################################################################################################################

completionsCache = {}

class PhpCodeIntelAutoComplete(PhpCodeIntelBase, sublime_plugin.EventListener):

    def run(self, edit, block=False):
        view = self.view
        path = view.file_name()
        debugMsg("PhpCodeIntelAutoComplete.run")


    def on_query_completions(self, view, prefix, locations):
        # don't do anything if not in a PHP source code file
        if view.score_selector(locations[0], "source.php") == 0:
            return []

        if prefs.autocomplete_enabled == False:
            return []

        id = view.id()
        if id in completionsCache:
            _completions = completionsCache[id]
            del completionsCache[id]
            return _completions

        return []


    def on_modified(self, view):
        if view.settings().get('syntax') != 'Packages/PHP/PHP.tmLanguage':
            return

        self.queueBackgroundAutocompletions(view)
        # self.triggerAutocomplete(view)


    def queueBackgroundAutocompletions(self, view):
        threadq.add(view, self.populateAutocompletionsCache)
        threadq.trigger()

    def triggerAutocomplete(self, view):
        view.run_command('auto_complete', {
            'disable_auto_insert': True,
            'api_completions_only': True,
            'next_completion_if_showing': False,
        })


    def populateAutocompletionsCache(self, view):
        global completionsCache

        for thread in threading.enumerate():
            if thread.isAlive() and thread.name == "populate autocompletions thread":
                debugMsg("autocompletions already running...")
                return

        id = view.id()
        # threading.thread(target=self.buildAutocompletions, name="build autocompletions", args=[view]).start()
        def _do_populate_completions(content, pos, php_intel_file):
            completionsCache[id] = self.buildAutocompletions(view, content, pos, php_intel_file)

        php_intel_file = self.getProjectRoot(view, view.file_name()) + '/.php_intel.sqlite3'
        content = view.substr(sublime.Region(0, view.size()))
        sel = view.sel()[0]
        pos = sel.end()

        threading.Thread(target=_do_populate_completions, name="populate autocompletions thread", args=[content, pos, php_intel_file]).start()

        return



    def buildAutocompletions(self, view, content, pos, php_intel_file):
        # # DEBUG
        # time.sleep(1.5)
        # return [('testcompletion1\tsample','test1'),('testcompletion2\tsample','test2')]
        # # DEBUG

        completions_array = self.runRemoteCommandInPHPDaemon('autoComplete', [content, pos, php_intel_file])
        if completions_array == None:
            debugMsg("completions_array was None");
            return

        # convert completions array into tuples for python
        completions = []
        for item in completions_array:
            completions.append((item[0], item[1]))
        return completions

    def getContent(self, view):
        content = view.substr(sublime.Region(0, view.size()))
        sel = view.sel()[0]
        pos = sel.end()
        return 


    ############################################
    # rescan file on save

    def on_post_save(self, view):
        if prefs.rescan_on_save == True:
            extension_types = prefs.php_extensions
            for extension in prefs.php_extensions:
                if view.file_name().endswith(extension):
                    self.rescanFile(view, view.file_name())






debugMsg("Plugin loaded")
