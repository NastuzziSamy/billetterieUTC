<?php
  include "ressources/php/include.php";

  if (isset($_SESSION['id'])) {
    header('Location: ./account.php');
    exit;
  }

  include "ressources/php/header.php";
?>

<div id="bandeau_help">
  <div class="container">
    <form name="form_reinit" method="post" style="text-align:center">
      <h3>
        Réinitialisation du mot de passe
      </h3>
      <?php
        if (isset($_GET['email']) && isset($_GET['token'])) {
          $query = $db->request(
            "SELECT id FROM passwords WHERE email = ? AND token = ?",
            array($_GET['email'], $_GET['token'])
          );

          if ($query->rowCount() == 0) {
            $error = 'L\'adresse email ou le token de réinitialisation est incorrect. Vérifiez d\'avoir bien cliquer sur le bon lien';
            $success = '';
          }
          elseif (isset($_POST['password']) && isset($_POST['password_conf'])) {
            if ($_POST['password'] == $_POST['password_conf']) {
              $db->request(
                "DELETE FROM passwords WHERE email = ? AND token = ?",
                array($_GET['email'], $_GET['token'])
              );

              $db->request(
                "UPDATE users SET password = ? WHERE email = ?",
                array(getPassword($_GET['email'], $_POST['password']), $_GET['email'])
              );

              $success = 'Votre mot de passe a été réinitialisé ! Vous pouvez à présent vous connecter';
            }
            else
              $error = 'Les mots de passe sont différents !';
          }
        }
        else {
          $error = 'Il est nécessaire d\'avoir une adresse email et un token de réinitialisation';
          $success = '';
        }

        if (isset($error))
          echo '<p class="error">', $error, '</p>';
        elseif (isset($success))
          echo '<p class="success">', $success, '</p>';

        if (!isset($success)) {
          ?>
            <div>
              <input id='email' <?php if (isset($_GET['email'])) echo 'value="', $_GET['email'], '" '; ?>name="email" class="form-control" placeholder="EMAIL" disabled='disabled' type="email" />
              <input name="password" required="required" class="form-control" placeholder="MOT DE PASSE" type="password" />
              <input name="password_conf" required="required" class="form-control" placeholder="MOT DE PASSE" type="password" />
            </div>

            <div style="padding-top: 25px">
              <input class="button form-button" value="REINITIALISER LE MOT DE PASSE" style="border-radius: 5px" type="submit">
            </div>
          <?php
        }
      ?>
    </form>
  </div>
</div>

<script>
  var email = $('#email')

  var goCas = function () {
    if (email.val().indexOf('utc.fr') !== -1 || email.val().indexOf('escom.fr') !== -1) {
      window.location.href = './connect.php?email=' + email.val();
    }
  }

  email.on('input', goCas);

  goCas();
</script>

<?php
  include "ressources/php/footer.php";
?>
