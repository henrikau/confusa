package confusa;

import java.math.BigInteger;
import java.security.*;
import java.security.cert.X509Certificate;
import java.util.Date;

import javax.security.auth.x500.X500Principal;
import org.bouncycastle.x509.*;
import org.bouncycastle.x509.extension.AuthorityKeyIdentifierStructure;

public class Crypto {
    
    /** Create a keyPair with given length
     * 
     * This function creates a keyPair with the desired length. Note that the lengt must be 
     * greater than, or equal to 512 bit
     * 
     * @param keyLength length of key, in bits
     * @return KeyPair
     */
     public static KeyPair gen(int keyLength) {
         KeyPair kp = null;
         KeyPair keyPair = null;
         
         Date startDate = new Date();
         Date expiryDate = new Date();
         BigInteger serialNumber = BigInteger.valueOf(2);
         PrivateKey caKey = null;
         X509Certificate caCert = null;
         
          try {
               KeyPairGenerator kpg = KeyPairGenerator.getInstance("RSA");
               kpg.initialize(keyLength, new SecureRandom());
               kp = kpg.genKeyPair();
               keyPair = kpg.genKeyPair();
               caKey = keyPair.getPrivate();

               X509V3CertificateGenerator certGen = new X509V3CertificateGenerator();
               X500Principal              subjectName = new X500Principal("CN=Test V3 Certificate");

               certGen.setSerialNumber(serialNumber);
               // certGen.setIssuerDN(caCert.getSubjectX500Principal());
               certGen.setNotBefore(startDate);
               certGen.setNotAfter(expiryDate);
               certGen.setSubjectDN(subjectName);
               certGen.setPublicKey(keyPair.getPublic());
               certGen.setSignatureAlgorithm("RSAWithSHA1");

//               certGen.addExtension(X509Extensions.AuthorityKeyIdentifier, false,
//                                       new AuthorityKeyIdentifierStructure(caCert));
//               certGen.addExtension(X509Extensions.SubjectKeyIdentifier, false,
//                                       new SubjectKeyIdentifierStructure(keyPair.getPublic());
          }
          catch (NoSuchAlgorithmException nsae) {
               nsae.printStackTrace();
               kp = null;
          }
          finally {}
          return kp;
     }

     /**Convert a KeyPair to a string represantation
      * 
      * @param kp KeyPair to export to String
      * @return String representation of the KeyPair
      */
     public static String keyPairString(KeyPair kp) {
         return kp.toString();
     } // end keyPairString

     /** create the CSR from the given keypar with suppliced subject
      * 
      * @param kp KeyPair from which to create the CSR
      * @param subject the subject of the CSR
      * @return byte-array of CSR
      */
     public static byte[] makeCSR(KeyPair kp, 
                                  String commonName,
                                  String orgUnit,
                                  String org,
                                  String country) {
          byte[] CSR = null;
          try {
               Signature sig = Signature.getInstance("RSAWithSHA1");
               sig.initSign(kp.getPrivate());

          }
          catch (Exception e) {
               e.printStackTrace();
          }
          return CSR;
     }
} // end class Crypto
