#!/usr/bin/env python
# The HTTPSClientAuthHandler is inspired by
#       http://www.threepillarsoftware.com/soap_client_auth
#
import urllib, urllib2, httplib

class HTTPSClientAuthHandler(urllib2.HTTPSHandler):
    """
    HTTPSClientAuthHandler extends HTTPSHandler

    Client code for a http-handler capable of SSL and X.509 authN
    """
    def __init__(self,key,cert):
        urllib2.HTTPSHandler.__init__(self)
        self.key  = key
        self.cert = cert

    def https_open(self, req):
        return self.do_open(self.getConncetion ,req)

    def getConncetion(self,host,timeout=300):
        return httplib.HTTPSConnection(host,key_file=self.key, cert_file=self.cert)


class XML_Client:
    def __init__(self, key, cert, url):
        self.https_client = HTTPSClientAuthHandler(key, cert)
        self.url          = url

    def get_list(self):
        self.data    = urllib.urlencode({ 'action' : 'cert_list' })
        res = self.execute()
        # validate and parse result

        return res

    def send_revoke_list(self, eppn_list):
        post = { 'action' : 'revoke_list' }
        self.data = urllib.urlencode(post)
        res = self.execute()
        # validate and parse result

        return res

    def execute(self):
        opener = urllib2.build_opener(self.https_client)
        return opener.open(self.url, self.data)
