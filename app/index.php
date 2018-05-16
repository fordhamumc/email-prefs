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
$test = filter_input( INPUT_GET, "t", FILTER_SANITIZE_NUMBER_INT );

$user = new User($credentials, $options, $recipientId, $encodedId, $emailInput, $source);
?>
<?php if (!$_SESSION['plain']): ?>
  <header class="intro container">
    <?php if ($source === 'mcsignup'): ?>
      <h1 class="intro-heading">Subscription Confirmed</h1>
      <div>Use the form below to customize the types of emails you receive.</div>
    <?php elseif ($test): ?>
      <h1 class="intro-heading">Set Your Email Preferences</h1>
      <div>Fordham University will use the information you provide on this form to stay in touch with you. Please use the options below to customize the types of emails you receive from Fordham.</div>
    <?php else: ?>
      <h1 class="intro-heading">Set Your Email Preferences</h1>
      <div>Check the types of emails you are interested in receiving.</div>
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

      <?php if ($test): ?>
        <div class="gdpr">
          <p>You can change your mind at any time by using the unsubscribe link in the footer of all the emails you receive from us, or by sending an email to <a href="mailto:emailmarketing@fordham.edu">emailmarketing@fordham.edu</a>. You can also review our <a href="https://www.fordham.edu/info/21366/policies/8331/privacy_policy">privacy policy</a>. By submitting this form, you agree that we may process your information in accordance with these terms.</p>
          <p>We use MailChimp and IBM Watson Campaign Automation as our marketing automation platforms. By submitting this form, you acknowledge that the information you provide will be transferred to MailChimp for processing in accordance with their <a href="https://mailchimp.com/legal/privacy/">Privacy Policy</a> and <a href="https://mailchimp.com/legal/terms/">Terms</a> and also to IBM Watson Campaign Automation in accordance with their <a href="https://www.ibm.com/privacy/us/en/">Privacy Policy</a> and <a href="https://www.ibm.com/legal/us/en/">Terms</a>.</p>
        </div>
      <?php endif ?>
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