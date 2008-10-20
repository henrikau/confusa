package confusa;

import java.util.ArrayList;
import javax.swing.*;
import java.awt.*;
import java.awt.event.*;

public class ConfusaEngine extends JPanel implements ActionListener {
     private String country;
     private String org;
     private String orgUnit;
     private String common;
     private String keyLength;
     
     private JTextArea summary;
     
     private static final long serialVersionUID = 24321;

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

          GridBagConstraints c = new GridBagConstraints();
          c.gridwidth = GridBagConstraints.REMAINDER;
          c.fill = GridBagConstraints.HORIZONTAL;
        
          this.summary.append("Country\t\t" + this.country + "\n");
          this.summary.append("Org\t\t" +this.org + "\n");
          this.summary.append("OrgUnit\t\t" + this.orgUnit + "\n");
          this.summary.append("CommonName\t\t"+this.common + "\n");
          this.summary.append("KeyLength\t\t"+this.keyLength + "\n");
          this.add(this.summary,c);

     }
     public void actionPerformed(ActionEvent ae) {
     }
}