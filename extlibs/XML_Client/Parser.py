import xml.etree.ElementTree as ET
import sys
USER_LIST = 0
REVOKED_LIST = 1

class Parser:

    def __init__(self, resource):
        self.valid = False
        if not resource:
            return
        # for  res in resource.read(8192):
        #     sys.stdout.write(res)
        #     sys.stdout.flush()
        # print ""

        # return
        self.iparse = ET.iterparse(resource, ['start', 'end'])
        self.userList = []      # list of users returned
        self.revdList = []      # list of users which had their certs revoked.
        self._parse_res()

    def get_list(self, user_list = True):
        if self.valid:
            if user_list:
                return self.userList
            else:
                return self.revdList
        return None


    def _parse_res(self):
        mn  = None              # master node
        uls = False
        rls = False
        for event, element in self.iparse:
            if element.tag == "ConfusaRobot" and event == "start":
                crs = True
                mn = element
                for  k in element.keys():
                    if k == 'date':
                        self.date = element.get('date')
                    elif k == 'subscriber':
                        self.subscriber = element.get('subscriber')
                    elif k == 'version':
                        self.version = element.get('version')
                    elif k == 'elementCount':
                        self.count = int(element.get('elementCount'))
                break

        # Did not find master element, wrong type of XML
        if not mn:
            self.valid = False
            return

        # handle userList, bot user and Revocation
        list = None
        for event, elem in self.iparse:
            if elem.tag == 'userList' and event == 'start':
                uls = True
                list = elem
                continue
            if elem.tag == 'userList' and event == 'end':
                uls = False
                mn.remove(list)
                continue

            if elem.tag == 'revocationList' and event == 'start':
                rls = True
                list = elem
                continue
            if elem.tag == 'revocationList' and event == 'end':
                rls = False
                mn.remove(list)
                continue

            if uls or rls:
                users = (elem for event, elem in self.iparse
                         if event == 'end' and elem.tag == 'listElement')
                for user in users:
                    if uls:
                        self.userList.append(user.attrib)
                    if rls:
                        self.revdList.append(user.attrib)
                    list.remove(user)

        length = len(self.userList) + len(self.revdList)
        if length != self.count:
            print "Not identical number of elements parsed as described by elementCount. Data may be corrupted."
            print "Expected %d but got %d" % (self.count, length)
            self.valid = False
            return

        self.valid = True
