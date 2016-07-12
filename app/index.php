<?php
define("IMC_DIR", __DIR__."/imc_connector");
require_once IMC_DIR."/ImcConnector.php";
$credentials = parse_ini_file(IMC_DIR."/authData.ini", true);
ImcConnector::getInstance($credentials["imc"]["baseUrl"]);
ImcConnector::getInstance()->authenticateRest(
    $credentials["imc"]["client_id"],
    $credentials["imc"]["client_secret"],
    $credentials["imc"]["refresh_token"]
);

$fidn = "A" . filter_input( INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT );
$email = filter_input( INPUT_GET, "email", FILTER_SANITIZE_EMAIL );
$options = json_decode(file_get_contents(IMC_DIR."/prefOptions.json"), TRUE);
$user = false;
$role = "";
$prefsList = array();

if (substr($fidn, 1)) {
    try {
        $user = json_decode(json_encode(ImcConnector::getInstance()->selectRecipientData($credentials["imc"]["database_id"], $fidn, $email)), true);
        $email = $user["EMAIL"];
        $role = get_column_value($user, 'Role');
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
            if ($userPrefs) {
                $userPrefs = explode(";", $userPrefs);
            }
        }

        $this->values = array_map(function($value) use($userPrefs) {
            return array("name" => $value,
                         "checked" => (($userPrefs) ? in_array($value, $userPrefs) : true));
        }, $values);
    }

    function __construct($name, $label, $values, $user) {
        $this->name = $name;
        $this->label = $label;
        $this->set_values($values, $user);
    }
}


foreach($options as $option) {
    array_push($prefsList, new Preference($option["name"], $option["label"], $option["values"], $user));
}

?>
<!doctype html>
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title></title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/4.1.1/normalize.min.css">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
<div class="container logo">
    <a href="http://fordham.edu">
        <img src="img/fordham.png" width="165" alt="Fordham University">
    </a>
</div>
<header class="intro container">
    <h1 class="intro-heading">Set Your Email Preferences</h1>
    If you prefer to not receive any emails from Fordham you can <a href="#">unsubscribe from all emails</a>.<br />
        <small>*Faculty, students, and parents cannot unsubscribe from certain mandatory emails.</small>
    </p>
</header>
<form class="container" method="post" action="https://www.pages02.net/fordham-sugartest/Preferences/Universal_Preferences" pageId="6224939" siteId="248719" parentPageId="6224937" >
    <?php
    if (preg_match('/\b(student|employee)\b/i', $role)) { ?>
    <section class="form-group">
        <h3 class="text-header">Email</h3>
        <div><?php echo $email; ?></div>
        <div><small>Students, faculty, and staff can update their email address through <a href="http://my.fordham.edu" target="_blank">my.fordham.edu</a>.</small></div>
        <input type="hidden" name="Email" value="<?php echo $email; ?>">
    </section>
    <?php } else { ?>
    <section class="form-group float-label--container">
        <label class="float-label" for="email">Email</label>
        <input type="email" name="Email" id="email" class="input-text" value="<?php echo $email; ?>" required>
    </section>
    <?php } // end role match ?>

    <h3>Customize the types of emails you receive:</h3>
    <div class="form-group pref-container">
        <?php foreach ($prefsList as &$pref) { ?>
        <section id="events" role="group" class="pref-section input-group">
            <div class="pref-label"><?php echo $pref->get_label(); ?></div>
            <div class="pref-list--container">
                <div class="pref-list">
                    <div class="pref-items">
                        <?php foreach ($pref->get_values() as &$value) {?>

                        <label class="pref-item">
                            <input class="pref-selector" type="checkbox" name="<?php echo $pref->get_name(); ?>" value="<?php echo $value["name"] ?>" <?php if($value["checked"]) {echo "checked";} ?>><?php echo $value["name"] ?>
                        </label>
                        <?php } //End values Loop ?>
                    </div>
                </div>
            </div>
        </section>
        <?php } //End $prefsList Loop ?>
    </div>

    <footer class="form-footer">
        <input type="submit" value="Update Your Preferences" class="btn">
    </footer>
    <input type="hidden" name="Fordham ID" value="<?php echo $fidn; ?>">
    <input type="hidden" name="formSourceName" value="StandardForm">
    <!-- DO NOT REMOVE HIDDEN FIELD sp_exp -->
    <input type="hidden" name="sp_exp" value="yes">
</form>
<script type="text/javascript" src="js/main.js"></script>
</body>
</html>