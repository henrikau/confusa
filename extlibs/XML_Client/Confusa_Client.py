#!/usr/bin/env python
#
#            Confusa_Client.py is part of Confusa.
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

"""
import time, xml.sax.handler, urllib, urllib2
from Confusa_Parser import ConfusaParser
from Timeout import Timeout, TimeoutException
from HTTPSClient import HTTPSClientAuthHandler
import xml.etree.ElementTree as ET

class Confusa_Client:
    def __init__(self, key, cert, url):
        """
        Constructor, set the keypair to use for AuthN and the URL to connect to.

        """
        self.https_client = HTTPSClientAuthHandler(key, cert)
        self.url          = url

    def get_list(self):
        """
        Returns the list of users with valid certificates.

        """
        self.data    = urllib.urlencode({ 'action' : 'cert_list' })
        res = self.execute()
        # validate and parse result

        return res

    def send_revoke_list(self, eppn_list):
        """
        Send a list of users for which all certificates should be revoked.

        eppn_list is an array, each line with a revokeCert-dictionary entry on the form
        {'eppn' : <name>, 'fulllDN' : <dn> , 'count' : <count>}

        Only eppn will be used, the rest are optional and will be discarded
        """
        # Is list set properly?
        if (not eppn_list or not len(eppn_list) > 0):
            print "Too short list, aborting"
            return

        post = { 'action' : 'revoke_list' }


        # Construct the XML-message
        foundElements = 0
        root = ET.Element("ConfusaRobot")
        root.set("date", time.strftime("%Y-%m-%d %H:%M:%S", time.localtime()))
        root.set("subscriber", "") # not used, determined from certificate
        root.set("version", "1.0")
        rev = ET.SubElement(root, "revocationList")
        list = ""
        for i in eppn_list:
            if 'eppn' in i:
                le = ET.SubElement(rev, "listElement")
                le.set("eppn", i['eppn'])
                foundElements += 1

        if foundElements ==  0:
            print "No elements found, aborting"
            return

        if foundElements != len(eppn_list):
            print "\nErrors with supplied data, length of list is not equal to number of valid entries"
            print "This may prove to be a minor detail, continuing\n"

        root.set("elementCount", "%d" % foundElements)
        post['list'] = ET.tostring(root)
        self.data = urllib.urlencode(post)
        return self.execute()

    def execute(self, timeout=60):
        """
        execute()

        Connect to the URL and send POST-data.  The returned data will
        be passed to ConfusaParser, and either an array of
        dictionary-entries or None will be returned.

        """
        # Create the XML-parser
        parser = xml.sax.make_parser()
        parser.setFeature(xml.sax.handler.feature_namespaces, 0)
        cp = ConfusaParser()
        parser.setContentHandler(cp)

        # Get the result and parse it
        opener  = urllib2.build_opener(self.https_client)
        t = Timeout(timeout)
        try:
            parser.parse(opener.open(self.url, self.data))
        except xml.sax._exceptions.SAXParseException:
            return None
        except TimeoutException:
            print "Did not receive a timely answer (%s seconds), aborting" % (timeout)
        return cp.getElements()
