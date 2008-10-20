package confusa;

import javax.swing.*;
import java.security.KeyPair;

public class ConfusaAppLocal {
     public static void main(String[] args) {
         if (args.length > 0) {
             if (args[0].equalsIgnoreCase("-c")) {
                 startCrypto();
             }
             else {
                 System.out.println("Unknown argument " + args[0]);
             }
         }
         else {
          System.out.println("Hello world!");
          JFrame jr = new JFrame("confusa app v.2");
          
          jr.setSize(800,600);
          jr.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
          jr.add(new ConfusaEngine("NO", "NorduGrid", "NorduGrid", "henrikau@uninett.no", "2048"));
          jr.setVisible(true);
         }
     } // end main

     private static void startCrypto() {
          KeyPair kp = Crypto.gen(512);
          System.out.println(kp);
     }
} // end ConfusaAppLocal()