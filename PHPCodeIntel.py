import sublime
import sublime_plugin
import sys
import os
import re
import subprocess
import socket
import json
import time


st_version = 2
if sublime.version() == '' or int(sublime.version()) > 3000:
    st_version = 3


##############################################################################################################################
# base plugun
##############################################################################################################################

class PhpCodeIntelBase:

    def loadSettings(self, view):
        self.settings = sublime.load_settings("PHPCodeIntel.sublime-settings")
        self.project_settings = view.settings().get('PHPCodeIntel', {})

        self.bin_path = sublime.packages_path() + '/PHPCodeIntel/PHP/bin'
        # self.debugMsg("self.settings="+json.dumps(self.settings))

    def getSetting(self, name, default):
        value = self.project_settings.get(name)
        if value == None:
            value = self.settings.get(name)
        if value == None:
            value = default
        return value

    # output debugging info to the console
    def debugMsg(self, msg):
      if self.getSetting('debug_enabled', False) == True:
        print("[PHPCodeIntel] " + str(msg))

    def warnMsg(self, msg):
        print("[PHPCodeIntel] WARN: " + str(msg))

    # find the top level folder in sublime
    def getProjectRoot(self, view, filename):
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


    # sends a remote command to the php daemon
    #  and returns the result
    def runRemoteCommandInPHPDaemon(self, command, args):
        payload = {}
        payload['cmd'] = command
        payload['args'] = args
        json_string = self.sendMessageToPHPDaemon(json.dumps(payload))
        if json_string == None or json_string == '':
            self.debugMsg("self.runRemoteCommandInPHPDaemon response: None")
            return
        response = json.loads(json_string)
        self.debugMsg("self.runRemoteCommandInPHPDaemon response: "+json.dumps(response['msg']))
        return response['msg']


    # connects to the socket, sends the message and returns the result
    def sendMessageToPHPDaemon(self, message):
        sock = None
        try:
            sock = self.connectToSocket()
        except socket.error, e:
            self.debugMsg("e.errno="+str(e.errno))
            if e.errno == 61:
                # connection refused - try restarting daemon
                self.debugMsg("starting daemon")
                self.startPHPDaemon()

                # wait 250ms for daemon to start
                time.sleep(0.25)

                # connect again
                sock = self.connectToSocket()
        except Exception, e:
            self.debugMsg("error starting PHP daemon: %s" % e)

        if not sock:
            self.warnMsg("unable to connect to socket on port "+str(self.getSetting('daemon_port', 20001)))
            return

        netstring = str(len(message))+":"+message+","
        sent = sock.send(netstring)

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
        sock.connect(('127.0.0.1', int(self.getSetting('daemon_port', 20001))))
        return sock


    # starts the PHP daemon that processes commands
    def startPHPDaemon(self):
        self.debugMsg("startPHPDaemon")

        args = []
        args.append(self.getSetting('php_path','/usr/bin/php'))
        args.append("daemon.php")
        args.append(str(self.getSetting('daemon_port', 20001)))

        # Hide the console window on Windows
        startupinfo = None
        if os.name == "nt":
            startupinfo = subprocess.STARTUPINFO()
            startupinfo.dwFlags |= subprocess.STARTF_USESHOWWINDOW

        proc_env = os.environ.copy()
        self.debugMsg("starting proc " + ' '.join(args))
        proc = subprocess.Popen(args, stdout=subprocess.PIPE, startupinfo=startupinfo, env=proc_env, cwd=self.bin_path)




##############################################################################################################################
# Plugin Commands
##############################################################################################################################

# scans a single file
class PhpCodeIntelScanFileCommand(PhpCodeIntelBase, sublime_plugin.TextCommand):

    def run(self, edit):
        self.loadSettings(self.view)
        src_file = self.view.file_name()
        if src_file == None:
            return
        db_file = self.getProjectRoot(self.view, src_file) + '/.php_intel.sqlite3'
        self.runRemoteCommandInPHPDaemon('scanFile', [src_file, db_file])

# scans a project
class PhpCodeIntelScanProjectCommand(PhpCodeIntelBase, sublime_plugin.TextCommand):

    def run(self, edit):
        self.loadSettings(self.view)
        
        src_file = self.view.file_name()
        if src_file == None:
            return
        project_root = self.getProjectRoot(self.view, src_file)

        include_dirs = []
        include_dirs.append(project_root)

        db_file = project_root + '/.php_intel.sqlite3'
        self.runRemoteCommandInPHPDaemon('scanProject', [include_dirs, db_file])


# tells the daemon to stop
class PhpCodeIntelShutdownDaemonCommand(PhpCodeIntelBase, sublime_plugin.TextCommand):

    def run(self, edit):
        self.loadSettings(self.view)
        self.runRemoteCommandInPHPDaemon('quit', [])

##############################################################################################################################
# Autocomplete
##############################################################################################################################

class PhpCodeIntelAutoComplete(PhpCodeIntelBase, sublime_plugin.EventListener):

    def on_query_completions(self, view, prefix, locations):
        self.loadSettings(view)
        if self.getSetting('autocomplete_enabled', False) == True:
            src_file = view.file_name()
            php_intel_file = self.getProjectRoot(view, src_file) + '/.php_intel.sqlite3'
            completions_array = self.runRemoteCommandInPHPDaemon('autoComplete', ['', php_intel_file])

            # convert completions array into tuples for python
            completions = []
            for item in completions_array:
                completions.append((item[0], item[1]))
            return completions


