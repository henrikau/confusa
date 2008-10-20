package confusa;

import javax.swing.*;
import java.awt.*;
import java.awt.event.*;
import java.security.KeyPair;
import confusa.Crypto;

/** Main engine for the ConfusaApplet
 * 
 * @author Henrik Austad
 */
public class ConfusaEngine extends JPanel implements ActionListener {
     private String country;
     private String org;
     private String orgUnit;
     private String common;
     private String keyLength;
     
     private JTextArea summary;
     private JButton startGen;
     private KeyPair kp;
     
     private static final long serialVersionUID = 24321;

     /**
      * 
      * @param country  The country to the user (common)
      * @param org      The organization the user belongs to
      * @param orgUnit  OrgUnit for the user
      * @param common   The commonName to the user, must be unique
      * @param keyLength    The length of the key to generate.
      */
     public ConfusaEngine (String country,
                           String org, 
                           String orgUnit, 
                           String common, 
                           String keyLength) {
          super(new GridBagLayout());
          this.country = country;
          this.org = org;
          this.orgUnit = orgUnit;
          this.common = common;
          this.keyLength = keyLength;

          this.summary = new JTextArea(5,40);
          this.summary.setEditable(false);
          this.startGen = new JButton("Generate Key");
          this.startGen.addActionListener(this);

          GridBagConstraints c = new GridBagConstraints();
          c.gridwidth = GridBagConstraints.REMAINDER;
          c.fill = GridBagConstraints.HORIZONTAL;
        
          this.summary.append("Country\t\t" + this.country + "\n");
          this.summary.append("Org\t\t" +this.org + "\n");
          this.summary.append("OrgUnit\t\t" + this.orgUnit + "\n");
          this.summary.append("CommonName\t\t"+this.common + "\n");
          this.summary.append("KeyLength\t\t"+this.keyLength + "\n");
          this.add(this.summary,c);
          this.add(this.startGen);
          
     }
     public void actionPerformed(ActionEvent ae) {
         if (this.kp == null) {
            this.kp = Crypto.gen(Integer.parseInt(this.keyLength));
            this.summary.append("\n\n");
            this.summary.append("\t" + Crypto.keyPairString(this.kp));
         }
     }
}