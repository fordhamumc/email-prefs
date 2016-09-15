<?php
include_once "inc/header.php";

$recipientId = filter_input( INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT );
$encodedId = filter_input( INPUT_GET, "eid", FILTER_SANITIZE_STRING );
$email = filter_input( INPUT_GET, "email", FILTER_SANITIZE_EMAIL );

$user = false;
$role = "";
$optOut = "no";
$isActive = false;
$prefsList = array();


if ($recipientId || $encodedId) {
    try {
        $user = json_decode(json_encode(ImcConnector::getInstance()->selectRecipientData($credentials["imc"]["database_id"], $recipientId, $encodedId)), true);
        $lastModified = strtotime($user['LastModified']);
        $email = $user["EMAIL"];
        $newEmail = filter_var(get_column_value($user, 'New_email'), FILTER_VALIDATE_EMAIL);

        if ( strtotime($user['LastModified']) < strtotime("+1 week") && $newEmail  ) {
            $email = $newEmail;
        }
        $role = json_encode(get_column_value($user, 'Role'));
        $optOut = get_column_value($user, 'Fordham Opt Out');
        $isActive = preg_match('/\b(student_active|employee|nb_employee)\b/i', $role);
        if ($optOut === "yes" || $optOut === "Yes") {
            $optOut = "yes";
        }

    }
    catch (ImcConnectorException $sce) {
        $user = false;
    }
}
function get_column_value($user, $name) {
    return array_values(array_filter($user["COLUMNS"]["COLUMN"], function($item) use($name) {
        return $item["NAME"] == $name;
    }))[0]["VALUE"];
}

class Preference {
    private $name;
    private $label;
    private $values;

    function get_name() {
        return $this->name;
    }

    function get_label() {
        return $this->label;
    }

    function get_values() {
        return $this->values;
    }

    function set_values($values, $user) {
        $userPrefs = false;
        if (!empty($user)) {
            $userPrefs = get_column_value($user, $this->name);
            if (is_string($userPrefs)) {
                $userPrefs = explode(";", $userPrefs);
            }
        }

        $this->values = array_map(function($value) use($userPrefs) {
            return array("name" => $value,
                "checked" => (($userPrefs === false) ? true : in_array($value, $userPrefs)));
        }, $values);
    }

    function __construct($name, $label, $values, $user) {
        $this->name     = $name;
        $this->label    = $label;
        $this->set_values($values, $user);
    }
}


foreach($options as $option) {
    array_push($prefsList, new Preference($option["name"], $option["label"], $option["values"], $user));
}

?>
<header class="intro container">
    <h1 class="intro-heading">Set Your Email Preferences</h1>
</header>
<form class="container" method="post" action="submit.php" pageId="6430542" siteId="258941" parentPageId="6430540">
    <div class="pref-container">
        <section class="input-group info-section">
            <input type="hidden" name="Email" value="<?php echo $user["EMAIL"]; ?>">
            <?php if ($isActive) { ?>
                <h3 class="text-header">Email</h3>
                <div><?php echo $email; ?></div>
            <?php } else { ?>
                <div class="float-label--container">
                    <label class="float-label" for="email">Email</label>
                    <input type="email" name="New_email" id="email" class="input-text" value="<?php echo $email; ?>" required>
                </div>
            <?php } // end role match ?>
            <label class="unsub-item">
                <input id="input-unsub" type="checkbox" value="Yes" <?php if ($optOut === "yes") { echo "checked"; } ?> name="Fordham Opt Out"> Unsubscribe from all <?php if ($isActive) { echo "non-mandatory "; } ?>Fordham emails
            </label>
        </section>

        <div class="prefs">
            <?php foreach ($prefsList as &$pref) { ?>
                <section id="<?php echo $pref->get_name(); ?>" role="group" class="pref-section input-group">
                    <div class="pref-label--container">
                        <h3 class="pref-label"><?php echo $pref->get_label(); ?></h3>
                    </div>
                    <div class="pref-list--container">
                        <div class="pref-list">
                            <?php foreach ($pref->get_values() as &$value) {?>
                                <label class="pref-item">
                                    <input class="pref-selector" type="checkbox" name="<?php echo $pref->get_name(); ?>[]" value="<?php echo $value["name"] ?>" <?php if($value["checked"]) {echo "checked";} ?>><?php echo $value["name"] ?>
                                </label>
                            <?php } //End values Loop ?>
                        </div>
                    </div>
                </section>
            <?php } //End of $prefsList ?>
        </div>
    </div>
    <footer class="form-footer">
        <input type="submit" value="Update Your Preferences" class="btn">
    </footer>
    <input type="hidden" name="formSourceName" value="StandardForm">
    <!-- DO NOT REMOVE HIDDEN FIELD sp_exp -->
    <input type="hidden" name="sp_exp" value="yes">
</form>

<?php
include_once "inc/footer.php";

$_SESSION["recipientId"] = $recipientId;
$_SESSION["encodedId"] = $encodedId;
?>