#            Confusa_Parser.py is part of Confusa.
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

"""
Parsing class with constants
"""

import xml.sax.handler

STATE_INIT      = -1
STATE_ERROR     =  0
STATE_USERLIST  =  1
STATE_REVLIST   =  2
STATE_REVUSER   =  3

class ConfusaParser(xml.sax.handler.ContentHandler):
    """
    Parse the content of an XML-message from the RI at a Confusa-portal
    """
    def __init__(self):
        """Initialize object"""
        xml.sax.handler.ContentHandler.__init__(self)
        self.element_count      = 0
        self.length     = -1
        self.state      = STATE_INIT
        self.elements   = []
        self.date       = None
        self.subscr     = None
        self.count      = None
        self.version    = None

    def startElement(self, name, attrs):
        """
        Called for each element in the XML-message.
        This will do the actual parsing.
        """
        if (self.state == STATE_ERROR):
            print "In error-state, aborting"
            return

        if name == "ConfusaRobot":
            if self.length != STATE_INIT:
                # This should never trigger as ContentHandler will
                # trigger on this before we enter this function.
                print "Malformed XML. aborting"
                exit(0)

            # Verify attributes
            self.date           = attrs.get('date', None)
            self.subscr         = attrs.get('subscriber', None)
            self.count          = attrs.get('elementCount', None)
            self.version        = attrs.get('version', None)

        elif name == "userList":
            if (self.state != STATE_INIT):
                self.state = STATE_ERROR
                return
            self.state = STATE_USERLIST

        elif name == "revocationList":
            if (self.state != STATE_INIT):
                self.state = STATE_ERROR
                return
            self.state = STATE_REVLIST

        elif name == "revokedCerts":
            if (self.state != STATE_INIT):
                self.state = STATE_ERROR
                return
            self.state = STATE_REVUSER


        elif name == "listElement":
            if (self.state == STATE_INIT):
                self.state = STATE_ERROR
                print "Cannot handle element before type is determined"
                return
            eppn    = attrs.get('eppn', None)
            dn      = attrs.get('fullDN', None)
            count   = attrs.get('count', None)
            res = {}
            res['eppn'] = eppn
            if dn:
                res['fullDN'] = dn
            if count:
                res['count'] = count
            self.element_count += 1
            self.elements.append(res)

        else:
            print "Unhandled type (%s)" % (name)
            self.state = STATE_ERROR
            return
    def getElements(self):
        """Returned the parsed list of elements"""
        if (len(self.elements) != self.element_count):
            print "errors with number of parameters. Data may be corrupted"
        return self.elements
