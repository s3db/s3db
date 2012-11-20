<?php
	function send_email($U) {
		#send_email is just a function that takes an email input, together with a host and send
		#INPUTS: $U = array($email, $subject, $message) =>EMAIL CAN BE AN ARRAY OF RECIPIENTS :-)
		extract($U);
		require_once(S3DB_SERVER_ROOT.'/pearlib/Net/SMTP.php');
		$host = $GLOBALS['s3db_info']['server']['email_host'];
		$from = 's3db@s3db.org';
		if(!is_array($email)) {
			$email = array($email);
		}
		$rcpt = $email;
		$subj = 'Subject: '.$subject;
		$body = $message;
	
		/* Create a new Net_SMTP object. */
		if (! ($smtp = new Net_SMTP($host))) {
		    die("Unable to instantiate Net_SMTP object\n");
		}
	
		/* Connect to the SMTP server. */
		if (PEAR::isError($e = $smtp->connect())) {
		    die($e->getMessage() . "\n");
		}
	
		/* Send the 'MAIL FROM:' SMTP command. */
		if (PEAR::isError($smtp->mailFrom($from))) {
		    die("Unable to set sender to <$from>\n");
		}
	
		/* Address the message to each of the recipients. */
		foreach ($rcpt as $to) {
		    if (PEAR::isError($res = $smtp->rcptTo($to))) {
		        die("Unable to add recipient <$to>: " . $res->getMessage() . "\n");
		    }
		}
	
		/* Set the body of the message. */
		if (PEAR::isError($smtp->data($subj . "\r\n" . $body))) {
		    die("Unable to send data\n");
		}
	
		/* Disconnect from the SMTP server. */
		$smtp->disconnect();
	}
?>