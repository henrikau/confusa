import urllib, urllib2, httplib

# The HTTPSClientAuthHandler is inspired by
#       http://www.threepillarsoftware.com/soap_client_auth
#
#       That soap-handler is used in conjunction  with Suds, which is
#       released under LGPLv3
#
#
#            HTTPSClient.py is part of Confusa.
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
