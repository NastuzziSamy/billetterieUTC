<?php
  include "ressources/php/include.php";

  if (isset($_SESSION['id'])) {
    header('Location: ./account.php');
    exit;
  }

  include "ressources/php/header.php";
?>

<div id="bandeau_ask">
  <div class="container">
    <form name="form_inscription" method="post" action='./register.php' style="text-align:center">
      <h3>
        Inscription
      </h3>
      <?php
        if (isset($_POST['email']) && isset($_POST['password']) && isset($_POST['password_conf']) && isset($_POST['lastname']) && isset($_POST['firstname']) && isset($_POST['birthdate'])) {
          if (strpos($_POST['email'], 'utc.fr') || strpos($_POST['email'], 'escom.fr'))
            $error = 'Il n\'est pas autorisé de créer un compte avec une adresse utc.fr ou escom.fr';
          elseif ($_POST['password'] == $_POST['password_conf']) {
            $query = $db->request(
              "SELECT id FROM users WHERE email = ?",
              array($_POST['email'])
            );

            if ($query->rowCount() == 0) {
              $db->request(
                "INSERT INTO users VALUES(NULL, NULL, ?, ?, ?, ?, ?, NULL, NULL)",
                array(strtoupper($_POST['lastname']), $_POST['firstname'], $_POST['email'], getPassword($_POST['email'], $_POST['password']), $_POST['birthdate'])
              );

              $token = sha1('e(tgthf5dERFrge5g)'.$_POST['email'].time());
              $url = 'https://'.$_SERVER['HTTP_HOST']."/lightupcity/connect.php?email=".$_POST['email']."&token=".$token;

              if (strpos($_POST['email'], 'hotmail.com') === FALSE && strpos($_POST['email'], 'hotmail.fr') === FALSE && strpos($_POST['email'], 'live.com') === FALSE && strpos($_POST['email'], 'outlook.com') === FALSE)
                $db->request(
                  "INSERT INTO emails VALUES(NULL, ?, ?, ?)",
                  array($_POST['email'], $token, time())
                );

              mail($_POST['email'], 'Confirmation de création de compte: LightUpCity (Compiègne en Lumière)',
"Bonjour ".$_POST['firstname'].' '.strtoupper($_POST['lastname']).",<br />
<br />
Veuillez confirmer votre inscription en cliquant ici: <a href='".$url."'>".$url."</a><br />
<br />
A bientôt,<br />
L'équipe de LightUpCity.", $headers);

              $success = 'Votre compte a été créé avec succès ! Il vous faut à présent confirmer cette inscription en cliquant sur le lien qui vous a été envoyé par email (qui peut se retrouver dans votre boite de spam)';
            }
            else {
              $query = $db->request(
                "SELECT * FROM emails WHERE email = ?",
                array($_POST['email'])
              );

              if ($query->rowCount() == 0)
                $error = 'Votre adresse email n\'a pas été confirmé, vous pouvez demander un nouvel envoi <a href="./recoveraccount.php?email='.$_POST['email'].'">ici</a>';
              else
                $error = 'L\'adresse email est déjà utilisée ! Vous pouvez demaner à réinitialiser le mot de passe <a href="./recoveraccount.php?email='.$_POST['email'].'">ici</a>';
            }
          }
          else
            $error = 'Les mots de passe sont différents !';
        }

        if (isset($error))
          echo '<p class="error">', $error, '</p>';
        elseif (isset($success))
          echo '<p class="success">', $success, '</p>';
        else
          echo '<p>Pour participer à l\'évènement, inscrivez-vous !</p>';

        if (!isset($success)) {
          ?>
            <p>Nous vous déconseillons fortement d'utiliser une adresse hotmail.fr, hotmail.com, live.com, outlook.com (vous ne recevrez aucun de nos mails)</p>
            <div>
              <input id='email' <?php if (isset($_POST['email'])) echo 'value="', $_POST['email'], '" '; ?>name="email" required="required" class="form-control" placeholder="EMAIL" type="email" />
              <input name="password" pattern=".{3,}" required="required" class="form-control" placeholder="MOT DE PASSE" type="password" />
              <input name="password_conf" pattern=".{3,}" required="required" class="form-control" placeholder="CONFIRMER LE MOT DE PASSE" type="password" />
            </div>
            <br />
            <div>
              <input <?php if (isset($_POST['lastname'])) echo 'value="', $_POST['lastname'], '" '; ?>name="lastname" required="required" class="form-control" placeholder="NOM" style="text-transform:uppercase" type="text" />
              <input <?php if (isset($_POST['firstname'])) echo 'value="', $_POST['firstname'], '" '; ?>name="firstname" required="required" class="form-control" placeholder="PRENOM" type="text" />
              <input <?php if (isset($_POST['birthdate'])) echo 'value="', $_POST['birthdate'], '" '; ?>name="birthdate" min='<?php $date = new DateTime('NOW'); $date->modify('-120 years'); echo $date->format('Y-m-d'); ?>' max='<?php $date = new DateTime('NOW'); $date->modify('-14 years'); echo $date->format('Y-m-d'); ?>' required="required" class="form-control" placeholder="DATE DE NAISSANCE" type="date" />
            </div>

            <div style="padding-top: 25px">
              <input id='cas-button' class="button form-button" onClick="window.location.href='https://cas.utc.fr/cas/login?service=https://<?php echo $_SERVER['HTTP_HOST'], '/lightupcity/connect.php'; ?>'" value="JE SUIS ETUDIANT(E) UTC/ESCOM" style="border-radius: 5px; margin-right: 10px" type="button"><br />
              <input class="button form-button" onClick="window.location.href='/lightupcity/registerTremplin.php';" value="JE SUIS COTISANT TREMPLIN" style="border-radius: 5px; margin-right: 10px" type="button"><br />
              <input class="button form-button" value="S'INSCRIRE" style="border-radius: 5px" type="submit">
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
