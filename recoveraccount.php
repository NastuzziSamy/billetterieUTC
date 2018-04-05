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
    <form name="form_recover" method="post" action='./recoveraccount.php' style="text-align:center">
      <h3>
        Nouvelle demande
      </h3>
      <?php
        if (isset($_POST['email'])) {
          $query = $db->request(
            "SELECT login FROM users WHERE email = ?",
            array($_POST['email'])
          );

          if ($query->rowCount() == 0)
            $error = 'Aucune adresse email ne correspond à celle que vous avez renseignée';
          else {
            $data = $query->fetch();

            $query = $db->request(
              "SELECT creation_date FROM emails WHERE email = ?",
              array($_POST['email'])
            );

            $token = sha1('e(tgthf5dERFrge5g)'.$_POST['email'].time());

            if ($data['login'] != NULL)
              $error = 'Il n\'est pas possible de réinitialiser le mot de passe du compte d\'un étudiant UTC/ESCOM <br /> (petit filou va)';
            elseif ($query->rowCount() == 0) {
              $query = $db->request(
                "SELECT creation_date FROM passwords WHERE email = ?",
                array($_POST['email'])
              );

              if ($query->rowCount() == 0) {
                $url = "https://".$_SERVER['HTTP_HOST']."/lightupcity/reinitpassword.php?email=".$_POST['email']."&token=".$token;

                $db->request(
                  "INSERT INTO passwords VALUES(NULL, ?, ?, ?)",
                  array($_POST['email'], $token, time())
                );

                mail($_POST['email'], 'Réinitialisation du mot de passe de votre compte LightUpCity',
"Bonjour,<br />
<br />
Vous pouvez réinitialiser votre mot de passe en cliquant ici: <a href='".$url."'>".$url."</a><br />
<br />
A bientôt,<br />
L'équipe de LightUpCity.", $headers);

                $success = 'Un email de réinitialisation de mot de passe vous a été envoyé ! Il vous est toujours possible de vous connecter si vous vous souvenez de votre mot de passe';
              }
              else {
                $data = $query->fetch();

                if (time() - $data['creation_date'] < 2 * 60 * 60)
                  $error = 'Il n\'est pas possible de regénérer un email de regénération de mot de passe. Un autre email a déjà été envoyé il y a moins de 2h';
                else {
                  $url = "https://".$_SERVER['HTTP_HOST']."/lightupcity/reinitpassword.php?email=".$_POST['email']."&token=".$token;

                  $db->request(
                    "UPDATE passwords SET token = ?, creation_date = ? WHERE email = ?",
                    array($token, time(), $_POST['email'])
                  );

                  mail($_POST['email'], 'Réinitialisation du mot de passe de votre compte LightUpCity',
"Bonjour,<br />
<br />
Voici le nouvel email de réinitialisation de mot de passe que vous avez demandé.<br />
Vous pouvez réinitialiser votre mot de passe en cliquant ici: <a href='".$url."'>".$url."</a><br />
<br />
A bientôt,<br />
L'équipe de LightUpCity.", $headers);

                  $success = 'Un nouvel email de réinitialisation de mot de passe vous a été envoyé ! Il vous est toujours possible de vous connecter si vous vous souvenez de votre mot de passe';
                }
              }
            }
            else {
              $data = $query->fetch();

              if (time() - $data['creation_date'] < 2 * 60 * 60)
                $error = 'Il n\'est pas possible de regénérer un email de confirmation. Un autre email a déjà été envoyé il y a moins de 2h';
              else {
                $url = "https://".$_SERVER['HTTP_HOST']."/lightupcity/connect.php?email=".$_POST['email']."&token=".$token;

                $db->request(
                  "UPDATE emails SET token = ?, creation_date = ?WHERE email = ?",
                  array($token, time(), $_POST['email'])
                );

                mail($_POST['email'], 'Confirmation de création de compte: LightUpCity',
"Bonjour,<br />
<br />
Voici le nouvel email de confirmation que vous avez demandé.<br />
Veuillez confirmer votre inscription en cliquant ici: <a href='".$url."'>".$url."</a><br />
<br />
A bientôt,<br />
L'équipe de LightUpCity.", $headers);

                $success = 'Un nouvel email de confirmation vous a été envoyé ! Il vous faut à présent confirmer cette inscription en cliquant sur le lien qui vous a été envoyé par le dernier email (qui peut se retrouver dans votre boite spam)';
              }
            }
          }
        }

        if (isset($error))
          echo '<p class="error">', $error, '</p>';
        elseif (isset($success))
          echo '<p class="success">', $success, '</p>';
        else
          echo '<p>Saisissez votre email. Nous vous enverrons un nouveau lien de confirmation ou une demande de réinitialisation de mot de passe.</p>';

        if (!isset($success)) {
          ?>
            <div>
              <input id='email' name="email" required="required" class="form-control" placeholder="EMAIL" type="email" />
            </div>

            <div style="padding-top: 25px">
              <input class="button form-button" value="NOUVELLE DEMANDE" style="border-radius: 5px" type="submit">
            </div>

            <div>
              <a href="./#contact">Nous contacter</a>
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
