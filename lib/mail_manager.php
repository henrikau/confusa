<?php
  /* mail_manager.php
   *
   * This package sends emails to the specified address.
   */

class MailManager {
    private $person;
    private $sender;
    private $subject;
    private $body;
    private $attachment_text;
    private $header;
    private $eol;
    private $mime_boundary;
    private $htmlalt_mime_boundary;

    public function __construct($pers, $sender, $subject,  $body) 
	{
		if (!isset($pers) || ! ($pers instanceof SLCSPerson)) { 
			echo __FILE__ .":".__LINE__. " Need person in MailManager!<BR>\n";
			echo __FILE__.":".__LINE__." Got " . $pers . "<br>\n";
			return;
		}
		$this->person = $pers;
		$this->receiver .= $this->person->get_name() . " <" . $this->person->get_email() . ">";

        $this->sender   = $sender;
        /* UTF-8 encode subject: */
        /* $this->subject  = "=?UTF-8?B?" . base64_encode("Subject: " . subject)."?"; */
        $this->subject = $subject;
        $this->body     = $body;
        $this->attachment_text = "";
        $this->eol = "\r\n";
        $this->mime_boundary="SLCSWeb_Part_".md5(time());
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
        $this->create_headers();
        $this->compose_mail();

        $mail_sent = @mail($this->receiver, $this->subject, $this->body, $this->header);
	/* echo __FILE__ . ":" . __LINE__ . " -> Mail sent to $this->receiver<BR>\n"; */
        return $mail_sent;
	} /* end send_mail */

    private function compose_mail() 
        {
            $msg = "";
            /* start on the mail: */
            $msg .= "--" . $this->mime_boundary . $this->eol;
            $msg .= "Content-Type: text/plain" .  $this->eol;
            $msg .= "Content-Transfer-Encoding: 7bit" .  $this->eol;
            $msg .= $this->eol;

            /* add body */
            $msg .= $this->body . $this->eol;

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
        $this->header .= "From: " . $this->sender . " <" . $this->sender . ">" . $this->eol;
        $this->header .= "Reply-To: " . $this->sender . " <" . $this->sender . ">" . $this->eol;
        $this->header .= "Return-Path: " . $this->sender . " <" . $this->sender . ">" . $this->eol;
        $this->header .= "Date: " . date(DATE_RFC822) . $this->eol;
        $this->header .= "Message-ID: <" . time() . "-" . $this->sender . ">" . $this->eol;
        $this->header .= "X-Mailer: PHP v".phpversion().$this->eol;
        $this->header .= "MIME-Version: 1.0" . $this->eol;
        $this->header .= "Content-Type: multipart/mixed; boundary=\"" . $this->mime_boundary . "\"" . $this->eol;
        $this->header .= "Content-Transfer-Encoding: 7bit" . $this->eol;
        }
      } /* end MailManager */
?>
