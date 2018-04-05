<?php
  include "ressources/php/include.php";

  if (!isset($_SESSION['id'])) {
    header('Location: ./connect.php');
    exit;
  }

  if (isset($_POST['consos']) && is_numeric($_POST['consos']) && 0 < $_POST['consos'] && $_POST['consos'] <= 100) {
    include_once "ressources/php/class/payutc.php";
    $payutc = new PAYUTC();

    $items = array(array(12035, intval($_POST['consos'])));
    $transaction = $payutc->createTransaction($items, $_SESSION['email'], 'https://'.$_SERVER['HTTP_HOST'].'/lightupcity/pay.php?consos=1');

    $GLOBALS['db']->request(
      "INSERT INTO transactions VALUES(NULL, ?, ?, ?, 'W', ?, NULL)",
      array($_SESSION['id'], $transaction['tra_id'], json_encode($items), time())
    );

    $query = $GLOBALS['db']->request(
      "SELECT id FROM transactions WHERE idUser = ? AND idTransaction = ?",
      array($_SESSION['id'], $transaction['tra_id'])
    );

    $data = $query->fetch();

    $_SESSION['consos'] = array(
      'idTransaction' => $data['id'],
      'money' => $_POST['consos']
    );

    header('Location: pay.php?consos=1');
    exit;
  }

  $query = $db->request(
    "SELECT SUM(money) AS total FROM consos WHERE idUser = ?",
    array($_SESSION['id'])
  );

  $data = $query->fetch();
  $money = $data['total'];

  if (empty($money))
    $money = 0;

  if (isset($_POST['tickets']) && is_array($_POST['tickets'])) {
    $sum = 0;

    foreach ($_POST['tickets'] as $val)
      $sum += $val;

    if ($val <= $money) {
      foreach ($_POST['tickets'] as $id => $val) {
        $db->request(
          "UPDATE tickets SET money = ? WHERE idUser = ? AND id = ?",
          array($val, $_SESSION['id'], $id)
        );
      }
    }
  }

  $query = $db->request(
    "SELECT tickets.*, types.name, types.info, types.price, types.nbrInPack, types.minAge, types.maxAge, types.sellToStudentsOnly, types.sellToTremplinOnly, types.sellToContributers, types.priority
    FROM tickets, types
    WHERE idUser = ? AND tickets.idType = types.id AND status = 1
    ORDER BY priority DESC, idType",
    array($_SESSION['id'])
  );

  $ticketsPayed = $query->fetchAll();
  $countPayed = count($ticketsPayed);

  if ($ticketsPayed == 0) {
    header('Location: ./account.php');
    exit;
  }

  include "ressources/php/header.php";
?>
  <div id="bandeau_ask" style="background-image: none;">
    <div class="container" style="text-align:center">
      <h3>
        Mes consos
      </h3>
      <h4 style="color: white">
        Vous pouvez acheter vos consos en avance, afin d'éviter de faire la queue lors du rechargement sur place.<br />
        A l'entrée de l'évènement, une carte (à 1€) vous sera fournie pour payer vos consommations.<br />
        Concernant les packs, une seule carte sera donnée au responsable du groupe/packé.<br />
      </h4>
      <img src="ressources/img/paiement.png" alt="paiement" style="width: 90%; max-width: 1000px" />
      <br />
      <br />
      Attention, le crédit restant sur votre carte après l'événement ne sera pas remboursable.<br/>
      Vous pouvez retrouver les prix des boissons et de la nourriture sur la page facebook Compiègne en Lumière dans publication.
      <form method="post" name="form_pay" style="text-align:center">
        <select name="consos" class="ticket">
          <?php
            for ($i = 1; $i <= 100; $i++) {
              ?>
                <option value="<?= $i ?>"><?= $i ?>€</option>
              <?php
            }
          ?>
        </select>
        <p>Il n'est plus possible de rechargera</p>
        <p>Vous avez au total <?= $money ?>€ en conso d'acheté(s) à distribuer</p>
      </form>
    </div>
  </div>

  <div id="bandeau_sell">
    <form method="post" name="form_pay" onSubmit="return checkConsos(<?= $money ?>)" style="text-align:center">
      <div class="container" style="text-align:center">
        <h3>
          Consos assignées aux billets
        </h3>

          <?php
            $nbr = array();
            for ($i = 0; $i < count($ticketsPayed); $i++) {
              array_push($nbr, $ticketsPayed[$i]['money']);
              ?>
                <div class="ticket" style="width: 100%; max-width: 1000px; margin-left: auto; margin-right: auto;">
                  <table style="border-collapse: collapse; text-align: left; width: 90%; height: 100%; margin: auto">
                    <tr>
                      <td style="width: 350px;">
                        <h3><?= $ticketsPayed[$i]['name']; ?></h3>
                      </td>
                      <td>
                        <h4><?= $ticketsPayed[$i]['firstname']; ?> <?= $ticketsPayed[$i]['lastname']; ?><?= ($ticketsPayed[$i]['nbrInPack'] > 1) ? " (Responsable)" : "" ?></h4>
                      </td>
                      <td style="width: auto; text-align: right">
                        <select name="tickets[<?= $ticketsPayed[$i]['id'] ?>]">
                          <?php
                            for ($j = 0; $j <= $money; $j++) {
                              ?><option><?= $j; ?></option><?php
                            }
                          ?>
                        </select> € de consos
                      </td>
                    </tr>
                  </table>
                </div>
              <?php

              if ($ticketsPayed[$i]['nbrInPack'] > 1)
                $i += $ticketsPayed[$i]['nbrInPack'] - 1;
            }
          ?>
        </div>
      <div style="padding-top: 40px">
        <p>Vous avez au total <?= $money ?>€ en conso d'acheté(s) à distribuer</p>
        <input class="button form-button" value="APPLIQUER" style="border-radius: 5px" type="submit" />
      </div>
    </form>
  </div>

  <script>
    checkConsos = function (money) {
      sum = 0;

      $('.ticket select').each(function(i, obj) {
          if ($(obj).length == 0 || $(obj).val() == 0)
            return;

          sum += parseInt($(obj).val());
      });

      if (sum > money) {
        alert('Attention ! Vous ne pouvez pas distribuer plus d\'argent que vous n\'en possédez.\nVous en avez: ' + money + '€\nVous avez distribué: ' + sum + '€');
        return false;
      }
      else {
        return true;
      }
    };

    $('.ticket select').each(function(i, obj) {
      $(obj).val(<?= json_encode($nbr) ?>[i]);
    });
  </script>
<?php
  include "ressources/php/footer.php";
?>
