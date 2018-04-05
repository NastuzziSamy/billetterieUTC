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
    <form name="form_inscription" method="post" action='./registerTremplin.php' style="text-align:center">
      <h3>
        Inscription pour un compte Tremplin
      </h3>
      <?php
        if (isset($_POST['email']) && isset($_POST['password']) && isset($_POST['password_conf'])) {
          if (strpos($_POST['email'], 'utc.fr') || strpos($_POST['email'], 'escom.fr'))
            $error = 'Il n\'est pas autorisé de créer un compte avec une adresse utc.fr ou escom.fr';
          elseif ($_POST['password'] == $_POST['password_conf']) {
            $query = $db->request(
              "SELECT id FROM users WHERE email = ?",
              array($_POST['email'])
            );

            if ($query->rowCount() == 0) {
              $query = $db->request(
                "SELECT * FROM tremplins WHERE email = ?",
                array($_POST['email'])
              );

              if ($query->rowCount() != 0) {
                $data = $query->fetch();

                $db->request(
                  "INSERT INTO users VALUES(NULL, NULL, ?, ?, ?, ?, ?, NULL, NULL)",
                  array(strtoupper($data['lastname']), $data['firstname'], $data['email'], getPassword($data['email'], $_POST['password']), $data['birthdate'])
                );

                $token = sha1('e(tgthf5dERFrge5g)'.$data['email'].time());
                $url = 'https://'.$_SERVER['HTTP_HOST']."/lightupcity/connect.php?email=".$data['email']."&token=".$token;

                $db->request(
                  "INSERT INTO emails VALUES(NULL, ?, ?, ?)",
                  array($data['email'], $token, time())
                );

                mail($data['email'], 'Confirmation de création de compte Tremplin: LightUpCity',
"Bonjour ".$data['firstname'].' '.strtoupper($data['lastname']).",<br />
<br />
Veuillez confirmer votre inscription en cliquant ici: <a href='".$url."'>".$url."</a><br />
<br />
Votre compte est rassocié à votre cotisation Tremplin, vous aurez donc aux places spécialement cotisantes Tremplin.<br/>
<br />
A bientôt,<br />
L'équipe de LightUpCity.", $headers);

                $success = 'Votre compte Tremplin a été créé avec succès ! Il vous faut à présent confirmer cette inscription en cliquant sur le lien qui vous a été envoyé par email';
              }
              else
                $error = 'L\'adresse email ne correspond à aucun compte cotisant Tremplin ! Vous pouvez vous inscrire normalement ici <a href="./register.php">ici</a>';
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
            <div>
              <input id='email' <?php if (isset($_POST['email'])) echo 'value="', $_POST['email'], '" '; ?>name="email" required="required" class="form-control" placeholder="EMAIL" type="email" />
              <input name="password" pattern=".{3,}" required="required" class="form-control" placeholder="MOT DE PASSE" type="password" />
              <input name="password_conf" pattern=".{3,}" required="required" class="form-control" placeholder="CONFIRMER LE MOT DE PASSE" type="password" />
            </div>

            <div style="padding-top: 25px">
              <input id='cas-button' class="button form-button" onClick="window.location.href='https://cas.utc.fr/cas/login?service=https://<?php echo $_SERVER['HTTP_HOST'], '/lightupcity/connect.php'; ?>'" value="JE SUIS ETUDIANT(E) UTC/ESCOM" style="border-radius: 5px; margin-right: 10px" type="button"><br />
              <input class="button form-button" onClick="window.location.href='/lightupcity/register.php';" value="JE NE SUIS PAS COTISANT TREMPLIN" style="border-radius: 5px; margin-right: 10px" type="button"><br />
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
