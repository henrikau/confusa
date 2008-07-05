<?php
class SLCSKey 
{
    private $private_key;
    private $csr;
    private $signed_csr;
    private $passphrase;

    function __construct()
    {
        $this->csr = null;
        $this->signed_csr = null;
    }

    function __destruct() 
    {
        $this->shred_object();
    }
    final function __clone()
    {
        $this->shred_object();
        $this->msg = "This is a clone!";
    }
    
    private function shred_object()
    {
        unset($this->csr);
        unset($this->signed_csr);
    }
    function __toString()
    {
        return "" . $this->msg;
    }
    /* debugging functions */
    function cert2str()    {
        return $this->export_csr() . "\n" . $this->export_scsr();
    }

    /* --------------------------------------------------------- *
     * CSR Management
     * --------------------------------------------------------- */
    function set_csr($new_csr)
    {
        if (isset($new_csr)) {
	  echo "new_csr is of type: " . get_class($new_csr) . "<BR>\n";

	  echo __FILE__ . ":" . __LINE__ . " -> New CSR set<BR>\n";
	  $this->csr = $new_csr;
        }
    }
    function get_csr()
    {
        if (isset($this->csr))
	  return $this->csr;
        return null;
    }

    function export_csr()
      {
	if (isset($this->csr))
	  {
	    openssl_csr_export($this->get_csr(), $csr, false);
	    return $csr;
	  }
      }

    function has_csr()
      {
        return isset($this->csr);
      }
    /* --------------------------------------------------------- *
     * Signed CSR Management
     * --------------------------------------------------------- */
    function set_scsr($new_scsr)
    {
        if(isset($new_scsr))
            {
                $this->signed_csr = $new_scsr;
                /* echo __FILE__ . " <B>inserted new signed csr ok</B><BR>\n"; */
            }
    }
    function get_scsr()
    {
        if(isset($this->signed_csr))
            return $this->signed_csr;
        return null;
    }
    function has_scsr()
    {
        return isset($this->signed_csr);
    }

    function export_scsr()
    {
        if ($this->has_scsr()) {
            openssl_x509_export($this->signed_csr, $tmp, false);
            return $tmp;
        }
        return "";
    }

} /* end class SLCSKey */
?>
