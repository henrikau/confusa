package confusa;

import java.security.*;
import java.security.spec.*;

import java.io.*;

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
         java.security.KeyPair kp = null;
          try {
               KeyPairGenerator kpg = KeyPairGenerator.getInstance("RSA");
               kpg.initialize(keyLength, new SecureRandom());
               kp = kpg.genKeyPair();
          }
          catch (NoSuchAlgorithmException nsae) {
               nsae.printStackTrace();
          }
          finally {}
          return kp;
     }

     /**Convert a KeyPair to a string represantation
      * 
      * @param kp KeyPair to export to String
      * @return String representation of the KeyPair
      */
     public static String KeyPairString(KeyPair kp) {
         return kp.toString();
     }
     /** create the CSR from the given keypar with suppliced subject
      * 
      * @param kp KeyPair from which to create the CSR
      * @param subject the subject of the CSR
      * @return byte-array of CSR
      */
     public static byte[] makeCSR(KeyPair kp, String subject) {
          byte[] CSR = null;

          try {
               PublicKey pubKey = kp.getPublic();


          }catch (Exception e) {
               e.printStackTrace();
          }
          return CSR;
     }
} // end class Crypto
