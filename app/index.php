<?php

include_once "inc/header.php";

use Email\User;

$datafile = ($_SESSION['qa'] == 1) ? "data-qa.ini" : "data.ini";
$credentials = parse_ini_file(__DIR__ . "/" . $datafile, true);
$options = json_decode(file_get_contents(__DIR__ . "/prefOptions.json"), TRUE);

$recipientId = filter_input( INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT );
$encodedId = filter_input( INPUT_GET, "eid", FILTER_SANITIZE_STRING );
$emailInput = filter_input( INPUT_GET, "email", FILTER_SANITIZE_EMAIL );
$source = filter_input( INPUT_GET, "source", FILTER_SANITIZE_STRING );

$user = new User($credentials, $options, $recipientId, $encodedId, $emailInput, $source);
?>

<?php if (!$_SESSION['plain']): ?>
  <header class="intro container">
    <?php if ($source === 'mcsignup'): ?>
      <h1 class="intro-heading">Subscription Confirmed</h1>
      <div>Use the form below to customize the types of emails you receive.</div>
    <?php else: ?>
      <h1 class="intro-heading">Set Your Email Preferences</h1>
      <div>Uncheck the types of emails you are not interested in receiving.</div>
    <?php endif ?>
  </header>
<?php endif ?>
<?php if ($user->exists()): ?>
  <form class="container" method="post" action="submit.php" pageId="6430542" siteId="258941" parentPageId="6430540">
    <div class="pref-container">
      <section class="input-group info-section">
        <?php echo $user->displayEmailHTML(); ?>
        <label class="unsub-item">
          <input id="input-unsub" type="checkbox" value="Yes" <?php echo ($user->isOptedOut()) ? "checked" : ""; ?> name="Fordham Opt Out"> Unsubscribe from all <?php echo ($user->isActive()) ? "non-mandatory" : ""; ?> Fordham emails
        </label>
      </section>
      <div class="prefs">
        <?php echo $user->displayPrefHTML(); ?>
      </div>
    </div>
    <footer class="form-footer">
      <input type="submit" value="Update Your Preferences" class="btn">
    </footer>
    <input type="hidden" name="formSourceName" value="StandardForm">
    <!-- DO NOT REMOVE HIDDEN FIELD sp_exp -->
    <input type="hidden" name="sp_exp" value="yes">
  </form>
<?php else: ?>
  <div class="container">
    We are having trouble locating your email address. Please contact <a href="mailto:emailmarketing@fordham.edu">emailmarketing@fordham.edu</a> to set your email preferences.
  </div>
<?php endif ?>
<?php
include_once "inc/footer.php";
$_SESSION["user"] = $user;
?>