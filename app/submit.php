<?php
include_once "inc/header.php";
$recipientId = $_SESSION["recipientId"];
$encodedId = $_SESSION["encodedId"];
$fields = array();


/**
 * Prep the update to send to IMC
 *
 * Loops through all of the preference fields
 * Checks if they are empty and if so adds a 'None' value for that field
 * Adds Global Opt Out field: Yes/None
 * Checks if the email field has been updated and if so sets the New_email field
 * If not it sets New_email to 'None'
**/

function set_field($name, &$fields) {
    $nameEncoded = preg_replace('/\s+/', '_', $name);

    $fields[$name] = "None";
    if ( array_key_exists($nameEncoded, $_POST) ) {
        if ( is_array( $_POST[$nameEncoded] ) ) {
            $fields[$name] = implode(";", $_POST[$nameEncoded]);
        } else {
            $fields[$name] = $_POST[$nameEncoded];
        }
    }
}

// Add preference fields to fields array
foreach($options as $option) {
    set_field($option["name"], $fields);
}

// Add global opt out to fields array
set_field("Fordham Opt Out", $fields);

// Add a new email if it is a valid email and is different from the current email
$fields["New_email"] = "None";
if ( array_key_exists("New_email", $_POST) ) {
    if ( $_POST["New_email"] !== $_POST["Email"] && filter_var($_POST["New_email"], FILTER_VALIDATE_EMAIL) ) {
        $fields["New_email"] = $_POST["New_email"];
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
    $user = json_decode(json_encode(ImcConnector::getInstance()->updateRecipient($credentials["imc"]["database_id"], $recipientId, $encodedId, $fields, $syncFields)), true);
}
catch (ImcConnectorException $sce) {
    $error = "We are having trouble updating your information.";
}


/**
 * Display response
 *
 * Displays success or error message to the user.
 * If an error occurs, it instructs the user who to contact.
 **/

$header = (isset($error)) ? "An Error Has Occurred" : "Thank You";
if (isset($error)) {
    $header = "An Error Has Occurred";
    $message = "<p class='error'>{$error}</p>
                <p>Please contact <a href='mailto:emailmarketing@fordham.edu'>emailmarketing@fordham.edu</a>.</p>";
} else {
    $header = "Thank You";
    $message = "<p>You have successfully updated your preferences.</p>
                <p>Visit the <a href=\"http://fordham.edu\">Fordham Homepage.</a></p>";
}

include_once "inc/header.php";
?>

<header class="intro container">
    <h1 class="intro-heading"><?php echo $header; ?></h1>
</header>
<div class="container"><?php echo $message; ?></div>
<?php
include_once "inc/footer.php"; ?>
