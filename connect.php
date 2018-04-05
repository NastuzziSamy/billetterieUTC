<?php
  include "ressources/php/include.php";

  if (isset($_GET['ticket'])) {
    include "ressources/php/class/cas.php";
    $info = CAS::authenticate();

  	if ($info != -1) {
      $_SESSION = array();
      $_SESSION['login'] = $info['cas:user'];
      $_SESSION['email'] = $info['cas:attributes']['cas:mail'];
      $_SESSION['firstname'] = $info['cas:attributes']['cas:givenName'];
      $_SESSION['lastname'] = strtoupper($info['cas:attributes']['cas:sn']);
  		$_SESSION['ticket'] = $_GET['ticket'];

      include "ressources/php/class/ginger.php";
      $infoFromGinger = $ginger->getUser($_SESSION['login']);

      $_SESSION['isAdult'] = $infoFromGinger['is_adulte'];
      $_SESSION['isContributer'] = $infoFromGinger['is_cotisant'];

      $query = $db->request(
        "SELECT id FROM users WHERE login = ?",
        array($_SESSION['login'])
      );

      if ($query->rowCount() == 0) {
        $db->request(
          "INSERT INTO users VALUES(NULL, ?, ?, ?, ?, NULL, NULL, ?, NOW())",
          array($_SESSION['login'], $_SESSION['lastname'], $_SESSION['firstname'], $_SESSION['email'], $_SESSION['isAdult'])
        );

        $query = $db->request(
          "SELECT id FROM users WHERE login = ?",
          array($_SESSION['login'])
        );

        $data = $query->fetch();
        $_SESSION['id'] = $data['id'];
      }
      else {
        $data = $query->fetch();

        $db->request(
          "UPDATE users SET login = ?, lastname = ?, firstname = ?, email = ?, isAdult = ?, lastConnection = NOW() WHERE id = ?",
          array($_SESSION['login'], $_SESSION['lastname'], $_SESSION['firstname'], $_SESSION['email'], $_SESSION['isAdult'], $data['id'])
        );

        $_SESSION['id'] = $data['id'];
      }

      $query = $db->request(
        "SELECT id FROM admins WHERE login = ?",
        array($_SESSION['login'])
      );

      $_SESSION['admin'] = ($query->rowCount() != 0);
    }
    else {
      CAS::login();
      exit;
    }
  }

  if (isset($_POST['email']) && isset($_POST['password'])) {
    $query = $db->request(
      "SELECT * FROM users WHERE email = ? AND password = ?",
      array($_POST['email'], getPassword($_POST['email'], $_POST['password']))
    );

    if ($query->rowCount() == 0)
      $error = 'L\'adresse mail et/ou le mot de passe est incorrect';
    else {
      $data = $query->fetch();

      $query = $db->request(
        "SELECT id FROM emails WHERE email = ?",
        array($_POST['email'])
      );

      if ($query->rowCount() != 0)
        $error = 'Votre adresse mail n\'a pas été confirmé, vous pouvez demander un nouvel envoi <a href="./recoveraccount.php?email='.$_POST['email'].'">ici</a>';
      else {
        $db->request(
          "UPDATE users SET lastConnection = NOW() WHERE id = ?",
          array($data['id'])
        );

        $query = $db->request(
          "DELETE FROM emails WHERE email = ?",
          array($_POST['email'])
        );

        $query = $db->request(
          "DELETE FROM passwords WHERE email = ?",
          array($_POST['email'])
        );

        $_SESSION['login'] = NULL;
        $_SESSION['email'] = $data['email'];
        $_SESSION['firstname'] = $data['firstname'];
        $_SESSION['lastname'] = $data['lastname'];
        $_SESSION['birthdate'] = $data['birthdate'];

        $query = $db->request(
          "SELECT id FROM users WHERE email = ?",
          array($_SESSION['email'])
        );

        $data = $query->fetch();
        $_SESSION['id'] = $data['id'];
      }
    }
  }

  if (isset($_GET['email']) && isset($_GET['token'])) {
    $query = $db->request(
      "SELECT id FROM emails WHERE email = ? AND token = ?",
      array($_GET['email'], $_GET['token'])
    );

    if ($query->rowCount() == 0)
      $error = 'L\'adresse email ou le token de confirmation est incorrect. Vérifiez d\'avoir bien cliqué sur le bon lien';
    else {
      $query = $db->request(
        "DELETE FROM emails WHERE email = ? AND token = ?",
        array($_GET['email'], $_GET['token'])
      );

      $success = 'Votre adresse email a été confirmée ! Vous pouvez à présent vous connecter';
      $_POST['email'] = $_GET['email'];
    }
  }
  elseif (isset($_GET['email']))
    $_POST['email'] = $_GET['email'];

  if (isset($_SESSION['id']) && isset($_SESSION['email'])) {
    if (isset($_SESSION['admin']) && $_SESSION['admin']) {
      if (isset($_GET['email'])) {
        $query = $db->request(
          "SELECT * FROM users WHERE email = ?",
          array($_GET['email'])
        );
      }
      else if (isset($_GET['login'])) {
        $query = $db->request(
          "SELECT * FROM users WHERE login = ?",
          array($_GET['login'])
        );
      }
      else
        $query = NULL;

      if ($query != NULL && $query->rowCount() != 0) {
        $data = $query->fetch();

        $_SESSION['id'] = $data['id'];
        $_SESSION['login'] = $data['login'];
        $_SESSION['email'] = $data['email'];
        $_SESSION['firstname'] = $data['firstname'];
        $_SESSION['lastname'] = $data['lastname'];
        $_SESSION['birthdate'] = $data['birthdate'];
      }
    }

    $query = $db->request(
      "SELECT id FROM tremplins WHERE email = ?",
      array($_SESSION['email'])
    );

    $_SESSION['isTremplin'] = ($query->rowCount() != 0);

    header('Location: ./billetterie.php');
    exit;
  }

  include "ressources/php/header.php";
?>

<div id="bandeau_ask">
  <div class="container">
    <form name="form_connection" method="post" action='./connect.php' style="text-align:center">
      <h3>
        Connexion
      </h3>

      <?php
        if (isset($error))
          echo '<p class="error">', $error, '</p>';
        elseif (isset($success))
          echo '<p class="success">', $success, '</p>';
        else
          echo '<p>Pour réserver une place, connectez-vous à votre compte !</p>';
      ?>

      <div>
        <input id="email" <?php if (isset($_POST['email'])) echo 'value="', $_POST['email'], '" '; ?>name="email" required="required" class="form-control" placeholder="EMAIL" type="email" />
        <input name="password" required="required" class="form-control" placeholder="MOT DE PASSE" type="password" />
      </div>

      <div style="padding-top: 25px">
        <input id='cas-button' class="button form-button" onClick="window.location.href='https://cas.utc.fr/cas/login?service=https://<?php echo $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']; ?>'" value="JE SUIS ETUDIANT(E) UTC/ESCOM" style="border-radius: 5px; margin-right: 10px" type="button">
        <input class="button form-button" value="SE CONNECTER" style="border-radius: 5px" type="submit">
      </div>

      <div>
        <a href="./recoveraccount.php">J'ai un problème pour me connecter</a>
      </div>
    </form>
  </div>
</div>

<script>
  var email = $('#email')

  var goCas = function () {
    if (email.val().indexOf('utc.fr') !== -1 || email.val().indexOf('escom.fr') !== -1) {
      $('#cas-button').click();
    }
  }

  email.on('input', goCas);
  goCas();
</script>

<?php
  include "ressources/php/footer.php";
?>
