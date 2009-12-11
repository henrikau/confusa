<?php
include_once 'metainfo.php';
/* the path to class.phpmailer.php should best be defined in php.ini's include
 * path */
require_once 'class.phpmailer.php';

/**
* MailManager
*
* This package sends emails to the specified address using PHPMailer
* Attachments are supported
*
* @author Henrik Austad <henrik.austad@uninett.no>
* @author Thomas Zangerl <tzangerl@pdc.kth.se>
*/
class MailManager
{
	/** The PHPMailer instance wrapped by the MailManager */
	private $mailer;

	/**
	 * Create a new MailManager and set all the relevant header flags for
	 * sending a message from $sender to $pers->getEmail
	 *
	 * @param $pers Person A person object containing recipient information
	 * @param $sender string The sender, as to be defined in the mail's
	 *                       envelope
	 * @param $senderName string The name that should appear in the sender
	 *                           field
	 * @param $sendHeader string The sender, as to be defined in the mail's
	 *                           header
	 */
	public function __construct($pers, $sender, $senderName, $sendHeader)
	{

		if (! $pers instanceof Person) {
			throw new ConfusaGenException("Error: First argument to the " .
			                              "MailManager constructor is not a " .
			                              "valid person object!");
		}

		$this->mailer = new PHPMailer();
		if (is_null($this->mailer)) {
			Framework::error_output("Could not create mailer. Aborting");
			return;
		}
		$this->mailer->CharSet = "UTF-8";
		$this->mailer->Mailer = "sendmail";
		/* set the envelope "from" address using the sendmail option -f, and
		 * the return-path header */
		$this->mailer->Sender = $sender;

		/* set the header "from" address */
		$this->mailer->From = $sendHeader;
		$this->mailer->FromName = $senderName;
		$this->mailer->WordWrap = 80;

		$this->mailer->AddAddress($pers->getEmail(),
		                          $pers->getName());

		$help_desk = $pers->getSubscriber()->getHelpEmail();
		/* add a reply-to to the helpdesk, if a helpdesk is defined */
		if (isset($help_desk)) {
			$support_name = $pers->getSubscriber()->getOrgName() . " support";
			$this->mailer->AddReplyTo($help_desk,
			                          $support_name);
		}
	} /* end constructor */

	/**
	 * Unset all the assigned headers, recipients etc. in PHPMailer
	 */
	public function __destruct()
	{
		if (!is_null($this->mailer)) {
			$this->mailer->clearAddresses();
			$this->mailer->clearAllRecipients();
			$this->mailer->clearAttachments();
			$this->mailer->clearReplyTos();
		}
	}

	/**
	 * Set the subject of the mail to the given string
	 *
	 * @param $subject string The subject of the mail
	 */
	public function setSubject($subject)
	{
		$this->mailer->Subject = $subject;
	}

	/**
	 * Set the body of the mail to the given string
	 *
	 * @param $body string The body of the mail
	 */
	public function setBody($body)
	{
		$wrapped_body = $this->mailer->WrapText($body, 75, false);
		$this->mailer->Body = $body;
	}

	/**
	 * Add an attachment to the mail (will be base64-encoded)
	 *
	 * @param $attachmentText string The attachment of the mail itself, in
	 *                               textual form
	 * @param $attachmentName string The filename of the attachment, as it will
	 *                               be displayed on the rcpt's side
	 */
	public function addAttachment($attachmentText, $attachmentName)
	{
		$this->mailer->AddStringAttachment($attachmentText,
		                                   $attachmentName,
		                                   "base64");
	} /* end addAttachment */

	/**
	 * Send the mail to the recipient
	 *
	 * @return boolean true, if succesful, false otherwise
	 */
	public function sendMail()
	{
		return $this->mailer->Send();
	} /* end sendMail */

} /* end MailManager */
?>
