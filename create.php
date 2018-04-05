<?php
  include "ressources/php/include.php";

  if (!isset($_SESSION['login'])) {
    header('Location: ./account.php');
    exit;
  }

  $query = $db->request(
    "SELECT id FROM admins WHERE login = ?",
    array($_SESSION['login'])
  );

  if ($query->rowCount() == 0) {
    header('Location: ./account.php');
    exit;
  }

  removeOldTickets();

  if (isset($_POST['idType'])) {
    include "ressources/php/class/ginger.php";
    $created = array();
    $included = array();
    $errors = array();

    if (isset($_POST['login'])) {
      $logins = explode("\r\n", strtolower($_POST['login']));

      foreach ($logins as $login) {
        try {
          $infoFromGinger = $ginger->getUser($login);
        }
        catch (Exception $e) {
          array_push($errors, $login);
          continue;
        }

        $query = $db->request(
          "SELECT id FROM users WHERE login = ?",
          array($infoFromGinger['login'])
        );

        if ($query->rowCount() == 0) {
          $db->request(
            "INSERT INTO users VALUES(NULL, ?, ?, ?, ?, NULL, NULL, ?, NULL)",
            array($infoFromGinger['login'], $infoFromGinger['nom'], $infoFromGinger['prenom'], $infoFromGinger['mail'], $infoFromGinger['is_adulte'])
          );

          $query = $db->request(
            "SELECT id FROM users WHERE login = ?",
            array($infoFromGinger['login'])
          );

          array_push($created, $infoFromGinger['login']);
        }
        else
          array_push($included, $infoFromGinger['login']);

        $data = $query->fetch();

        $days = (isset($_POST['dayNbr']) && $_POST['dayNbr'] > 0) ? $_POST['dayNbr'] : 2;

        $db->request(
          "INSERT INTO tickets VALUES(NULL, ?, ?, NULL, ?, ?, NULL, 0, ?, ?, ?)",
          array($data['id'], $_POST['idType'], $infoFromGinger['nom'], $infoFromGinger['prenom'], (isset($_POST['isPaid']) && $_POST['isPaid'] == 'on' ? 1 : 0), (isset($_POST['isPaid']) && $_POST['isPaid'] == 'on' ? time() : strtotime('+'.$days.' hours', time())), (isset($_POST['isPaid']) && $_POST['isPaid'] == 'on' ? time() : NULL))
        );
      }
    }
    elseif (isset($_POST['lastname']) && isset($_POST['firstname']) && isset($_POST['email']) && isset($_POST['birthdate'])) {
      $lastnames = explode("\r\n", $_POST['lastname']);
      $firstnames = explode("\r\n", $_POST['firstname']);
      $emails = explode("\r\n", strtolower($_POST['email']));
      $birthdates = explode("\r\n", $_POST['birthdate']);

      if (count($lastnames) != count($firstnames) || count($lastnames) != count($emails) || count($lastnames) != count($birthdates)) {
        echo 'Entrées incorrectes !';
        exit;
      }

      foreach ($emails as $key => $email) {
        $query = $db->request(
          "SELECT id FROM users WHERE email = ?",
          array($email)
        );

        if ($query->rowCount() == 0) {
          $db->request(
            "INSERT INTO users VALUES(NULL, NULL, ?, ?, ?, NULL, ?, NULL, NULL)",
            array(strtoupper($lastnames[$key]), $firstnames[$key], $email, date('Y-m-d', strtotime($birthdates[$key])))
          );

          $token = sha1('e(tgthf5dERFrge5g)'.$email.time());
          $url = "https://".$_SERVER['HTTP_HOST']."/lightupcity/reinitpassword.php?email=".$email."&token=".$token;

          $db->request(
            "INSERT INTO passwords VALUES(NULL, ?, ?, ?)",
            array($email, $token, time())
          );

          mail($email, 'Création automatique de votre compte LightUpCity et réservation de votre billet',
"Bonjour,<br />
<br />
Un compte a été automatiquement créé sous ces informations:<br />
Nom: ".strtoupper($lastnames[$key])."<br />
Prenom: ".$firstnames[$key]."<br />
Email: ".$email."<br />
Date de naissance: ".date('d/m/Y', strtotime($birthdates[$key]))."<br />
<br />
Un ticket vous a été automatiquement réservé".(isset($_POST['isPaid']) && $_POST['isPaid'] == 'on' ? ' et payé' : '').".<br />
<br />
Vous pouvez créer votre mot de passe en cliquant ici: <a href='".$url."'>".$url."</a><br />
<br />
A bientôt,<br />
L'équipe de LightUpCity.", $headers);

          $query = $db->request(
            "SELECT id FROM users WHERE email = ?",
            array($email)
          );

          array_push($created, $email);
        }
        else
          array_push($included, $email);

        $data = $query->fetch();

        $days = (isset($_POST['dayNbr']) && $_POST['dayNbr'] > 0) ? $_POST['dayNbr'] : 2;

        $db->request(
          "INSERT INTO tickets VALUES(NULL, ?, ?, NULL, ?, ?, ?, 0, ?, ?, ?)",
          array($data['id'], $_POST['idType'], strtoupper($lastnames[$key]), $firstnames[$key], date('Y-m-d', strtotime($birthdates[$key])), (isset($_POST['isPaid']) && $_POST['isPaid'] == 'on' ? 1 : 0), (isset($_POST['isPaid']) && $_POST['isPaid'] == 'on' ? time() : strtotime('+'.$days.' hours', time())), (isset($_POST['isPaid']) && $_POST['isPaid'] == 'on' ? time() : NULL))
        );
      }
    }

    if (count($included) + count($created) == 0)
      $error = 'Problème à l\'ajout !<br />';
    else {
      $success = 'Creation de : '.implode(', ', $created).'<br />Ajout de : '.implode(', ', $included);

      $db->request(
        'UPDATE types SET nbrToSell = nbrToSell - ? WHERE id = ?',
        array(count($created) + count($included), $_POST['idType'])
      );
    }

    if (count($errors))
      $error = 'Erreur de : '.implode(', ', $errors).'<br />';
  }
  elseif (isset($_POST['lastname']) && isset($_POST['firstname']) && isset($_POST['email']) && isset($_POST['birthdate'])) {
    include "ressources/php/class/ginger.php";
    $included = array();
    $errors = array();

    $lastnames = explode("\r\n", $_POST['lastname']);
    $firstnames = explode("\r\n", $_POST['firstname']);
    $emails = explode("\r\n", strtolower($_POST['email']));
    $birthdates = explode("\r\n", $_POST['birthdate']);

    if (count($lastnames) != count($firstnames) || count($lastnames) != count($emails) || count($lastnames) != count($birthdates)) {
      echo 'Entrées incorrectes !';
      exit;
    }

    foreach ($emails as $key => $email) {
      $query = $db->request(
        "SELECT id FROM tremplins WHERE email = ?",
        array($email)
      );

      if ($query->rowCount() == 0) {
        $db->request(
          "INSERT INTO tremplins VALUES(NULL, ?, ?, ?, ?)",
          array(strtoupper($lastnames[$key]), $firstnames[$key], $email, date('Y-m-d', strtotime($birthdates[$key])))
        );

        array_push($included, $email);
      }
      else
        array_push($errors, $email);
    }

    if (count($errors))
    $error = 'Erreur de : '.implode(', ', $errors).'<br />';

    if (count($included) == 0)
      $error = 'Problème à l\'ajout !<br />';
    else
      $success = 'Ajout de : '.implode(', ', $included);
  }

  include "ressources/php/header.php";
?>

<div id="bandeau_ask">
  <div class="container" style="text-align: center">
    <div id='tickets'>
      <?php
        if (isset($error))
          echo '<p class="error">', $error, '</p>';
        if (isset($success))
          echo '<p class="success">', $success, '</p>';
      ?>

      <div class="ticket">
        <form name="form_connection" method="post" style="text-align:center">
          <table style="border-collapse: collapse; width: 90%; height: 100%; margin: auto">
            <tr><td>
              <h3>Etuiant(s) à ajouter</h3>
            </td></tr>

            <tr style="height: 40%"><td>
              <textarea name="login" required="required" class="form-control" placeholder="LOGINS ETUS"></textarea>
            </td></tr>

            <tr style="height: 40%"><td>
              <select style="width:90%" name="idType" />
                <?php
                  $query = $db->request(
                    "SELECT * FROM types WHERE nbrInPack = 1",
                    array()
                  );

                  $types = $query->fetchAll();

                  foreach ($types as $type)
                    echo '<option value="'.$type['id'].'">'.$type['name'].' - '.$type['price'].' €</option>';
                ?>
              </select>
            </td></tr>

            <tr style="height: 40%"><td>
              <input name="isPaid" class="form-control" type="checkbox" style="width: auto; height: auto; display: inline" /> Déjà payé
            </td></tr>

            <tr style="height: 40%"><td>
              Paiement en <input name="dayNbr" class="form-control" placeholder="2" style="width: 50px; height: auto; display: inline" /> heure(s)
            </td></tr>

            <tr><td>
              <div style="border: 2px SOLID #333; margin: auto"></div>
            </td></tr>

            <tr style="height: 40%"><td>
            <input class="button form-button" value="CREER UN TICKET" style="border-radius: 5px" type="submit" />
            </td></tr>
          </table>
        </form>
      </div>

      <div class="ticket">
        <form name="form_connection" method="post" style="text-align:center">
          <table style="border-collapse: collapse; width: 90%; height: 100%; margin: auto">
            <tr><td>
              <h3>Extérieur(s) à ajouter</h3>
            </td></tr>

            <tr style="height: 40%"><td>
              <textarea name="lastname" required="required" class="form-control" placeholder="NOMS"></textarea>
            </td></tr>

            <tr style="height: 40%"><td>
              <textarea name="firstname" required="required" class="form-control" placeholder="PRENOMS"></textarea>
            </td></tr>

            <tr style="height: 40%"><td>
              <textarea name="email" required="required" class="form-control" placeholder="EMAILS"></textarea>
            </td></tr>

            <tr style="height: 40%"><td>
              <textarea name="birthdate" required="required" class="form-control" placeholder="DATES DE NAISSANCE"></textarea>
            </td></tr>

            <tr style="height: 40%"><td>
              <select style="width:90%" name="idType" />
                <?php
                  $query = $db->request(
                    "SELECT * FROM types WHERE nbrInPack = 1 AND sellToStudentsOnly = 0",
                    array()
                  );

                  $types = $query->fetchAll();

                  foreach ($types as $type)
                    echo '<option value="'.$type['id'].'">'.$type['name'].' - '.$type['price'].' €</option>';
                ?>
              </select>
            </td></tr>

            <tr style="height: 40%"><td>
              <input name="isPaid" class="form-control" type="checkbox" style="width: auto; height: auto; display: inline" /> Déjà payé
            </td></tr>

            <tr style="height: 40%"><td>
              Paiement en <input name="dayNbr" class="form-control" placeholder="2" style="width: 50px; height: auto; display: inline" /> heure(s)
            </td></tr>

            <tr><td>
              <div style="border: 2px SOLID #333; margin: auto"></div>
            </td></tr>

            <tr style="height: 40%"><td>
            <input class="button form-button" value="CREER UN TICKET" style="border-radius: 5px" type="submit" />
            </td></tr>
          </table>
        </form>
      </div>

      <div class="ticket">
        <form name="form_connection" method="post" style="text-align:center">
          <table style="border-collapse: collapse; width: 90%; height: 100%; margin: auto">
            <tr><td>
              <h3>Tremplin(s) à ajouter</h3>
            </td></tr>

            <tr style="height: 40%"><td>
              <textarea name="lastname" required="required" class="form-control" placeholder="NOMS"></textarea>
            </td></tr>

            <tr style="height: 40%"><td>
              <textarea name="firstname" required="required" class="form-control" placeholder="PRENOMS"></textarea>
            </td></tr>

            <tr style="height: 40%"><td>
              <textarea name="email" required="required" class="form-control" placeholder="EMAILS"></textarea>
            </td></tr>

            <tr style="height: 40%"><td>
              <textarea name="birthdate" required="required" class="form-control" placeholder="DATES DE NAISSANCE"></textarea>
            </td></tr>

            <tr><td>
              <div style="border: 2px SOLID #333; margin: auto"></div>
            </td></tr>

            <tr style="height: 40%"><td>
            <input class="button form-button" value="CREER" style="border-radius: 5px" type="submit" />
            </td></tr>
          </table>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
  include "ressources/php/footer.php";
?>
