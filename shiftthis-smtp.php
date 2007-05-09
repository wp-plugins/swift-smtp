<?php
/*
Plugin Name: ShiftThis.net | Swift SMTP
Plugin URI: http://www.shiftthis.net/wordpress-swift-smtp-plugin/
Description: Send email via SMTP (Compatible with GMAIL)
Author: ShiftThis.net
Version: 0.94 BETA
Author URI: http://www.shiftthis.net


CHANGELOG
Jan 28, 2007 - Fixed outdated use of $user_level variable with WordPress 2.1 compatible current_user_can() function.
Dec 17, 2006 - Fixed "Cannot redeclare class swift" Error.
Oct 29, 2006 - Fixed compatibility issue with ShiftThis Newsletter Plugin.  Added connection test to Options page.
*/
#---------------------------------------------------



	st_smtp_check_config(); //Initialize Configuration Variables
	
	add_action('admin_menu', 'st_smtp_add_pages'); //Add page menu links

	if ( isset($_POST['st_smtp_submit_options']) ) 
		add_action('init', 'st_smtp_options_submit'); //Update Options 
		

	// Load Options
	$st_smtp_config = get_option('st_smtp_config');
	
	// Determine plugin filename
	$sts_scriptname = basename(__FILE__);



#OVERRIDE WP_MAIL FUNCTION!!!!
if ( !function_exists('wp_mail') ) {
function wp_mail($to, $subject, $message, $headers=''){
global $wpdb, $table_prefix, $st_smtp_config;
$php =  phpversion();
if ($php >= 5){
		require_once('Swift5/Swift.php');
		require_once('Swift5/Swift/Connection/SMTP.php');
	} else {
		require_once('Swift4/Swift.php');
		require_once('Swift4/Swift/Connection/SMTP.php');
	}


$toplus = strpos($to, ' ');
if ($toplus == TRUE){
	$to = str_replace(' ', '+', $to);
}
//echo 'TO: '.$to.'<br>Subject: '.$subject.'<br>Message: '.$message.'<br> <br>Headers: '.$headers.'<br><br>&nbsp;';

if ($st_smtp_config['port'] == 25 ){
	//standard port 25 connect
	$connection = new Swift_Connection_SMTP($st_smtp_config['server']);
} elseif ($st_smtp_config['port'] == 465 && $st_smtp_config['ssl'] == 'ssl'){
	//standard SSL connection
	$connection = new Swift_Connection_SMTP($st_smtp_config['server'], SWIFT_SECURE_PORT, SWIFT_SSL);
} elseif ($st_smtp_config['port'] == 465 && $st_smtp_config['ssl'] == 'tls'){
	//TLS connection
	$connection = new Swift_Connection_SMTP($st_smtp_config['server'], SWIFT_SECURE_PORT, SWIFT_TLS);
} else {
	//TLS on a non-standard port (arbitrary)
	$connection = new Swift_Connection_SMTP($st_smtp_config['server'], $st_smtp_config['port'], $st_smtp_config['ssl']);
}
$mailer = new Swift($connection);


//If anything goes wrong you can see what happened in the logs
if ($mailer->isConnected()) //Optional
{
	//You can call authenticate() anywhere before calling send()
	if ($mailer->authenticate($st_smtp_config['username'], $st_smtp_config['password']))
	{		
		
		$attached = strpos($message, 'Content-Disposition: attachment;');
		$hasbcc = strpos($headers, 'Bcc:');
		$replyto = strpos($headers, 'From:');
		$html = strpos($headers, 'text/html');
		
		
		if ($replyto == TRUE){
			
			$from = strstr($headers, 'From:');
			$from = ereg_replace("[\n\r\t]", "{|}", $from);
			$from = explode('{|}', $from);
			//print_r($from);
			$from = $from[0];
			$from = str_replace('From:', '', $from);
			$from = ltrim($from);
			$from = explode('<', $from);
			$from = '"'.trim($from[0]).'" <'.$from[1];
			
		} else { $from = $st_smtp_config['username'];}
			if ($attached == TRUE){
				$messd = nl2br($message);
				$mess = explode('<br />', $messd);
				foreach($mess as $x){
					$app .= rtrim(strstr($x, 'application'), ';');
					$filename .= rtrim(str_replace('filename="', '', strstr($x, 'filename')), '"');
				}
				$name = $filename;
				$file = ABSPATH . WP_BACKUP_DIR . '/' . $filename;
				$mailer ->addPart($message);
				if ($html == TRUE){
					$mailer->addPart($message, 'text/html');
				}
				$mailer ->addAttachment(file_get_contents($file), $name, $app);
				if($headers != ''){
					$head = ereg_replace("[\n\r\t]", "{|}", $headers);
					$headarr = explode('{|}', $head);
					foreach($headarr as $hline){
					 	$mailer->addHeaders($hline);
					}
				}
				$mailer->send($to, $from, $subject);
				//echo "(ATTACHED)";

			} elseif ($hasbcc == TRUE){
				$headbr = nl2br($headers);
				$head = explode('<br />', $headbr);
				foreach($head as $h){
					if (strpos($h, '@') == TRUE){
						$e = ereg_replace("[\n\r\t]", "\t", $h);
						$e = str_replace('Bcc:', '', $e);
						$e = ltrim($e);
						$bcc[] = array('', rtrim($e, ','));
					}
				}
				unset($bcc[0]);
				unset($bcc[1]);
				$bcc= array_values($bcc);
				if ($html == TRUE){
					$mailer->addPart($message, 'text/html');
					$mailer->send($bcc, $from, $subject);
				}else{
				$mailer->send($bcc, $from, $subject, $message);
				}
				//echo "(BCC)";
			} else {
				if($headers != ''){
					$head = ereg_replace("[\n\r\t]", "{|}", $headers);
					$headarr = explode('{|}', $head);
					foreach($headarr as $hline){
						
					 	$mailer->addHeaders($hline);
					}
				}
				if ($html == TRUE){
					$mailer->addPart($message, 'text/html');
					$mailer->send($to, $from, $subject);
				}else{
				//Sends a simple email
				$mailer->send($to, $from, $subject, $message);
				}
			}
			return TRUE;
	}
	
	else echo "Didn't authenticate to server";
	//Closes cleanly... works without this but it's not as polite.
	$mailer->close();
	
}
else echo "The mailer failed to connect. Errors: <pre>".print_r($mailer->errors, 1)."</pre><br />
	Log: <pre>".print_r($mailer->transactions, 1)."</pre>";


}
}



/*-------------------------------------------------------------
 Name:      st_smtp_add_pages
 Purpose:   Add pages to admin menus
-------------------------------------------------------------*/
function st_smtp_add_pages() {

	global $st_smtp_config;
	add_options_page('SMTP', 'SMTP', 10, __FILE__, 'st_smtp_options_page');
	

}

function st_smtp_options_page() {

	// Make sure we have the freshest copy of the options
	$st_smtp_config = get_option('st_smtp_config');
	global $wpdb, $table_prefix, $php;

	
	if($_GET['remove'] == "oldjunk"){
			$deletejunk = 'DROP TABLE '.$table_prefix.'st_smtp';
			if(!$wpdb->query($deletejunk)){ echo '<div class="updated">Cleanup Successful!</div>';}
	}

	// Default options configuration page
	if ( !isset($_GET['error']) && current_user_can('level_10') ) {
		?>
		<div class="wrap">
		  	<h2>ShiftThis SMTP Swift options</h2>
		  	<form method="post" action="<?=$_SERVER['REQUEST_URI']?>&amp;updated=true">
		    	<input type="hidden" name="st_smtp_submit_options" value="true" />
				<label for="server">Server Address: </label> <input name="server" type="text" size="25" value="<?=$st_smtp_config['server']?>" /><br />
				<label for="username">Username: </label> <input name="username" type="text" size="25" value="<?=$st_smtp_config['username'];?>" /><br />
				<label for="password">Password: </label> <input name="password" type="password" size="25" value="<?=$st_smtp_config['password'];?>" /><br />
				<label for="ssl">Use SSL or TLS?: </label> <select name="ssl">
					<option value="" <?php if ($st_smtp_config['ssl'] == ''){echo 'selected="selected"';}?>>No</option>
					<option value="ssl" <?php if ($st_smtp_config['ssl'] == 'ssl'){echo 'selected="selected"';}?>>SSL</option>
					<option value="tls" <?php if ($st_smtp_config['ssl'] == 'tls'){echo 'selected="selected"';}?>>TLS</option>
					</select><br />

				<label for="port">Port: </label> <select name="port">
							<option value="25" <?php if ($st_smtp_config['port'] == "25"){echo "selected='selected'";}?>>25 (Default SMTP Port)</option>
							<option value="465" <?php if ($st_smtp_config['port'] == "465"){echo "selected='selected'";}?>>465 (Use for SSL/TLS/GMAIL)</option>
							<option value="custom" <?php if ( ($st_smtp_config['port'] != "465") && ($st_smtp_config['port'] != "25") ){echo "selected='selected'";}?>>Custom Port: (Use Box)</option>
							
					</select>&nbsp;<input name="customport" type="text" size="4" value="<?php if ($st_smtp_config['port'] == "465"){}elseif ($st_smtp_config['port'] == "25"){} else{echo $st_smtp_config['port']; } ?>" />
				
			    <p class="submit" style="text-align:left">
			      	<input type="submit" name="Submit" value="Update Options &raquo;" />
			    </p>
			</form>
			<h2>Test Connection</h2>
			<p>Once you've saved your settings, click the link below to test your connection.</p>
			<form method="post" action="<?=$_SERVER['REQUEST_URI']?>&amp;test=true">
			<label>Send Test Email to this Address:<input type="text" name="testemail" size="25" /> <input type="submit" value="Send Test" /></label><br />
			</form>

			<?php
			if ($_GET['test'] == true){
			$email = $_POST['testemail'];
			$text = "This is a test mail sent using the ShiftThis SMTP Plugin.  If you've received this email it means your connection has been set up properly!  Sweet!";
	if(@wp_mail($email, 'ShiftThis SMTP Test', $text)){
		echo '<p><strong>TEST EMAIL SENT - Connection Verified.</strong></p>';
	}
			}
	
			?>
		  <h2>Instructions</h2>
		  <p><strong>Fill in the blanks!</strong> (Gmail users need to use the server 'smtp.gmail.com' with TLS enabled and port 465.)</p>
		  
		  <h2>Upgrade Cleanup</h2>
		<p> If you are upgrading from my previous non-Swift plugin, you will notice that you need to refill your options.  This is due to my removal of using unnessesary tables in your SQL.  I recommend clearing out this old junk as it is no longer needed.  (I also recommend completely deleting the previous plugin from your server as this one uses a slightly different naming scheme anyway.  To delete the old unused tables from a previous version click the link below - DO THIS AT YOUR OWN RISK!</p>
		<p><a href="http://<?=$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'&amp;remove=oldjunk';?>" class="delete" onclick="return confirm('You are about to delete the table \'wp_st_smtp\' from your SQL database. DO THIS AT YOUR OWN RISK!\n  \'OK\' to delete, \'Cancel\' to stop.')">Upgrade Table Cleanup</a></p>
		<?php 
		$php =  phpversion();
		if ($php >= 5){
		echo '<p><small>(5) You are using PHP Version: '.$php.'</small></p>';
		}else {
		echo '<p><small>(4) You are using PHP Version: '.$php.'</small></p>';
		}
		?>
		</div>
		<?

	} // End If

}



function st_smtp_check_config() {

	if ( !$option = get_option('st_smtp_config') ) {

		// Default Options
		

		update_option('st_smtp_config', $option);

	}


}

function st_smtp_options_submit() {


	if ( current_user_can('level_10') ) {

		//options page
		$option['server'] = $_POST['server'];
		$option['username'] = $_POST['username'];
		$option['password'] = $_POST['password'];
		$option['ssl'] = $_POST['ssl'];
		if ($_POST['port'] != 'custom'){
			$option['port'] = $_POST['port'];
		} else {
			$option['port'] = $_POST['customport'];
		}
		
		update_option('st_smtp_config', $option);

	}

}
?>