<?php
include_once "inc/header.php";
include_once "mailer/mailer.php";
include_once "mailchimp/MailChimp.php";
$recipientId = $_SESSION["recipientId"];
$encodedId = $_SESSION["encodedId"];
$fidn = $_SESSION["fidn"];
$name = $_SESSION["name"];
$fields = array();
$merge = array();
$errors = "";

use \DrewM\MailChimp\MailChimp;
$MailChimp = new MailChimp($credentialsMC["api_key"]);

/**
 * Prep the update to send to IMC
 *
 * Loops through all of the preference fields
 * Checks if they are empty and if so adds a 'None' value for that field
 * Adds Global Opt Out field: Yes/None
 * Checks if the email field has been updated and if so sets the New_email field
 * If not it sets New_email to 'None'
**/

function set_field($name, $mglabel, &$fields, &$merge) {
    $nameEncoded = preg_replace('/\s+/', '_', $name);

    $fields[$name] = $merge[$mglabel] = "None";

    if ( array_key_exists($nameEncoded, $_POST) ) {
        if ( is_array( $_POST[$nameEncoded] ) ) {
            $fields[$name] = implode(";", $_POST[$nameEncoded]);
            $merge[$mglabel] = "^" . implode("^,^", $_POST[$nameEncoded]) . "^";
        } else {
            $fields[$name] = $merge[$mglabel] = $_POST[$nameEncoded];
        }
    }
}

// Add preference fields to fields array
foreach($options as $option) {
    set_field($option["name"], $option["merge"], $fields, $merge);
}

// Add global opt out to fields array
set_field("Fordham Opt Out", "OPTOUT", $fields, $merge);

// Add a new email if it is a valid email and is different from the current email
if ( array_key_exists("New_email", $_POST) ) {
    if ( $_POST["New_email"] !== $_POST["Email"] && filter_var($_POST["New_email"], FILTER_VALIDATE_EMAIL) ) {
        $fields["New_email"] = $_POST["New_email"];
    }
    if ( $_POST["New_email"] === $_SESSION["user_email"]) {
        $fields["New_email"] = "None";
    }
}

// Add Sync Field to look up record when both the recipientId and EncodedId are missing
$syncFields = array();
if (!$recipientId && !$encodedId) {
    $fields["Email"] = $_POST["New_email"];
    $syncFields["Email"] = $_POST["New_email"];
}


/**
 * Send the update to IMC
 *
 * If successful returns recipient_id
 * If an error occurs, it populates an error message
 **/

try {
    $user = json_decode(json_encode(ImcConnector::getInstance()->updateRecipient($credentialsIMC["database_id"], $recipientId, $encodedId, $fields, $syncFields)), true);
}
catch (ImcConnectorException $sce) {
    error_log( json_encode($sce) );
    $errors = "We are having trouble updating your information." . "<br>";
}


/**
 * Send the update to Mailchimp
 *
 * If successful returns member array
 * If an error occurs, it populates an error message
 **/

$subscriber_hash = $MailChimp->subscriberHash(strtolower($_POST["Email"]));
$mcstatus = $MailChimp->get("lists/{$credentialsMC['list_id']}/members/$subscriber_hash")["status"];
if ($mcstatus) {
    echo "updating mailchimp...";
    $mergefields = ["merge_fields" => $merge];
    $mcargs = array();
    if ( array_key_exists("New_email", $fields) ) {
        if (filter_var($fields["New_email"], FILTER_VALIDATE_EMAIL)) {
            $mcargs["email_address"] = $fields["New_email"];
        }
    }
    if ($fields["Fordham Opt Out"] === "Yes") {
        $mcargs["status"] = "unsubscribed";
    } elseif ($mcstatus !== "subscribed") {
        $mcargs["status"] = "pending";
    }
    $mcargs["merge_fields"] = $merge;

    $mcresult = $MailChimp->patch("lists/{$credentialsMC['list_id']}/members/$subscriber_hash", $mcargs);

    if (!$MailChimp->success()) {

        error_log( json_encode($MailChimp->getLastRequest()) );
        error_log( json_encode($MailChimp->getLastResponse()) );
        if (array_key_exists("errors", $mcresult)) {
            foreach ($mcresult["errors"] as $error) {
                $errors .= $error["message"] . "<br>";
            }
        } else {
            $errors = "We are having trouble updating your information.";
        }
    }
}


/**
 * Display response
 *
 * Displays success or error message to the user.
 * If an error occurs, it instructs the user who to contact.
 **/

$header = (empty($errors)) ? "Thank You" : "An Error Has Occurred";
if (empty($errors)) {
    $header = "Thank You";
    $message = "<p>You have successfully updated your preferences.</p>
                <p>Visit the <a href=\"http://fordham.edu\">Fordham Homepage.</a></p>";
} else {
    $header = "An Error Has Occurred";
    $message = "<p class='error'>{$errors}</p>
                <p>Please contact <a href='mailto:emailmarketing@fordham.edu'>emailmarketing@fordham.edu</a>.</p>";
}


/**
 * Update new email
 **/

$email = "";
if (array_key_exists("New_email", $fields)) {
    $email =  $fields["New_email"];
}
mailer($name, $email, $fidn);
?>
<header class="intro container">
    <h1 class="intro-heading"><?php echo $header; ?></h1>
</header>
<div class="container"><?php echo $message; ?></div>
<?php
include_once "inc/footer.php"; ?>
