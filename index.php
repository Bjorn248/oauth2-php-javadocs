<?php

require_once getcwd() . '/includes/google-api-php-client/src/Google_Client.php';
require_once getcwd() . '/includes/google-api-php-client/src/contrib/Google_Oauth2Service.php';

// Set your cached access token. Remember to replace $_SESSION with a
// real database or memcached.
session_start();
// place this index.php file in the root of the directory you want to secure

$client = new Google_Client();
$client->setApplicationName('Whatever it may be'); // Make sure to edit this as well
// Visit https://code.google.com/apis/console?api=plus to generate your
// client id, client secret, and to register your redirect uri.
$client->setClientId('Gibberish'); //make sure to replace all relevant secrets, keys, etc below
$client->setClientSecret('More Gibberish');
$client->setRedirectUri('so much more');
$client->setDeveloperKey('too much');
$client->setScopes(array('https://www.googleapis.com/auth/userinfo.email'));
$client->setState($_SERVER['REQUEST_URI']);
$google_oauthV2 = new Google_Oauth2Service($client);
$clearURL = $_SERVER['PHP_SELF'] . '?logout';

$signedin = False;

if (isset($_GET['code'])) {
  $client->authenticate();
  $_SESSION['token'] = $client->getAccessToken();
  if (isset($_GET['state'])) {
    $original_url = $_GET['state'];
  }
  else {
    $original_url = $_SERVER['PHP_SELF'];
  }
  $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $original_url;
  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
}

if (isset($_SESSION['token'])) {
  $client->setAccessToken($_SESSION['token']);
}


if ($client->getAccessToken()) {
  // get user info from oauth response
  $user = $google_oauthV2->userinfo->get();
  $email = filter_var($user['email'], FILTER_SANITIZE_EMAIL);

  if (strpos($email, "@some_company.com") === False) { // your corporate domain here
    $message = "<span class='email'>$email</span> is not an approved email address.";
    $message .= "<br />";
    $message .= "Please sign in using an approved email address.";
    $message .= "<br />";
    print $message;
    print "<a href=$clearURL>Logout</a>";
    error_log("email is not part of the company domain");
  }
  else {
    $_SESSION['token'] = $client->getAccessToken();
    $signedin = True;
  }
} else {
  showLogin();
}

function showLogin() {
    global $client;
    $authUrl = $client->createAuthUrl();
    header('Location: '.$authUrl);
}

if (isset($_REQUEST['logout'])) {
  unset($_SESSION['token']);
  $client->revokeToken();
  $redirect = 'http://' . $_SERVER['HTTP_HOST'] . '/javadocs/index.php';
  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
}

if ($signedin) {
	print "<html>";
	print "<head>";
	print "<style>
	 a {
	  color: #087891;
	  text-decoration:none;
	  font-size:32px;
	}
	a:hover {
	  color: #043A47;
	  border-bottom:1px solid #043A47;
	}
	.links{
		text-align:center;
	}
	</style>";
	print "</head>";
	print "<body>";
	print "<div class=\"links\">";
	$files = scandir(getcwd());
	foreach($files as $file) {
		if(!(strpos($file, "index") !== false || strpos($file, "includes") !== false || strpos($file, ".") === 0)) {
			print "<a href=/javadocs/$file/index.html>$file</a>";
			print "</br>";
		}
	}
	print "</div>";
	print "</body>";
	print "</html>";
	if (isset($_GET['dest']) && empty($_GET['dest']) === false) {
		ob_end_clean();
		readfile($_GET['dest']);
	}
}

?>
