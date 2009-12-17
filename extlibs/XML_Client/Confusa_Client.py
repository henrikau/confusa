#!/usr/bin/env python
# The HTTPSClientAuthHandler is inspired by
#       http://www.threepillarsoftware.com/soap_client_auth
#
"""

"""
import time, xml.sax.handler, urllib, urllib2
from Confusa_Parser import ConfusaParser
from Timeout import Timeout, TimeoutException
from HTTPSClient import HTTPSClientAuthHandler

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
        list = ""
        for i in eppn_list:
            if 'eppn' in i:
                list += "\t\t<listElement eppn='%s' />\n" % i['eppn']
                foundElements += 1

        if foundElements ==  0:
            print "No elements found, aborting"
            return

        if foundElements != len(eppn_list):
            print "\nErrors with supplied data, length of list is not equal to number of valid entries"
            print "This may prove to be a minor detail, continuing\n"

        listHeader = '<ConfusaRobot date="%s"' % (time.strftime("%Y-%m-%d %H:%M:%S", time.localtime()))
        listHeader += ' subscriber="%s"' % ("TEST UNIVERSITY hogwarts")
        listHeader += ' elemementCount="%d"' % (foundElements)
        listHeader += ' version="1.0">\n'
        listHeader += "\t<revocationList>\n"
        listFooter = "\t</revocationList>\n"
        listFooter += "</ConfusaRobot>\n"

        message = "%s%s%s" % (listHeader, list, listFooter)
        post['list'] = message
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
