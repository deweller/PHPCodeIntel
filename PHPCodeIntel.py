import sublime
import sublime_plugin
import sys
import os
import re
import subprocess
import socket
import json
import time

settings = sublime.load_settings("PHPCodeIntel.sublime-settings")
bin_path = sublime.packages_path() + '/PHPCodeIntel/PHP/bin'

st_version = 2
if sublime.version() == '' or int(sublime.version()) > 3000:
    st_version = 3

# output debugging info to the console
def debug_message(msg):
  if settings.get('debug_enabled', False) == True:
    print("[PHPCodeIntel] " + str(msg))

# find the top level folder in sublime
def find_top_folder(view, filename):
    folders = view.window().folders()
    path = os.path.dirname(filename)

    # We don't have any folders open, return the folder this file is in
    if len(folders) == 0:
        return path

    oldpath = ''
    while not reached_top_level_folders(folders, oldpath, path):
        oldpath = path
        path = os.path.dirname(path)
    return path

# helper function for find_top_level_folder
def reached_top_level_folders(folders, oldpath, path):
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
def run_remote_cmd(command, args):
    payload = {}
    payload['cmd'] = command
    payload['args'] = args
    response = json.loads(send_message_to_php_daemon(json.dumps(payload)))
    debug_message("run_remote_cmd response: "+json.dumps(response['msg']))
    return response['msg']


# connects to the socket, sends the message and returns the result
def send_message_to_php_daemon(message):
    try:
        sock = connect_to_socket()
    except Exception, e:
        if e.errno == 61:
            # connection refused - try restarting daemon
            debug_message("starting daemon")
            start_php_daemon()

            # wait 250ms for daemon to start
            time.sleep(0.25)

            # connect again
            sock = connect_to_socket()


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
def connect_to_socket():
    sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    sock.settimeout(5)
    sock.connect(('127.0.0.1', settings.get('port', 20001)))
    return sock


# starts the PHP daemon that processes commands
def start_php_daemon():
    debug_message("startPHPDaemon")

    args = []
    args.append("php")
    args.append("daemon.php")

    # Hide the console window on Windows
    startupinfo = None
    if os.name == "nt":
        startupinfo = subprocess.STARTUPINFO()
        startupinfo.dwFlags |= subprocess.STARTF_USESHOWWINDOW

    proc_env = os.environ.copy()
    debug_message("starting proc " + ' '.join(args))
    proc = subprocess.Popen(args, stdout=subprocess.PIPE, startupinfo=startupinfo, env=proc_env, cwd=bin_path)






##############################################################################################################################
# Plugin Commands
##############################################################################################################################

# scans a single file
class PhpCodeIntelScanFileCommand(sublime_plugin.TextCommand):
    def run(self, edit):
        src_file = self.view.file_name()
        if src_file == None:
            return
        dest_file = find_top_folder(self.view, src_file) + '/.php_intel_data'
        run_remote_cmd('scanFile', [src_file, dest_file])



##############################################################################################################################
# Autocomplete
##############################################################################################################################

class PhpCodeIntelAutoComplete(sublime_plugin.EventListener):
    def on_query_completions(self, view, prefix, locations):
        if settings.get('autocomplete_enabled', False) == True:
            src_file = view.file_name()
            php_intel_file = find_top_folder(view, src_file) + '/.php_intel_data'
            completions_array = run_remote_cmd('autoComplete', ['', php_intel_file])

            # convert completions array into tuples for python
            completions = []
            for item in completions_array:
                completions.append((item[0], item[1]))
            return completions

debug_message("Plugin Loaded")

