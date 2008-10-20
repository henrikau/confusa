package confusa;

import javax.swing.*;

public class ConfusaAppLocal {
     public static void main(String[] args) {
          System.out.println("Hello world!");
          JFrame jr = new JFrame("confusa app v.2");
          
          jr.setSize(800,600);
          jr.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
          jr.add(new ConfusaEngine("NO", "NorduGrid", "NorduGrid", "henrikau@uninett.no", "2048"));
          jr.setVisible(true);
     } // end main
} // end ConfusaAppLocal()