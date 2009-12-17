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
