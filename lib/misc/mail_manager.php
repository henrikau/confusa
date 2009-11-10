<?php
include_once 'metainfo.php';

  /* mail_manager.php
   *
   * This package sends emails to the specified address.
   * Attachments are supported
   *
   * @author Henrik Austad <henrik.austad@uninett.no>
   * @author Thomas Zangerl <tzangerl@pdc.kth.se>
   */

class MailManager {
    private $person;
    private $sender;
    private $senderName;
    private $sendHeader;
    private $subject;
    private $body;
    private $attachment_text;
    private $header;
    private $eol;
    private $mime_boundary;
    private $htmlalt_mime_boundary;

    public function __construct($pers, $sender, $senderName, $sendHeader, $subject,  $body)
	{
		if (!isset($pers) || ! ($pers instanceof Person)) { 
			echo __FILE__ .":".__LINE__. " Need person in MailManager!<BR>\n";
			echo __FILE__.":".__LINE__." Got " . $pers . "<br>\n";
			return;
		}
		$this->person = $pers;
		$this->receiver .= $this->person->getName() . " <" . $this->person->getEmail() . ">";
        $this->sender   = $sender;
        $this->sendHeader = $sendHeader;
        $this->senderName = $senderName;

        /* UTF-8 encode subject: */
        $this->subject = "=?UTF-8?Q?" . $this->quoted_printable_encode($subject) . "?=";
        $this->body     = $this->quoted_printable_encode($body);
        $this->attachment_text = "";
        $this->eol = "\r\n";
        $this->mime_boundary="Confusa_Part_".md5(time());
        $this->alt_mime_boundary = $this->mime_boundary . "_alt";
        $this->header   = "";

        } /* end constructor */

    public function add_attachment($attachment_text, $attachment_name) 
	{
        /* sequentially add attachments as base64 encoded text to the body
         * 
         * Content-Type: text/x-csrc; name=can.c
         * Content-Transfer-Encoding: base64
         * X-Attachment-Id: f_cvx0mgfv
         * Content-Disposition: attachment; filename=can.c
         */
        $this->attachment_text .= "--" . $this->mime_boundary . $this->eol;
        $this->attachment_text .= "Content-Type: text/plain; name=" . $attachment_name . $this->eol;
        $this->attachment_text .= "Content-Transfer-Encoding: base64" . $this->eol;
        $this->attachment_text .= "X-Attachment-Id: file-" . md5(time()) . $this->eol;
        $this->attachment_text .= "Content-Disposition: attachment; filename=" . $attachment_name . $this->eol;
        $this->attachment_text .= $this->eol;

        $this->attachment_text .= chunk_split(base64_encode($attachment_text));
        $this->attachment_text .= $this->eol;

        /* echo __FILE__ . " Added attachment " . $attachment_name . "<br>\n"; */
	} /* end add_attachment */
		
    public function send_mail()
    {
	    if (Config::get_config('auth_bypass')) {
		    Logger::log_event(LOG_NOTICE, $_SERVER['SCRIPT_FILENAME'] . ":" . __LINE__ .
				      " Refusing to send email when Confusa is placed in auth-bypass");
		    return false;
	    }
        $this->create_headers();
        $this->compose_mail();

        $mail_sent = @mail($this->receiver, $this->subject, $this->body, $this->header, "-f" . $this->sender);
	/* echo __FILE__ . ":" . __LINE__ . " -> Mail sent to $this->receiver<BR>\n"; */
        return $mail_sent;
	} /* end send_mail */

    private function compose_mail() 
        {
            $msg = "";
            /* start on the mail: */
            $msg .= "--" . $this->mime_boundary . $this->eol;
            $msg .= 'Content-Type: text/plain; charset=UTF-8' .  $this->eol;
            $msg .= 'Content-Transfer-Encoding: quoted-printable' .  $this->eol;
            $msg .= $this->eol;

            /* add body */
            $msg .= $this->body;

            /* add attachments */
            $msg .= $this->eol;
            $msg .= $this->attachment_text;

            /* add closing boundary */
            $msg .= "--" . $this->mime_boundary . "--" . $this->eol;

            /* move the message to body for sending */
            $this->body = $msg;
        } /* end compose_mail() */

    private function create_headers()
        {

        try {
			$confusaVersion = MetaInfo::getConfusaVersion();
		} catch (ConfusaGenException $cge) {
			/* take a version that won't appear that fast */
			$confusaVersion = "17.2.11";
		}

        $this->header .= "From: " . $this->senderName . " <" . $this->sendHeader . ">" . $this->eol;
        $this->header .= "Return-Path: " . $this->sender . " <" . $this->sender . ">" . $this->eol;
        $this->header .= "Date: " . date(DATE_RFC2822) . $this->eol;
        $this->header .= "Message-ID: <" . time() . "-" . $this->sender . ">" . $this->eol;
        $this->header .= "X-Mailer: Confusa/" . $confusaVersion . $this->eol;
        $this->header .= "MIME-Version: 1.0" . $this->eol;
        $this->header .= "Content-Type: multipart/mixed; boundary=\"" . $this->mime_boundary . "\"" . $this->eol;
        $this->header .= "Content-Transfer-Encoding: 7bit" . $this->eol;
        }

	/**
	 * Encode a string quoted printable and wordwrap it at the same time
	 *
	 * The following will be done:
	 * 	1) Split the original text into lines according to the existing newlines
	 * 	2) For each line:
	 * 		2.1 Encode every character that must be encoded
	 * 		2.2 Count the length, once the line length limit (default 75) is
	 * 		    reached, stop
	 * 		2.3 From the line length limit do a reverse lookup back to the last
	 * 		    whitespace
	 * 		2.4 Include the substring up to the last whitespace in the output,
	 * 		    add a qp-encoded linebreak and
	 * 		    reset the line pointer to the position of the last whitespace
	 *
	 * @param $input string The input string, not quoted-printable encoded
	 * @param $line_max integer The length at which the lines will be wrapped
	 * @return $output string The quoted-printable encoded string
	 */
	private function quoted_printable_encode($input, $line_max = 75) {
		$hex = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F');
		$lines = preg_split("/(?:\r\n|\r|\n)/", $input);
		$linebreak = "=0D=0A";
		$escape = "=";
		$output = "";
		$cur_conv_line = "";
		$length = 6; /* length of the linebreak, included in total length */

		for ($j=0; $j<count($lines); $j++) {
			$line = $lines[$j];
			$linlen = strlen($line);

			for ($i = 0; $i < $linlen; $i++) {
				$c = substr($line, $i, 1);
				$dec = ord($c);

				if ( ($dec == 32) && ($i == ($linlen - 1)) ) { // convert space at eol only
					$c = "=20";
				} elseif ( ($dec == 61) || ($dec < 32 ) || ($dec > 126) ) { // always encode "\t", which is *not* required
					$h2 = floor($dec/16); $h1 = floor($dec%16);
					$c = $escape . $hex["$h2"] . $hex["$h1"];
				}

				$length++;

				// length for wordwrap exceeded, get a newline into the text
				if ($length >= $line_max) {
					$cur_conv_line .= $c;

					// move pointer back to last whitespace, so the wordwrap takes place across word boundaries and looks nice
					$whitesp_it = $i;

					// TODO reverse search taking into account all QP-encoded chars is not very efficient
					while (($c = substr($line, $whitesp_it,1)) !== " ") {
						$dec = ord($c);

						if (($dec == 61) && ($dec < 32) || ($dec > 126)) {
							$whitesp_it -= 3;
						} else {
							$whitesp_it--;
						}
					}

					// read only up to the whitespace for the current line
					$whitesp_diff = $i - $whitesp_it;
					$output .= substr($cur_conv_line, 0, (strlen($cur_conv_line) - $whitesp_diff)) . $linebreak;

					// the text after the whitespace will have to be read again
					$i =  $i - $whitesp_diff;

					$cur_conv_line = "";
					$length = 6;
				} else {
					// length for wordwrap not reached, continue reading
					$cur_conv_line .= $c;
				}
			} // end of for

			$length = 6;
			$output .= $cur_conv_line;
			$cur_conv_line = "";

			if ($j<=count($lines)-1) {
				$output .= $linebreak;
			}
		}

		return trim($output);
		//return wordwrap(trim($output), $line_max);
	}

} /* end MailManager */
?>
