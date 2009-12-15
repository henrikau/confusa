<?php
  /* Dictionary file, this should be tied into the translation-portal, until
   * then, try to make it as simple as possible to add languages.
   */
$lang = array();

$lang['unauth_welcome_1'] = array(
	'en' => 'This service allows you to get or deactive a personal certificate.',
	);

$lang['unauth_login_notice'] = array(
	'en' => 'To use this service, you will need to log in.',
	);

$lang['auth_welcome_1'] = array(
	'en' => 'You have successfully authenticated to your home organization,
		 and you can now use the rest of the portal.',
	);

$lang['log_in_msg'] = array(
	'en' => "Log in",
	);

$lang['index_summary_line1'] = array(
	'alig' => 'Confusa iz da system dat maps da federated identity into da x.509 certificate.',
	'en' => 'Confusa is a system that maps a federated identity into an X.509 certificate.',
	'de' => 'Confusa ist ein System, welches eine föderierte Identität auf ein X.509
		 Zertifikat abbildet.',
	'sv' => 'Confusa är ett system, som skapper en X.509 certifikat av ett federerad
		 identitet.',
	'lb' => 'Confusa ass ee System fir federéiert Identitéiten op ee X.509 Zertifikat
		 ofzebillen.',
	);

$lang['index_summary_line2'] = array(
	'alig' => 'Da aim hof confusa iz to help yous obtain da x.509 certificate well fast usin\'
		   dees two steps:',
	'en' => 'The aim of Confusa is to help you obtain a X.509 certificate very fast using
		 these two steps:',
	'de' => 'Das Ziel von Confusa ist, Ihnen zu helfen, sehr schnell an ein X.509 Zertifikat
		 zu gelangen. Dazu genügen die folgenden zwei Schritte:',
	'sv' => 'Målet av Confusa är, att hjälpa dig att snabbt få en X.509 certificat. De
		 följande två steger är nödvändig:',
	'lb' => 'D\'Zil vu Confusa ass, Iech ze hëllefen, séier ee X.509 Zertifikat ze
		 kréien. Dofir misst dir dat folgend maachen:',
	);

$lang['index_enum_line1'] = array(
	'alig' => 'Loggin in to ya turf institushun.',
	'en' => 'Logging in to your home institution',
	'de' => 'Login zu Ihrer Heiminstitution',
	'sv' => 'Logga in till din heminstitution',
	);

$lang['index_enum_line2'] = array(
	'alig' => 'Uploadin da certificate request.',
	'en' => 'Uploading a certificate request',
	'de' => 'Upload eines certificate signing requests',
	'sv' => 'Uplad av en certificate signing request',
	);

$lang['index_summary_line3'] = array(
	'alig' => 'Da turf institushun iz gonna relay informashun about yous (name, hemail,
		   organization) to confusa. dis informashun an\' ya certificate
		   request iz gonna permit confusa an\' da backend online-ca to
		   issue da x.509 certificate to yous.',

	'en' => 'The home institution will relay information about you (name, email,
		 organization) to Confusa. This information and your certificate
		 request will permit Confusa and a backend Online-CA to issue an
		 X.509 certificate to you.',
	);

$lang['index_faq_heading1'] = array(
	'en' => 'How does it work?',
	);

$lang['index_faq_text1'] = array(
	'en' => 'The basis for it is trust. We trust your home institution to autheniticate you
		 correctly and the Online-CA trusts Confusa to do its job
		 properly.  Your home institution already identified you when
		 you signed up with it, so why verify the same information twice
		 or three times?');

$lang['index_faq_heading2'] = array(
	'en' => 'How long are the certificates valid?',
	);

$lang['index_faq_text2'] = array(
	'en' => 'Certificates issued with Confusa are valid for 13 months.',
	);

$lang['index_faq_heading3'] = array(
	'en' => 'Why do I have to login?',
	);

$lang['index_faq_text3'] = array(
	'en' => 'Before we can issue a certificate to <i>you</i>, we need to be sure about
		 <i>who</i> you are. Your home institution can do this much
		 better than what we can ever hope to do, so we delegate this
		 task. This way we can focus on what\'s important, to issue certificates as quickly and user-friendly as possible.',
	);

$lang['index_faq_heading4'] = array(
	'en' => 'Does Confusa store my private data?',
	);

$lang['index_faq_text4'] = array(
	'en' => 'Privacy has been a very big concern in the design of Confusa. When you login,
		 Confusa doesn\'t store any data about you! Only if you get a
		 certificate issued by Confusa, Confusa and/or the Online-CA
		 store the subject name of that certificate. Such a subject name
		 usually contains your country, organization and full name. We
		 <b>have</b> to store that information.',
	);
$lang['index_faq_heading5'] = array(
	'en' => "What is this \"Confusa\" I see everywhere?"
	);
$lang['index_faq_text5'] = array(
	'en' => '"Confusa" is the name of the portal software. This means that the portal
		  <i>is</i> Confusa, yet is not. A bit like the spoon. If you
		  really want to know, have a look at <a
		  href="http://www.confusa.org/">www.confusa.org</a>',
	);

?>
