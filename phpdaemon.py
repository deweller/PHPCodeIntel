import sublime
import os
import json
import subprocess
import threading
import socket
import time

# sends a remote command to the php daemon
#  and returns the result
def runRemoteCommandInPHPDaemon(prefs, command, args, aSync=False):
    payload = {}
    payload['cmd'] = command
    payload['args'] = args
    json_string = sendMessageToPHPDaemon(prefs, json.dumps(payload), aSync)
    if aSync:
        return

    if json_string == None or json_string == '':
        debugMsg(prefs, "runRemoteCommandInPHPDaemon response: None")
        return
    response = json.loads(json_string)
    debugMsg(prefs, "runRemoteCommandInPHPDaemon response: "+json.dumps(response['msg']))
    return response['msg']

def runAsyncRemoteCommandInPHPDaemon(prefs, command, args):
    runRemoteCommandInPHPDaemon(prefs, command, args, True)


# connects to the socket, sends the message and returns the result
def sendMessageToPHPDaemon(prefs, message, aSync=False):
    sock = None
    try:
        sock = connectToSocket(prefs)
    except socket.error as e:
        debugMsg(prefs, "e.errno="+str(e.errno))
        if e.errno == 61:
            # connection refused - try restarting daemon
            debugMsg(prefs, "starting daemon")
            startPHPDaemon(prefs)

            # wait 250ms for daemon to start
            time.sleep(0.25)

            # connect again
            sock = connectToSocket(prefs)
    except Exception as e:
        debugMsg(prefs, "error starting PHP daemon: %s" % e)

    if not sock:
        warnMsg("unable to connect to socket on port "+str(prefs.daemon_port))
        return

    netstring = str(len(message))+":"+message+","
    sent = sock.send(netstring.encode('utf-8'))

    if aSync:
        threading.Thread(target=processAsyncResponse, args=(prefs,sock)).start()
        return

    data = readDataFromSocket(sock)
    return data



def processAsyncResponse(prefs, sock):
    json_string = readDataFromSocket(sock)
    if json_string == None or json_string == '':
        warnMsg("runRemoteCommandInPHPDaemon response (async): None")
        return
    response = json.loads(json_string)
    # print("response read: "+json.dumps(response['msg']))

    # do something with response['msg']
    sublime.status_message("PHPCI: done")

def readDataFromSocket(sock):
    data = ''
    while True:
        chunk = sock.recv(1024)
        if not chunk:
            break
        data = data + chunk.decode('utf-8')

    sock.close()
    return data

# initiates a socket connection
def connectToSocket(prefs):
    sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    sock.settimeout(5)
    sock.connect(('127.0.0.1', int(prefs.daemon_port)))
    return sock


# starts the PHP daemon that processes commands
def startPHPDaemon(prefs):
    debugMsg(prefs, "startPHPDaemon")

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
    debugMsg(prefs, "starting proc " + ' '.join(args))
    bin_path = os.path.join(sublime.packages_path(), 'PHPCodeIntel', 'PHP', 'bin')
    proc = subprocess.Popen(args, stdout=subprocess.PIPE, startupinfo=startupinfo, env=proc_env, cwd=bin_path)


##############################################################################################################################
# debug
##############################################################################################################################


def debugMsg(prefs, msg):
    if prefs.debug_enabled == True:
      print("[PHPCodeIntel] " + str(msg))

def warnMsg(msg):
    print("[PHPCodeIntel] WARN: " + str(msg))
