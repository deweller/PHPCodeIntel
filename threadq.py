import sublime
import threading


q_callbacks = {}
thread_name = "PHPCodeIntel Loop"

dispatch_lock = threading.Lock()
semaphore = threading.Semaphore(0)

# let old threads die
loop_is_running = False
signal_loop_is_running = False

def add(view, callback, args=[], kwargs={}):
    dispatch_lock.acquire()
    try:
        q_callbacks[view.id()] = (view, callback, args, kwargs)
    finally:
        dispatch_lock.release()

def trigger():
    semaphore.release()


def runLoop():
    global loop_is_running
    while loop_is_running:
        semaphore.acquire()
        dispatch()

def dispatch(force=False):
    dispatch_lock.acquire()
    try:
        callbacks_to_run = q_callbacks.values()
        q_callbacks.clear()
    finally:
        dispatch_lock.release()

    for view, callback, args, kwargs in callbacks_to_run:
        def _callback():
            callback(view, *args, **kwargs)
        sublime.set_timeout(_callback, 0)


def shutdownOldThreads():
    global loop_is_running, signal_loop_is_running
    loop_is_running = False

    # shutdown old threads
    for thread in threading.enumerate():
        if thread.isAlive() and thread.name == thread_name:
            signal_loop_is_running = True

            # release the semaphore so old threads can end
            thread.semaphore.release()
            print "shutting down old thread..."
            thread.join(None)


def startThreadLoop():
    global loop_is_running
    loop_is_running = True

    q_thread = threading.Thread(target=runLoop, name=thread_name)

    # save the semaphore to the thread
    q_thread.semaphore = semaphore

    q_thread.start()


# this loop will release the semaphore once every 15 sec
# def startSignalLoop():
#     # runs a loop to trigger the queue
#     if not signal_loop_is_running:
#         # Start a regular timer
#         def _signal_loop():
#             print "_signal_loop"
#             semaphore.release()
#             sublime.set_timeout(_signal_loop, 15000)
#         _signal_loop()


shutdownOldThreads()

# startSignalLoop()

startThreadLoop()

