import xml.sax.handler

state_init      = -1
state_error     = 0
state_userlist  = 1
state_revlist   = 2
state_revuser   = 3

# Class for parsing incoming messages (not for sending)
class ConfusaParser(xml.sax.handler.ContentHandler):
    def __init__(self):
        self.length = -1
        self.elementCount = 0
        self.state = state_init
        self.elements = []
        None

    def startElement(self,name, attrs):
        if (self.state == state_error):
            print "In error-state, aborting"
            return

        if name == "ConfusaRobot":
            if self.length != state_init:
                # This should never trigger as ContentHandler will
                # trigger on this before we enter this function.
                print "Malformed XML. aborting"
                exit(0)

            # Verify attributes
            self.date   = attrs.get('date', None)
            self.subscr = attrs.get('subscriber', None)
            self.count  = attrs.get('elementCount', None)
            self.version= attrs.get('version', None)

        elif name == "userList":
            if (self.state != state_init):
                self.state = state_error
                return
            self.state = state_userlist

        elif name == "revocationList":
            if (self.state != state_init):
                self.state = state_error
                return
            self.state = state_revlist

        elif name == "revokedCerts":
            if (self.state != state_init):
                self.state = state_error
                return
            print "List of revoked certs for a bunch of users"
            self.state = state_revuser


        elif name == "listElement":
            if (self.state == state_init):
                self.state = state_error
                print "Cannot handle element before type is determined"
            eppn    = attrs.get('eppn', None)
            dn      = attrs.get('fullDN', None)
            count   = attrs.get('count', None)
            res = {}
            res['eppn'] = eppn
            if dn:
                res['fullDN'] = dn
            if count:
                res['count'] = count
            self.elements.append(res)
            return
        else:
            print "Unhandled type (%s)" % (name)
            self.state = state_error
            return
    def getElements(self):
        return self.elements
