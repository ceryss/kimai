<?php
// =====================
// = standard includes =
// =====================
require('../includes/basics.php');

// =========================
// = authentication method =
// =========================
if (!is_file(WEBROOT.'auth/' . $kga['authenticator'] . '.php')) {
    $kga['authenticator'] = 'kimai';
}
require(WEBROOT.'auth/' . $kga['authenticator'] . '.php');
$authClass  = ucfirst($kga['authenticator']).'Auth';
$authPlugin = new $authClass($database, $kga);

// =========================
// = perform action method =
// =========================
$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$banned = false;

switch ($action) {

    case 'login':
        $name = htmlspecialchars(trim($_REQUEST['name']));
        $password = $_REQUEST['password'];

        // perform login of user
        if ($authPlugin->authenticate($name,$password,$userId))
        {
            if ($userId === false) {
              $userId   = $database->usr_create(array(
                          'usr_name' => $name,
                          'usr_sts' => 2,
                          'usr_active' => 1
                        ));
              $database->setGroupMemberships($userId,array($authPlugin->getDefaultGroupId()));
            }

            $userData = $database->usr_get_data($userId);

            if ($userData['ban'] < ($kga['conf']['loginTries']) ||
                (time() - $userData['banTime']) > $kga['conf']['loginBanTime']) {

              // logintries not used up OR
              // bantime is over
              // => grant access

              $keymai=random_code(30);
              setcookie ("kimai_key",$keymai);
              setcookie ("kimai_usr",$userData['usr_name']);

              $database->usr_loginSetKey($userId,$keymai);

              header("Location: record.php");
            } else {
              // login attempt even though logintries are used up and bantime is not over => deny
              setcookie ("kimai_key","0"); setcookie ("kimai_usr","0");
              $database->loginUpdateBan($userId);

              $banned = true;
            }
            }
            else {
            // wrong username/password => deny
            setcookie ("kimai_key","0"); setcookie ("kimai_usr","0");
            if ($userId !== false)
              $database->loginUpdateBan($userId,true);

            $loginFailed = true;
        }
        break;
}


?>
<html>
  <head>
    <title></title>
    <meta content="">
    <style>
    body {
      /*background-color:black;*/
      background:url('../grfx/ki_twitter_bg.jpg') no-repeat;
      background-color:#43e820;
    }
    label {
      display:block;
    }
    </style>
  </head>
  <body>

<?php
if ($banned) {
  echo "<h1>".$kga['lang']['banned']."</h1>";
  echo $kga['lang']['tooManyLogins'];
}
?>

<form method="post">
<input type="hidden" name="action" value="login"/>
<label for="name">User:</label>
<input type="text" name="name"/>

<label for="username">Password:</label>
<input type="password" name="password"/>

<input type="submit" value="login"/>

</form> 

<?php
if (isset($loginFailed)) {
        echo "<h1>".$kga['lang']['accessDenied']."</h1>";
        echo $kga['lang']['wrongPass'];
}
?>


</body>
</html>