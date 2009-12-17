import signal

class TimeoutException(Exception):
    pass

class Timeout:
    def __init__(self, timeout):
        if timeout < 0:
            raise Exception("Too low value")
        self.timeout = timeout
        old = signal.signal(signal.SIGALRM, self.handle_timeout)
        signal.alarm(self.timeout)

    def handle_timeout(self, signum, frame):
        raise TimeoutException("Got timeout, too slow :-)")

    def cancel_timeout(self):
        signal.alarm(0)
