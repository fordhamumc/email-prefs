<?php
include_once "inc/header.php";

use Email\User;
use IMCConnector\ImcConnectorException;

$datafile = ($_SESSION['qa'] == 1) ? "data-qa.ini" : "data.ini";
$credentials = parse_ini_file(__DIR__ . "/" . $datafile, true);

/**
 * Update user object
 *
 *
 * @param user  $user         User object
 * @param mixed $imcResults   Recipient ID of the
 **/

$user = $_SESSION['user'];
if (array_key_exists('email', $_POST)) {
  $user->setEmail($_POST['email']);
}
$user->setOptOut(array_key_exists('Fordham_Opt_Out', $_POST));
$user->setPrefs($_POST);

$results = [];

/** Update IMC **/

$results['imc'] = $user->updateIMC($credentials['imc']);


/** Update Mailchimp **/

$results['mailchimp'] = $user->updateMailchimp($credentials['mailchimp']);


/** Update Banner **/

$results['banner'] = $user->updateBanner($credentials['mailer']);

?>
<?php if (!$_SESSION['plain']): ?>
<header class="intro container">
  <h1 class="intro-heading">Thank You</h1>
</header>
<?php endif; ?>
<div class="container">
  <?php if ($_SESSION['plain']): ?><h1 class="intro-heading">Thank You</h1><?php endif; ?>
  <p>You have successfully updated your preferences.</p>
  <p><?php if ($user->isResub()): ?>To complete your subscription, please click the link in the email we just sent you.<?php endif; ?></p>
</div>
<?php
include_once "inc/footer.php"; ?>