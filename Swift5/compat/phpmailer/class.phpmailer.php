<?php

/**
 * This is a compatability stub for developers migrating from PHPMailer.
 * It is NOT in any way linked with PHPMailer, nor have the PHPMailer devs
 * endorsed it.  It simply proxies calls made to PHPMailer, over to Swift.
 * You are advised to only use this stub temporarily and only if you really
 * do not have time to re-code your application at present since you will lose
 * a small amount of performance due to the overhead of proxying.
 *
 * DO NOT study this code if you're trying to figure out how to use Swift.  Parts
 * of it are not done in the usual way as per allowing things to be done in the
 * order PHPMailer would do it (ack!).  The Unit Test cases, the documentation
 * and the examples will help you there.
 *
 * NOTE: This file has absolutely nothing to do with the real PHPMailer!
 *
 * *** MOVE THIS FILE TO THE BASE Swift/ DIRECTORY WHERE Swift.php RESIDES ***
 *
 * @package	Swift
 * @version	>= 2.1.13
 * @author	Chris Corbyn
 * @date	28th August 2006
 * @license	http://www.gnu.org/licenses/lgpl.txt Lesser GNU Public License
 *
 * @copyright Copyright &copy; 2006 Chris Corbyn - All Rights Reserved.
 * @filesource
 * 
 *   This library is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Lesser General Public
 *   License as published by the Free Software Foundation; either
 *   version 2.1 of the License, or (at your option) any later version.
 *
 *   This library is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *   Lesser General Public License for more details.
 *
 *   You should have received a copy of the GNU Lesser General Public
 *   License along with this library; if not, write to
 *
 *   The Free Software Foundation, Inc.,
 *   51 Franklin Street,
 *   Fifth Floor,
 *   Boston,
 *   MA  02110-1301  USA
 *
 *    "Chris Corbyn" <chris@w3style.co.uk>
 *
 */

//If this file is not in the Swift base directory,
// where is the directory in which Swift.php resides?
define('SWIFT_LOCATION', '.');

require_once SWIFT_LOCATION . '/Swift.php';
require_once SWIFT_LOCATION . '/Swift/Connection/NativeMail.php';
//Other require() statements executed at call-time

/**
 * Swift Mailer's Compat Stub for PHPMailer users
 * Proxies calls to PHPMailer over to Swift Mailer
 * @package Swift
 * @author Chris Corbyn
 */
class PHPMailer
{
	private $swift;
	
	public $Sender = false;
	public $Body = "";
	public $AltBody = false;
	public $CharSet = "ISO-8859-1";
	public $ConfirmReadingTo = false;
	public $ContentType = 'text/plain';
	public $Encoding = '8bit';
	public $ErrorInfo;
	public $From = 'root@localhost';
	public $FromName = 'Swift User';
	public $Host = 'localhost';
	public $Hostname; //Ignored .. strcuturally not possible to proxy
	public $Mailer = 'mail';
	public $Password;
	public $Port = 25;
	public $Priority = 3;
	public $Sendmail = '/usr/sbin/sendmail';
	public $SMTPAuth = false;
	public $SMTPDebug = false; //Ignored .. for now
	public $SMTPKeepAlive = false; //Ignored... absolutely no reason not to use it
	public $Subject;
	public $Timeout = 10;
	public $Username;
	public $Version = 'N/A'; //Ignored
	public $WordWrap = false; //Ignored
	
	//Private stuff
	public $to = array();
	private $numAttachments = 0;
	
	
	public function __construct()
	{
		$this->swift = new Swift(new Swift_Connection_NativeMail);
		$this->swift->useExactCopy();
		$this->swift->autoFlush(false);
		
		$this->errorInfo =& $this->swift->lastError;
	}
	
	public function isSMTP()
	{
		$this->Mailer = 'smtp';
	}
	
	public function isMail()
	{
		$this->Mailer = 'mail';
	}
	
	public function isSendmail()
	{
		$this->Mailer = 'sendmail';
	}
	
	public function isQmail()
	{
		$this->Sendmail = '/var/qmail/bin/sendmail';
        $this->Mailer = 'sendmail';
	}
	
	public function isHTML($is=true)
	{
		if ($is) $this->ContentType = 'text/html';
		else $this->ContentType = 'text/plain';
	}
	
	public function AddAddress($address, $name=false)
	{
		$this->to [] = $this->makeAddress($address, $name);
	}
	
	public function AddReplyTo($address, $name=false)
	{
		$this->swift->setReplyTo($this->makeAddress($address, $name));
	}
	
	public function AddAttachment($path, $name='', $encoding='', $type='application/octet-stream')
	{
		if (!file_exists($path) || is_dir($path))
		{
			$this->swift->logError('Attachment Path Not found');
			return false;
		}
		if (!$name) $name = basename($path);
		
		$mq = get_magic_quotes_runtime();
        set_magic_quotes_runtime(0);
		$data = file_get_contents($path);
		set_magic_quotes_runtime($mq);
		
		$this->swift->addAttachment($data, $name, $type);
		$this->numAttachments++;
	}
	
	public function AddEmbeddedImage($path, $cid, $name='', $encoding='', $type='application/octet-stream')
	{
		if (!file_exists($path) || is_dir($path))
		{
			$this->swift->logError('Attachment Path Not found');
			return false;
		}
		if (!$name) $name = basename($path);
		
		$mq = get_magic_quotes_runtime();
        set_magic_quotes_runtime(0);
		$data = file_get_contents($path);
		set_magic_quotes_runtime($mq);
		
		$this->numAttachments++;
		return $this->swift->embedFile($data, $type, $name, $cid);
	}
	
	public function AddStringAttachment($string, $name, $encoding='', $type='application/octet-stream')
	{
		$this->swift->addAttachment($string, $name, $type);
		$this->numAttachments++;
	}
	
	public function AddBCC($address, $name=false)
	{
		$this->swift->addBcc($this->makeAddress($address, $name));
	}
	
	public function AddCC($address, $name=false)
	{
		$this->swift->addCc($this->makeAddress($address, $name));
	}
	
	public function ClearAddresses()
	{
		$this->to = array();
		$this->swift->flushTo();
	}
	
	public function ClearAttachments()
	{
		$this->swift->flushAttachments();
		$this->numAttachments = 0;
	}
	
	public function ClearAllRecipients()
	{
		$this->swift->flushTo();
		$this->swift->flushCc();
		$this->swift->flushBcc();
	}
	
	public function ClearBCCs()
	{
		$this->swift->flushBcc();
	}
	
	public function ClearCCs()
	{
		$this->swift->flushCc();
	}
	
	public function ClearCustomHeaders()
	{
		$this->swift->flushHeaders();
	}
	
	public function ClearReplyTos()
	{
		$this->swift->setReplyTo(null);
	}
	
	public function isError()
	{
		return !empty($this->swift->errors);
	}
	
	public function SmtpClose()
	{
		$this->swift->close();
	}
	
	public function AddCustomHeader($string=false)
	{
		if ($string)
		{
			$this->swift->addheaders($string);
		}
	}
	/**
	 * Send the message.
	 * Not very optimal at all, but PHPMailer doesn't help us with
	 * its lack of setters for catching actions.
	 * @return boolean
	 */
	public function Send()
	{
		//Swap the mailer if we need to
		switch (strtolower($this->Mailer))
		{
			case 'smtp':
			
			require_once SWIFT_LOCATION . '/Swift/Connection/Multi.php';
			require_once SWIFT_LOCATION . '/Swift/Connection/SMTP.php';
			
			$hosts = preg_split('/\s*[;,]\s*/', $this->Host);
			
			$connections = array();
			foreach ($hosts as $host)
			{
				if (strlen($host) == 0) break;
				
				$parts = explode(':', $host);
				$conn = new Swift_Connection_SMTP(
			      $parts[0], !empty($parts[1]) ? $parts[1] : $this->Port);
				$conn->setConnectTimeout($this->Timeout);
				
				$connections[] = $conn;
			}
			
			//Disconnect while we swap mailers
			$this->swift->close();
			
			if (count($connections) > 1)
			{
				$this->swift->connection = new Swift_Connection_Multi($connections);
			}
			elseif (count($connections == 1))
			{
				$this->swift->connection = $connections[0];
			}
			//The reconnect again
			$this->swift->connect();
			
			//Authenticate if needed
			if ($this->SMTPAuth)
			{
				$this->swift->authenticate($this->Username, $this->Password);
				if ($this->swift->hasFailed()) $this->swift->failed = false;
				$this->swift->reset();
			}
			
			break;
			
			//End case 'smtp'
			
			case 'sendmail':
			
			require_once SWIFT_LOCATION . '/Swift/Connection/Sendmail.php';
			
			$this->swift->close();
			
			$this->swift->connection = new Swift_Connection_Sendmail($this->Sendmail.' -bs');
			
			$this->swift->connect();
			
			break;
			
			//End case 'sendmail'
			
			case 'mail': default:
			break;
		} //End switch
		
		if ($this->Sender) $this->swift->setReturnPath($this->Sender);
		if ($this->ConfirmReadingTo) $this->swift->requestReadReceipt();
		
		$this->swift->setPriority($this->Priority);
		
		$this->swift->setCharset($this->CharSet);
		
		if ($this->AltBody || $this->numAttachments)
		{
			$this->swift->addPart($this->Body, $this->ContentType);
			if ($this->AltBody) $this->swift->addPart($this->AltBody);
			$sent = $this->swift->send(
			  $this->to,
			  $this->makeAddress($this->From, $this->FromName),
			  $this->Subject);
		}
		else
		{
			$sent = $this->swift->send(
			  $this->to,
			  $this->makeAddress($this->From, $this->FromName),
			  $this->Subject,
			  $this->Body,
			  $this->ContentType);
		}
		$this->swift->flushParts();
		return $sent;
	}
	/**
	 * Not implemented
	 */
	public function SetLanguage()
	{
		//Not implemented
		return true;
	}
	/**
	 * Turn the address into a format swift will use
	 * @param string address
	 * @param string name
	 * @return string composed address
	 */
	private function makeAddress($address, $name)
	{
		$ret = $address;
		if ($name) $ret = '"'.$name.'" <'.$address.'>';
		return $ret;
	}
}

?>