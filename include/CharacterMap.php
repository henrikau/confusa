<?php
class sspmod_core_Auth_Process_CharacterMap extends SimpleSAML_Auth_ProcessingFilter {

   /* This is going to become NREN-specific */
   private $special_chars = array('ö','ä','å','ü','ø','æ','á','à','é','è','ß');
   private $replacement_chars = array('oe','ae','aa','ue','oe','ae','a','a','e','e','sz');

   public function __construct($config, $reserved) {
          parent::__construct($config, $reserved);
   }

   public function process(&$request) {
          if (isset($request['Attributes']['cn'][0])) {
              echo $request['Attributes']['cn'][0] . "<br />\n";
              $request['Attributes']['cn'][0] = str_replace($this->special_chars, $this->replacement_chars, $request['Attributes']['cn'][0]);
              echo $request['Attributes']['cn'][0] . "<br />\n";

          }
   }
}
?>
