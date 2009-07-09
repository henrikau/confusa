<?php

/**
 * Small convenience class for reading an uploaded CSV file and parsing the
 * entries from it to an array.
 *
 * PHP version 5
 * @author Thomas Zangerl <tzangerl@pdc.kth.se>
 */

class CSV_Lib
{
    /** The unparsed content of the CSV file */
    private $content = "";
    /** The content of the CSV file in form of an array with the values */
    private $csv_entries;

    /**
     * Get a new CSV-lib trying to read the file parameter called fname and
     * reading it in to memory.
     */
    public function __construct($fname)
    {
        $this->_test_file($fname);
        $file = $_FILES[$fname]['tmp_name'];
        $fd = fopen($file, 'r');
        $fsize = filesize($file);
        $this->content = fread($fd, $fsize);
        fclose($fd);
    }

    public function __destruct()
    {
        if (isset($this->content)) {
            unset($this->content);
        }

        if (isset($this->csv_entries)) {
            unset($this->csv_entries);
        }
    }

    /**
     * Parse the CSV entries from the string representation into an array,
     * if that hasn't already happened. Otherwise return the array.
     *
     * @return Array with the values that used to be comma-separated
     */
    public function get_csv_entries()
    {
        if (isset($this->csv_entries)) {
            return $this->csv_entries;
        }

        $this->csv_entries = explode(',', $this->content);

        foreach($this->csv_entries as $csv_entry) {
            $csv_entry = trim($csv_entry);
        }

        return $this->csv_entries;
    }

    /**
     * Test if the file has been received correctly.
     *
     * @param string $file the name of the file in the $_FILES array
     *
     * @throws FileException if there is a problem with the CSV that has been
     * uploaded
     */
    private function _test_file($file)
    {
    /* check if $fname exists */
    if (isset($_FILES[$file]['name'])) {
        switch($_FILES[$file]['error']) {
        case UPLOAD_ERR_OK:
              break;

        case UPLOAD_ERR_INI_SIZE:

        case UPLOAD_ERR_FORM_SIZE:
              throw new FileException("Size of file exceeds maximum allowed filesize\n");

        case UPLOAD_ERR_PARTIAL:
              throw new FileException("Upload did not finish properly, incomplete file. Try again\n");

        case UPLOAD_ERR_NO_FILE:
              throw new FileException("No file given to upload-handler!\n");

        default:
              throw new FileException("Unknown error condition!\n");
            }
        }

        return true;
    }
}
?>
