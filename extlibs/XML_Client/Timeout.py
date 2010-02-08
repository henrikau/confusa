#            Timeout.py is part of Confusa.
#
# All of Confusa is free software: you can redistribute it and/or
# modify it under the terms of the GNU General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# Confusa is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Confusa.  If not, see <http://www.gnu.org/licenses/>.

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
