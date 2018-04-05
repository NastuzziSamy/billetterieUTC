<?php
  include "ressources/php/include.php";

  if (!isset($_SESSION['id'])) {
    header('Location: ./connect.php');
    exit;
  }

  if (isset($_POST['toCancel'])) {
    if ($_POST['toCancel'] == 'all') {
      $db->request(
        'UPDATE tickets SET creation_date = 0 WHERE idUser = ? AND status = 0',
        array($_SESSION['id'])
      );
    }
    else {
      foreach ($_POST['toCancel'] as $id => $toCancel) {
        $db->request(
          'UPDATE tickets SET creation_date = 0 WHERE id = ? AND idUser = ? AND status = 0',
          array($id, $_SESSION['id'])
        );
      }
    }

    removeOldTickets();
    generateTransaction();
  }
  else
    removeOldTickets();

  if (isset($_POST['lastname']) && isset($_POST['firstname']) && isset($_POST['birthdate'])) {
    foreach ($_POST['lastname'] as $id => $lastname) {
      $query = $db->request(
        "SELECT tickets.*, types.sellToStudentsOnly, types.sellToContributers, types.minAge, types.maxAge
        FROM tickets, types
        WHERE idUser = ? AND tickets.id = ? AND tickets.idType = types.id AND status = 1",
        array($_SESSION['id'], $id)
      );

      if ($query->rowCount() == 0)
        continue;

      $ticket = $query->fetch();

      if (isset($_SESSION['login']) && ($ticket['sellToStudentsOnly'] || $ticket['sellToContributers']))
        continue;

      $birthdate = $_POST['birthdate'][$id] == '' ? NULL : date('Y-m-d', strtotime(strtotime(str_replace('/', '-', $_POST['birthdate'][$id])) === FALSE ? implode('-', array_reverse(explode('-', str_replace('/', '-', $_POST['birthdate'][$id])))) : str_replace('/', '-', $_POST['birthdate'][$id])));

      if ($birthdate != '') {
        $date = new DateTime('NOW');
        $date->modify('- '.$ticket['minAge'].' years');
        if ($date->format('Y-m-d') < $birthdate)
        continue;

        $date = new DateTime('NOW');
        $date->modify('- '.$ticket['maxAge'].' years');
        if ($date->format('Y-m-d') > $birthdate)
        continue;
      }

      $db->request(
        'UPDATE tickets SET lastname = ?, firstname = ?, birthdate = ?, modification_date = ? WHERE id = ?',
        array($_POST['lastname'][$id] == '' ? NULL : strtoupper($_POST['lastname'][$id]), $_POST['firstname'][$id] == '' ? NULL : $_POST['firstname'][$id], $birthdate, time(), $id)
      );
    }
  }

  if ($_POST != array()) {
    header('Location: ./account.php');
    exit;
  }

  $query = $db->request(
    "SELECT tickets.*, types.name, types.info, types.price, types.nbrInPack, types.minAge, types.maxAge, types.sellToStudentsOnly, types.sellToTremplinOnly, types.sellToContributers, types.priority
    FROM tickets, types
    WHERE idUser = ? AND tickets.idType = types.id AND status = 0
    ORDER BY priority DESC, idType",
    array($_SESSION['id'])
  );

  $ticketsUnpayed = $query->fetchAll();

  $query = $db->request(
    "SELECT tickets.*, types.name, types.info, types.price, types.nbrInPack, types.minAge, types.maxAge, types.sellToStudentsOnly, types.sellToTremplinOnly, types.sellToContributers, types.priority
    FROM tickets, types
    WHERE idUser = ? AND tickets.idType = types.id AND status = 1
    ORDER BY priority DESC, idType",
    array($_SESSION['id'])
  );

  $ticketsPayed = $query->fetchAll();

  function printTickets($tickets, $isToSell = FALSE) {
    $totalPrice = 0;
    $areAllCompleted = TRUE;
    ?>
    <div id="tickets">
      <?php
        for ($i = 0; $i < count($tickets); $i) {
          $nbrInPack = $tickets[$i]['nbrInPack'];
          $totalPrice += $tickets[$i]['price'];

          if (strpos($tickets[$i]['info'], '*'))
            $GLOBALS['seeInfos'] = TRUE;
          ?>
            <div class="ticket" style="width: <?php echo ($nbrInPack > 1 ? (300 * $nbrInPack / 2) : 250); ?>px">
              <form name="form_sell" method="post" action='./account.php' style="text-align:center; max-width: 100%"<?php echo ($isToSell) ? ' onsuBmit="return confirmCancelation()"' : ' onSubmit="return checkModifications('.$tickets[$i]['id'].')"'; ?>>
                <table style="border-collapse: collapse; width: 90%; height: 100%; margin: auto">
                  <tr><td>
                    <h3><?php echo $tickets[$i]['name']; ?></h3>
                  </td></tr>

                  <tr><td>
                    <h4><?php echo $tickets[$i]['info']; ?></h4>
                  </td></tr>

                  <tr><td>
                    <div style="border: 2px SOLID #333; margin: auto; margin-bottom: 10px"></div>
                  </td></tr>

                  <tr><td>
                    <div id="people" class="row">
                      <?php
                        $isCompleted = TRUE;
                        for ($j = 0; $j < $nbrInPack; $j++) {
                          if (!$tickets[$i]['sellToStudentsOnly'] && !$tickets[$i]['sellToContributers'] && ($tickets[$i]['lastname'] == NULL || $tickets[$i]['firstname'] == NULL || $tickets[$i]['birthdate'] == NULL || $tickets[$i]['lastname'] == '' || $tickets[$i]['firstname'] == '' || $tickets[$i]['birthdate'] == ''))
                            $isCompleted = FALSE;

                          ?>
                            <div>
                              <h4><?php echo ($nbrInPack <= 1) ? '' : 'Participant '.($j + 1); ?></h4>
                              <?php
                                if ($isToSell) {
                                  ?>
                                    <h2><?php echo ($tickets[$i]['price'] == 0) ? 'Gratuit' : (floor($tickets[$i]['price'] / $nbrInPack) == ($tickets[$i]['price'] / $nbrInPack) ? ($tickets[$i]['price'] / $nbrInPack) : round($tickets[$i]['price'] / $nbrInPack, 1)).' €'; ?></h2>
                                  <?php
                                }
                                else {
                                  ?>
                                  <input <?php echo (isset($tickets[$i]['lastname'])) ? 'value="'.$tickets[$i]['lastname'].'" original="'.$tickets[$i]['lastname'].'" style="text-transform:uppercase" ' : 'original="" style="border-color:grey; text-transform:uppercase" '; ?>name="lastname[<?php echo $tickets[$i]['id']; ?>]" class="form-control" placeholder="NOM" type="text"<?php echo (isset($_SESSION['login']) && ($tickets[$i]['sellToStudentsOnly'] || $tickets[$i]['sellToContributers'])) ? ' disabled="disabled"' : ''; ?>/>
                                  <input <?php echo (isset($tickets[$i]['firstname'])) ? 'value="'.$tickets[$i]['firstname'].'" original="'.$tickets[$i]['firstname'].'" ' : 'original="" style="border-color:grey"'; ?>name="firstname[<?php echo $tickets[$i]['id']; ?>]" class="form-control" placeholder="PRENOM" type="text"<?php echo (isset($_SESSION['login']) && ($tickets[$i]['sellToStudentsOnly'] || $tickets[$i]['sellToContributers'])) ? ' disabled="disabled"' : ''; ?>/>
                                  <?php
                                  if (isset($_SESSION['login']) && ($tickets[$i]['sellToStudentsOnly'] || $tickets[$i]['sellToContributers'])) {
                                    ?>
                                      <input name="birthdate[<?php echo $tickets[$i]['id']; ?>]" required="required" class="form-control" placeholder="DATE DE NAISSANCE" value="<?php echo ($_SESSION['isAdult']) ? 'Majeur' : 'Actuellement mineur'; ?>" type="text" disabled="disabled"/>
                                    <?php
                                  }
                                  else {
                                    ?>
                                      <input <?php echo (isset($tickets[$i]['birthdate'])) ? 'value="'.$tickets[$i]['birthdate'].'" original="'.$tickets[$i]['birthdate'].'" ' : 'original="" style="border-color:grey"'; ?>name="birthdate[<?php echo $tickets[$i]['id']; ?>]"
                                      max="<?php $date = new DateTime('NOW'); $date->modify('- '.$tickets[$i]['minAge'].' years'); echo $date->format('Y-m-d'); ?>" min="<?php $date = new DateTime('NOW'); $date->modify('- '.$tickets[$i]['maxAge'].' years'); echo $date->format('Y-m-d'); ?>" class="form-control" placeholder="DATE DE NAISSANCE" type="date" />
                                    <?php
                                  }
                                }
                              ?>
                            </div>
                          <?php

                          $i++;
                        }
                      ?>
                    </div>
                  </td></tr>

                  <?php
                    if ($isToSell) {
                      ?>
                        <tr><td>
                          <?php
                            for ($j = $nbrInPack; $j > 0; $j--) {
                              ?>
                                <input type='hidden' name='toCancel[<?php echo $tickets[$i - $j]['id']; ?>]' value='1' />
                              <?php
                            }
                          ?>
                          <input class="button form-button" value="ANNULER" style="border-radius: 5px" type="submit" />
                        </td></tr>
                      <?php
                    }
                    else {
                      if (!isset($_SESSION['login']) || (!$tickets[$i - $nbrInPack]['sellToStudentsOnly'] && !$tickets[$i - $nbrInPack]['sellToContributers'])) {
                        ?>
                          <tr><td>
                            <input class="button form-button" value="VALIDER LES INFOS" style="border-radius: 5px" type="submit"/>
                          </td></tr>
                        <?php
                      }
                      else {
                        ?>
                          <tr><td>
                            Il n'est pas possible de modifier un billet cotisant
                          </td></tr>
                        <?php
                      }

                      if (!$isCompleted)
                        $areAllCompleted = FALSE;
                      else
                        {
                         ?>
                         <tr><td>
                           <br />
                           <a class="button form-button" value="TELECHARGER" href='./pdf.php?id=<?php echo $tickets[$i - $j]['id']; ?>' style="border-radius: 5px" type="button">TELECHARGER</a>
                         </td></tr>
                         <?php
                       }
                    }
                  ?>
                </table>
              </form>
            </div>
          <?php
        }
      ?>
    </div>
    <?php

    return $isToSell ? $totalPrice : $areAllCompleted;
  }

  $countUnpayed = count($ticketsUnpayed);
  $countPayed = count($ticketsPayed);

  if ($countPayed + $countUnpayed == 0) {
    header('Location: ./billetterie.php');
    exit;
  }

  include "ressources/php/header.php";

  if ($countUnpayed > 0) {
    $minToPay = floor(((21 * 60 / $ticketsUnpayed[0]['priority']) + $ticketsUnpayed[0]['creation_date'] - time()) / 60);
    $secToPay = ceil(((21 * 60 / $ticketsUnpayed[0]['priority']) + $ticketsUnpayed[0]['creation_date'] - time()) % 60);
    ?>
      <div id="bandeau_ask">
        <div class="container" style="text-align:center">
          <h3>
            Billet<?php echo ($countUnpayed > 1) ? 's' : ''; ?> à payer -
            <span id='minToPay'><?php echo $minToPay; ?></span> m <span id='secToPay'><?php echo $secToPay; ?></span> s restante<span id='sToPrint'><?php echo ($minToPay > 1) ? 's' : ''; ?></span><br />
          </h3>
          <h4>
            A la fin du compte à rebours, votre réservation est annulée si elle n'a pas été payée.
          </h4>
          <?php $seeInfos = FALSE;
            $total = printTickets($ticketsUnpayed, TRUE);

            if ($seeInfos) {
              ?>
              <h5 style="color:#337ab7">* justificatif requis</h5>
              <?php
            }
          ?>

          <form name="form_cancel" method="post" style="text-align:center; padding-top:0">
            <input type='hidden' name='toCancel' value='all' />
            <input class="button form-button" value="TOUT ANNULER" style="border-radius: 5px" type="submit" />
          </form>

          <form name="form_pay" method="post" action='./pay.php' style="text-align:center">
            <div style="padding-top: 20px">
              <?php if (in_array($ticketsUnpayed[0]['idType'], array(16, 17, 18, 19, 20))) {
                ?>
                  <label for="accept"><input name="accept" id="accept" type='checkbox' required='required' style="margin-right: 5px" />En cochant cette case, j'ai lu et j'accepte les <a href=./ressources/pdf/conditions.pdf>conditions générales de vente</a> et la <a href=./ressources/pdf/permanenciers.pdf>charte des permanenciers</a></label><br />
                <?php
              }
              else if ($ticketsUnpayed[0]['priority'] == 2) {
                ?>
                  <label for="accept"><input name="accept" id="accept" type='checkbox' required='required' style="margin-right: 5px" />En cochant cette case, j'ai lu et j'accepte les <a href=./ressources/pdf/conditions.pdf>conditions générales de vente</a></label><br />
                <?php
              }
              else {
                ?>
                  <a href=./ressources/pdf/conditions.pdf>Rappel des conditions générales de vente</a><br />
                <?php
              } ?>
              <input style="margin-top: 5px" class="button form-button" value="PAYER MAINTENANT<?php echo ($total == 0) ? '' : ' '.$total.' €'?>" style="border-radius: 5px" type="submit" />
            </div>
          </form>
        </div>
      </div>
    <?php
  }


  if ($countPayed > 0) {
    ?>
      <div id="bandeau_sell">
        <div class="container" style="text-align:center">
          <h3>
            Billet<?php echo ($countPayed > 1) ? 's' : ''; ?> payé<?php echo ($countPayed > 1) ? 's' : ''; ?>
          </h3>
          <?php $seeInfos = FALSE;
            $areAllCompleted = printTickets($ticketsPayed);

            if ($seeInfos) {
              ?>
              <h5 style="color:#337ab7">* justificatif requis</h5>
              <?php
            }
          ?>
        </div>

        <form name="form_pay" method="post" action='./pay.php' style="text-align:center">
          <div style="padding-top: 40px">
            <?php
              if ($areAllCompleted) {
                ?>
                  <input class="button form-button" value="TELECHARGER TOUS MES BILLETS" onClick='window.location.href="./pdf.php?id=-1"' style="border-radius: 5px" type="button" /><br />
                <?php
              }
              else  {
                ?>
                  Il est nécessaire de compléter les informations de chaque billet pour tous les télécharger
                  <br />
                <?php
              }
            ?>
          </div>
        </form>
      </div>
    <?php
  }
?>

<script>
  confirmCancelation = function () {
    return confirm("Voulez-vous vraiment annuler la réservation de ce billet ?");
  };

  checkModifications = function (id) {
    return true;
  };

  min = $('#minToPay');
  sec = $('#secToPay');
  s = $('#sToPrint');

  if (min.length !== 0) {
    setInterval(function () {
      if (min.text() <= 1)
        s.text('');

      if (min.text() <= 0 && sec.text() <= 0)
        window.location.href = './account.php';
      else if (sec.text() <= 0) {
        sec.text(59);
        min.text(min.text() - 1);
      }
      else
        sec.text(sec.text() - 1);
    }, 1000);
  }
</script>

<?php
  include "ressources/php/footer.php";
?>
