<?php
  include "ressources/php/include.php";

  if (!isset($_SESSION['id'])) {
    header('Location: ./connect.php');
    exit;
  }

  removeOldTickets();

  $query = $db->request(
    "SELECT tickets.*, types.idNemopay
    FROM tickets, types
    WHERE idUser = ? AND status = 0 AND tickets.idType = types.id",
    array($_SESSION['id'])
  );

  if ($query->rowCount() == 0 || isset($_GET['consos'])) {
    if (isset($_SESSION['consos']) && isset($_SESSION['consos']['idTransaction'])) {
      $query = $GLOBALS['db']->request(
        "SELECT * FROM transactions WHERE id = ? AND status = 'W'",
        array($_SESSION['consos']['idTransaction'])
      );

      if ($query->rowCount() == 0) {
        header('Location: ./connect.php');
        exit;
      }

      $data = $query->fetch();

      include "ressources/php/class/payutc.php";
      $payutc = new PAYUTC();
      $info = $payutc->getTransactionInfo($data['idTransaction']);

      if ($info['status'] == 'W') {
        header('Location: '.$payutc->getTransactionUrl().$data['idTransaction']);
        exit;
      }
      elseif ($info['status'] == 'V') {
        $GLOBALS['db']->request(
          "UPDATE transactions SET status = 'V', modification_date = ? WHERE id = ?",
          array(time(), $data['id'])
        );

        $GLOBALS['db']->request(
          "INSERT INTO consos VALUES(NULL, ?, ?, ?, ?, NULL)",
          array($_SESSION['id'], $data['id'], $_SESSION['consos']['money'], time())
        );

        $url = 'https://'.$_SERVER['HTTP_HOST']."/lightupcity/consos.php";

        mail($_SESSION['email'], 'Confirmation d\'achat de consos: LightUpCity',
"Bonjour,<br />
<br />
Nous vous confirmons l'achat de ".$_SESSION['consos']['money']."€ de consos que vous venez d'effectuer.
Vous pouvez à tout moment consulter, et attribuer (une fois les informations nécessaires données) vos consos ici: <a href='".$url."'>".$url."</a><br />
<br />
A bientôt,<br />
L'équipe de LightUpCity.", $headers);

        unset($_SESSION['consos']);
        header('Location: ./consos.php');
      }
      else {
        $query = $GLOBALS['db']->request(
          "UPDATE transactions SET status = 'A', modification_date = ? WHERE id = ?",
          array(time(), $data['id'])
        );

        $query = $GLOBALS['db']->request(
          "UPDATE tickets SET creation_date = 0, status = 0 WHERE idTransaction = ?",
          array($data['id'])
        );
      }
    }
    else
      header('Location: ./connect.php');

    exit;
  }

  $data = $query->fetch();

  if ($data['idTransaction'] == NULL) {
    generateTransaction();

    header('Location: ./pay.php');
    exit;
  }

  $query = $GLOBALS['db']->request(
    "SELECT * FROM transactions WHERE id = ? AND status = 'W'",
    array($data['idTransaction'])
  );

  if ($query->rowCount() == 0) {
    header('Location: ./connect.php');
    exit;
  }

  $data = $query->fetch();

  include "ressources/php/class/payutc.php";
  $payutc = new PAYUTC();
  $info = $payutc->getTransactionInfo($data['idTransaction']);

  if ($info['status'] == 'W') {
    header('Location: '.$payutc->getTransactionUrl().$data['idTransaction']);
    exit;
  }
  elseif ($info['status'] == 'V') {
    $GLOBALS['db']->request(
      "UPDATE transactions SET status = 'V', modification_date = ? WHERE id = ?",
      array(time(), $data['id'])
    );

    $GLOBALS['db']->request(
      "UPDATE tickets SET status = 1, modification_date = ? WHERE idTransaction = ?",
      array(time(), $data['id'])
    );

    $url = 'https://'.$_SERVER['HTTP_HOST']."/lightupcity/account.php";

    mail($_SESSION['email'], 'Confirmation d\'achat de votre/vos billet(s): LightUpCity',
"Bonjour,<br />
<br />
Nous vous confirmons l'achat de places que vous venez d'effectuer.
Vous pouvez à tout moment consulter, modifier et télécharger (une fois les informations nécessaires données) vos billets ici: <a href='".$url."'>".$url."</a><br />
<br />
A bientôt,<br />
L'équipe de LightUpCity.", $headers);
  }
  else {
    $query = $GLOBALS['db']->request(
      "UPDATE transactions SET status = 'A', modification_date = ? WHERE id = ?",
      array(time(), $data['id'])
    );

    $query = $GLOBALS['db']->request(
      "UPDATE tickets SET creation_date = 0, status = 0 WHERE idTransaction = ?",
      array($data['id'])
    );
  }

  header('Location: ./account.php');
  exit;

?>
