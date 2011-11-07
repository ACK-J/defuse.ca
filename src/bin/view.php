<?php
/* 	
	Encrypted Pastebin by FireXware 

	WWW: https://defuse.ca/
	Contact: firexware@gmail.com
*/

if($_SERVER["HTTPS"] != "on") {
   header("HTTP/1.1 301 Moved Permanently");
   header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
   die();
}

//Disable caching of viewed posts:
header("Cache-Control: no-cache, must-revalidate"); 
header("Expires: Mon, 01 Jan 1990 00:00:00 GMT"); 

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
<title>Encrypted Pastebin</title>
<style type="text/css">
	body
	{
		background-color: white; 
		color:black;
	}

	textarea
	{
	    width:100%;
		height: 200px;
		background-color: white;
		color:black;
		border:solid black 1px;
        font-family: monospace;
	}

    .div0
    {
        font-family: monospace;
        background-color: #e0e0e0;
    }
	/* every second row of text is shaded */
	.div1
	{
		background-color: #FFFFFF;
        font-family: monospace;
	}

    #timeleft
    {
        font-weight: bold;
        padding-bottom: 10px;
    }
</style>
</head>
<body>
<!-- Scripts required for client-side decryption -->
<script type="text/javascript" src="https://defuse.ca/js/cryptoHelpers.js" ></script>
<script type="text/javascript" src="https://defuse.ca/js/jsHash.js" ></script>
<script type="text/javascript" src="https://defuse.ca/js/aes.js" ></script>
<script type="text/javascript" src="https://defuse.ca/js/firexware.js" ></script>
<script type="text/javascript">
<!--
function encrypt()
{
	var pass1 = document.getElementById("pass1").value;
	var pass2 = document.getElementById("pass2").value;
	if(pass1 == pass2 && pass1 != "")
	{
		var iv = "<?php echo hash("sha256", mcrypt_create_iv(512, MCRYPT_DEV_URANDOM)) ?>";
		var salt = "<?php echo hash("sha256", mcrypt_create_iv(512, MCRYPT_DEV_URANDOM)) ?>";
		var plain = document.getElementById("paste").value;
		var ct = fxw.encrypt(pass1, salt, iv, plain);
		document.getElementById("paste").value = ct;
		document.getElementById("jscrypt").value = "yes";
		document.pasteform.submit();
	}
	else if(pass1 != pass2)
	{
		alert("Your passwords do not match! Please try again.");
	}
	else if(pass1 == "")
	{
		alert("Encrypting with no password is pointless!");
	}
}
-->
</script>
<!-- End of scripts for client-side decryption -->

<b>Encrypted, Secure Pastebin by <a href="https://defuse.ca/pastebin.htm">Defuse Cyber-Security</a></b>. See <a href="#copypaste">below</a> to copy the text and make a new post.<br /><br />
<?php

require_once('info.php');

//get the url-password
if(isset($_GET['h']))
{
	$password = $_GET['h'];
}
else //.htaccess
{
	$password = substr($_SERVER['REQUEST_URI'], 1);
}

//regenerate the key and db id
$key = hash("SHA256", $password . "243f6a8885a308d313198a2e03707344a4093822299f31d0082efa98ec4e6c89452821e638d01377be5466cf34e90c6cc0a", true);
$hash = hash("SHA256", $password);
$query = mysql_query("SELECT * FROM pastes WHERE token='$hash'");

if(mysql_num_rows($query) > 0)
{
	//decrypt the post
	$query = mysql_fetch_array($query);

    $timeleft = ($query['time'] + 10 * 3600 * 24) - time();
    $days = (int)($timeleft / (3600 * 24));
    $hours = (int)($timeleft / (3600)) % 24;
    $minutes = (int)($timeleft / 60) % 60;
    echo "<div id=\"timeleft\">This post will be deleted in <u>$days days, $hours hours, and $minutes minutes</u>.</div>";


	$data = $query['data'];
	$data = SafeDecode($query['data']);
	$data = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $data, MCRYPT_MODE_CBC, md5($key));

	if($query['jscrypt'] == "0") //client-side encryption wasn't used, so we can print it here
	{
		$data = xsssani(str_replace("\0","", $data));
		$split = explode("\n", $data);
		$i = 0;
		echo '<div style="font-family:monospace; background-color: #e0e0e0; border: solid black 1px;"><ol>';
		foreach($split as $line)
		{
			$line = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $line);
			$line = str_replace("  ", "&nbsp;&nbsp;", $line);
			echo '<li><div class="div' . $i . '">&nbsp;' . $line . '</div></li>';
			$i = ($i + 1) % 2;
		}
		echo '</ol></div>';
	}
	else //client-side decryption is required
	{
		PrintPasswordPrompt(); //shows box asking for password
		//give space for the JS to print the text
		echo '<div id="tofill" style="font-family:monospace; background-color: #e0e0e0; border: solid black 1px;"></div>';

		//output the JS decryption function, with the encrypted data embedded
		PrintDecryptor(str_replace("\0","", $data));
	}
	?>
	<form name="pasteform" id="pasteform" action="https://bin.defuse.ca/add.php" method="post">
	<a name="copypaste"></a><h2>Copy &amp; Make New Post</h2>

	<textarea id="paste" name="paste" spellcheck="false" rows="30" cols="80"><?
		if($query['jscrypt'] == "0")
			echo $data;
	?></textarea>

	<input id="jscrypt" type="hidden" name="jscrypt" value="no" />
	<input style="width:300px;" type="submit" name="submitpaste" value="Post Without Client-Side Encryption" />
	<input type="checkbox" name="shorturl" value="yes" /> Use shorter URL
	<br /> <b>Your post will be deleted automatically in 10 days.</b></form>

	<div id="encinfo">
		Client-Side Password: 
		<noscript>
			<b>Needs JavaScript!!!</b>
		</noscript>
		<input type="password" id="pass1" value="" /> &nbsp;
		Verify: <input type="password" id="pass2" value="" /> 
		<input type="button" value="Encrypt &amp; Post" onclick="encrypt()" /> 
	</div>
	<?
}
else //numrows = 0, invalid or deleted paste
{
	echo "Sorry, the paste you were looking for could not be found.";
}

//delete all posts older than 10 days
$tendaysago = time() - (3600 * 24) * 10;
mysql_query("DELETE FROM pastes WHERE time <= $tendaysago");

//------FUNCTIONS---------------------------------------------------------------
function PrintPasswordPrompt()
{
?>
	<div id="passwordprompt"><span style="color:red;"><b>Enter Password:</b></span> <input type="password" id="password" name="password" value="" /><input type="button" name="decrypt" value="Decrypt" onClick="decrypt();" /></div>
<?
}

function PrintDecryptor($data)
{
?>
<script type="text/javascript">
function decrypt(){
	var encrypted = "<? echo $data; ?>";
	var password = document.getElementById("password").value;
	if(fxw.validate(password, encrypted))
	{
		document.getElementById("passwordprompt").innerHTML = "";
		var plaintext = fxw.decrypt(password, encrypted);

		document.getElementById("paste").value = plaintext;

		var lines = plaintext.split("\n");
		var fancyLines = [];
		var i = 0; 
		fancyLines.push("<ol>");
		for(i = 0; i < lines.length; i++)
		{
			var bgColor = i % 2;
			var line = lines[i].replace("\n", "");
			line = line.replace("\r", "");
			fancyLines.push("<li><div class=\"div" + bgColor + "\">&nbsp;" + fxw.allhtmlsani(line) + "</div></li>");
		}
		fancyLines.push("</ol>");

		var fill = document.getElementById("tofill");
		fill.innerHTML = fancyLines.join('');
	}
	else
	{
		alert("Wrong password.");
	}
}
</script>
<?
}
?>
</body>
</html>
