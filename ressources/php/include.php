<?php
  ini_set('display_errors', 1);  ini_set('display_startup_errors', 1);  error_reporting(E_ALL);
  date_default_timezone_set('Europe/Paris');

  include "ressources/php/class/db.php";
  $db = new db();

  $headers  = 'MIME-Version: 1.0'."\r\n";
  $headers .= 'Content-type: text/html; charset=UTF-8'."\r\n";
  $headers .= 'From: lightupcity-billetterie@assos.utc.fr'."\r\n";
  $headers .= 'Reply-To: lightupcity-billetterie@assos.utc.fr'."\r\n";

  session_start();

  function getPassword($email, $password) {
    return sha1('98rg7rt98g7'.$password.md5($email));
  }

  function removeOldTickets() {
    $query = $GLOBALS['db']->request(
      "SELECT id, idUser, idTransaction FROM transactions WHERE (creation_date + (21 * 60)) < ? AND status = 'W'",
      array(time())
    );

    $transactions = $query->fetchAll();

    foreach ($transactions as $transaction) {
      include_once "ressources/php/class/payutc.php";
      $payutc = new PAYUTC();

      $info = $payutc->getTransactionInfo($transaction['idTransaction']);

      if ($info['status'] == 'V') {
        $query = $GLOBALS['db']->request(
          "SELECT id FROM tickets WHERE idTransaction = ?",
          array($transaction['id'])
        );

        if ($query->rowCount() == 0) {
          $query = $GLOBALS['db']->request(
            "SELECT * FROM users WHERE id = ?",
            array($transaction['idUser'])
          );

          $user = $query->fetch();

          mail('lightupcity@assos.utc.fr', 'Billet possiblement payé mais non comptabilisé !',
"Coucou,<br />
<br />
Il se peut qu'une personne ait payé sans avoir reçu ses billets. Pour le coup, voici quelques infos:<br />".
json_encode($transaction)."<br />
<br />
Infos de Nemopay:<br />".
json_encode($info)."<br />
<br />
Infos de la personne:<br />".
json_encode($user)."<br />
<br />
Le bot de Samy", $GLOBALS['headers']);

          mail('samy.nastuzzi@etu.utc.fr', 'Billet possiblement payé mais non comptabilisé !',
"Coucou,<br />
<br />
Il se peut qu'une personne ait payé sans avoir reçu ses billets. Pour le coup, voici quelques infos:<br />".
json_encode($transaction)."<br />
<br />
Infos de Nemopay:<br />".
json_encode($info)."<br />
<br />
Infos de la personne:<br />".
json_encode($user)."<br />
<br />
Le bot de Samy", $GLOBALS['headers']);

          $GLOBALS['db']->request(
            'UPDATE transactions SET status = "E", modification_date = ? WHERE id = ?',
            array(time(), $transaction['id'])
          );
        }
        else {
          $GLOBALS['db']->request(
            "UPDATE transactions SET status = 'V', modification_date = ? WHERE id = ?",
            array(time(), $transaction['id'])
          );

          $GLOBALS['db']->request(
            "UPDATE tickets SET status = 1, modification_date = ? WHERE idTransaction = ?",
            array(time(), $transaction['id'])
          );

          $url = 'https://'.$_SERVER['HTTP_HOST']."/lightupcity/account.php";

          mail($_SESSION['email'], 'Confirmation de réservation LightUpCity',
"Bonjour,<br />
<br />
Nous vous confirmons l\'achat de places que vous venez d\'effectuer.
Vous pouvez à tout moment consulter et modifier vos billets ici: <a href='".$url."'>".$url."</a><br />
<br />
A bientôt,<br />
L'équipe de LightUpCity.", $headers);
        }
      }
      else {
        $GLOBALS['db']->request(
          'UPDATE transactions SET status = "A", modification_date = ? WHERE id = ?',
          array(time(), $transaction['id'])
        );
      }
    }

    $query = $GLOBALS['db']->request(
      "SELECT id, priority, nbrInPack FROM types ORDER BY priority DESC",
      array()
    );

    $types = $query->fetchAll();

    foreach ($types as $type) {
      $query = $GLOBALS['db']->request(
        'DELETE FROM tickets WHERE status = 0 AND idType = ? AND (creation_date + ((21 DIV ?) * 60)) < ?',
        array($type['id'], $type['priority'], time())
      );

      $nbr = $query->rowCount();
      if ($nbr != 0) {
        $GLOBALS['db']->request(
          'UPDATE types SET nbrToSell = nbrToSell + ? WHERE id = ?',
          array($nbr / $type['nbrInPack'], $type['id'])
        );
      }
    }
  }

  function generateTransaction() {
    $query = $GLOBALS['db']->request(
      "SELECT tickets.*, types.idNemopay, types.nbrInPack
      FROM tickets, types
      WHERE idUser = ? AND status = 0 AND tickets.idType = types.id",
      array($_SESSION['id'])
    );

    if ($query->rowCount() != 0) {
      $tickets = $query->fetchAll();

      $ids = array();
      foreach ($tickets as $ticket) {
        if (!isset($ids[$ticket['idNemopay']]))
          $ids[$ticket['idNemopay']] = 0;

        $ids[$ticket['idNemopay']] += 1 / $ticket['nbrInPack'];
      }

      $items = array();
      foreach ($ids as $id => $nbr) {
        $array = array($id, $nbr);
        array_push($items, $array);
      }

      include_once "ressources/php/class/payutc.php";
      $payutc = new PAYUTC();

      $transaction = $payutc->createTransaction($items, $_SESSION['email'], 'https://'.$_SERVER['HTTP_HOST'].'/lightupcity/pay.php');

      $GLOBALS['db']->request(
        "INSERT INTO transactions VALUES(NULL, ?, ?, ?, 'W', ?, NULL)",
        array($_SESSION['id'], $transaction['tra_id'], json_encode($items), time())
      );

      $query = $GLOBALS['db']->request(
        "SELECT id FROM transactions WHERE idUser = ? AND idTransaction = ?",
        array($_SESSION['id'], $transaction['tra_id'])
      );

      $data = $query->fetch();

      $GLOBALS['db']->request(
        "UPDATE tickets SET idTransaction = ?, creation_date = ? WHERE idUser = ? AND status = 0",
        array($data['id'], time(), $_SESSION['id'])
      );
    }
  }
?>
