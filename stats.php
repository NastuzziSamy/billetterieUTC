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

  $query = $db->request(
    "SELECT * FROM users",
    array()
  );

  $users = $query->fetchAll();

  $query = $db->request(
    "SELECT * FROM types ORDER BY priority DESC",
    array()
  );

  $types = $query->fetchAll();

  include "ressources/php/header.php";
?>

<div id="bandeau_sell">
  <form name="form_connection" method="post" action='./billetterie.php' style="text-align:center">
    <h3>
      Stats
    </h3>

    <div id="tickets">
      <?php
        foreach ($types as $type) {
          $query = $db->request(
            "SELECT * FROM tickets WHERE status = 0 AND idType = ?",
            array($type['id'])
          );

          $reservedTickets = $query->rowCount() / $type['nbrInPack'];

          $query = $db->request(
            "SELECT * FROM tickets WHERE status = 1 AND idType = ?",
            array($type['id'])
          );

          $soldTickets = $query->rowCount() / $type['nbrInPack'];
            ?>
              <div class="ticket">
                <table style="border-collapse: collapse; width: 90%; height: 100%; margin: auto">
                  <tr><td>
                    <h3><?php echo $type['name']; ?></h3>
                  </td></tr>

                  <tr style="height: 40%"><td>
                    <h4><?php echo $soldTickets, ' payée', ($soldTickets > 1 ? 's' : ''); ?></h4>
                  </td></tr>

                  <tr style="height: 40%"><td>
                    <h4><?php echo $reservedTickets; ?> en cours</h4>
                  </td></tr>

                  <tr style="height: 40%"><td>
                    <h4><?php echo $type['nbrToSell'], ' restante', ($type['nbrToSell'] > 1 ? 's' : ''), ($type['nbrTotal'] - $soldTickets - $reservedTickets == $type['nbrToSell'] ? '' : ' (erreur, '.($type['nbrTotal'] - $soldTickets - $reservedTickets - $type['nbrToSell']).' place'.($type['nbrTotal'] - $soldTickets - $reservedTickets - $type['nbrToSell'] > 1 ? 's' : '').' manquante'.($type['nbrTotal'] - $soldTickets - $reservedTickets - $type['nbrToSell'] > 1 ? 's' : '').')'); ?></h4>
                  </td></tr>

                  <tr><td>
                    <div style="border: 2px SOLID #333; margin: auto"></div>
                  </td></tr>

                  <tr style="height: 40%"><td>
                    <h4><?php echo (($soldTickets + $reservedTickets) / count($users)); ?> par utilisateur</h4>
                  </td></tr>
                </table>
              <?php
                if ($type['nbrToSell'] <= 0) {
                  ?>
                  <img src='ressources/img/sold_out.png' class='sold_out'/>
                  <?php
                }
              ?>
            </div>
          <?php
        }

        $query = $db->request(
          "SELECT * FROM tickets WHERE status = 0",
          array()
        );

        $reservedTickets = $query->rowCount();

        $query = $db->request(
          "SELECT * FROM tickets WHERE status = 1",
          array($type['id'])
        );

        $soldTickets = $query->rowCount();
      ?>
        <div class="ticket">
          <table style="border-collapse: collapse; width: 90%; height: 100%; margin: auto">
            <tr><td>
              <h3>Place totale</h3>
            </td></tr>

            <tr style="height: 40%"><td>
              <h4><?php echo $soldTickets, ' payée', ($soldTickets > 1 ? 's' : ''); ?></h4>
            </td></tr>

            <tr style="height: 40%"><td>
              <h4><?php echo $reservedTickets; ?> en cours</h4>
            </td></tr>

            <tr><td>
              <div style="border: 2px SOLID #333; margin: auto"></div>
            </td></tr>

            <tr style="height: 40%"><td>
              <h4><?php echo (($soldTickets + $reservedTickets) / count($users)); ?> par utilisateur</h4>
            </td></tr>
          </table>
        <?php
          if ($type['nbrToSell'] <= 0) {
            ?>
            <img src='ressources/img/sold_out.png' class='sold_out'/>
            <?php
          }
        ?>
      </div>
    </div>
  </form>
</div>

<div id="bandeau_ask">
  <div class='container' style='text-align:center; padding-top: 50px'>
    <h3>
      Transactions
    </h3>

    <div id="tickets">
      <?php
        $query = $db->request(
          "SELECT * FROM transactions WHERE status = 'W'",
          array()
        );

        $w = $query->rowCount();

        $query = $db->request(
          "SELECT * FROM transactions WHERE status = 'A'",
          array()
        );

        $a = $query->rowCount();

        $query = $db->request(
          "SELECT * FROM transactions WHERE status = 'V'",
          array()
        );

        $v = $query->rowCount();

        ?>
        <div class="ticket">
          <table style="border-collapse: collapse; width: 90%; height: 100%; margin: auto">
            <tr><td>
              <h3>Annulées</h3>
            </td></tr>

            <tr style="height: 40%"><td>
              <h4><?php echo $a; ?> au total</h4>
            </td></tr>

            <tr><td>
              <div style="border: 2px SOLID #333; margin: auto"></div>
            </td></tr>

            <tr style="height: 40%"><td>
              <h4><?php echo $a > 0 ? floatval($a / count($users)) : 0; ?> par utilisateur</h4>
            </td></tr>
        </table>
      </div>
      <div class="ticket">
        <table style="border-collapse: collapse; width: 90%; height: 100%; margin: auto">
          <tr><td>
            <h3>En cours</h3>
          </td></tr>

          <tr style="height: 40%"><td>
            <h4><?php echo $w; ?> au total</h4>
          </td></tr>

          <tr><td>
            <div style="border: 2px SOLID #333; margin: auto"></div>
          </td></tr>

          <tr style="height: 40%"><td>
            <h4><?php echo $w > 0 ? floatval($w / count($users)) : 0; ?> par utilisateur</h4>
          </td></tr>
      </table>
    </div>
    <div class="ticket">
      <table style="border-collapse: collapse; width: 90%; height: 100%; margin: auto">
        <tr><td>
          <h3>Validées</h3>
        </td></tr>

        <tr style="height: 40%"><td>
          <h4><?php echo $v; ?> au total</h4>
        </td></tr>

        <tr><td>
          <div style="border: 2px SOLID #333; margin: auto"></div>
        </td></tr>

        <tr style="height: 40%"><td>
          <h4><?php echo $v > 0 ? floatval($v / count($users)) : 0; ?> par utilisateur</h4>
        </td></tr>
    </table>
  </div>
    </div>
  </div>
</div>

<div id="bandeau_sold">
  <form name="form_connection" method="post" action='./billetterie.php' style="text-align:center">
    <h3>
      Places
    </h3>

    <div id="tickets">
      <?php
        foreach ($types as $type) {
          $query = $db->request(
            "SELECT tickets.*, users.lastname, users.firstname, users.login FROM tickets, users WHERE tickets.idUser = users.id AND status = 0 AND idType = ? ORDER BY id DESC",
            array($type['id'])
          );

          $reservedTickets = $query->fetchAll();

          $query = $db->request(
            "SELECT tickets.*, users.lastname, users.firstname, users.login FROM tickets, users WHERE tickets.idUser = users.id AND status = 1 AND idType = ? ORDER BY id DESC",
            array($type['id'])
          );

          $soldTickets = $query->fetchAll();

          $total = (count($reservedTickets) > 10 * $type['nbrInPack'] ? 10 : count($reservedTickets) / $type['nbrInPack']) + (count($soldTickets) > 10 * $type['nbrInPack'] ? 10 : count($soldTickets) / $type['nbrInPack']);

          if ($total == 0)
            continue;

          ?>
            <div class="ticket" style="width: 90%">
              <table style="border-collapse: collapse; width: 90%; height: 100%; margin: auto">
                <tr><td colspan="6">
                  <h3><?php echo $type['name'], ($total > 1 ? ' - '.$total.' dernières places réservées' : ''); ?></h3>
                </td></tr>

                <tr style="height: 40%">
                  <td><h4>ID</h4></td><td><h4>Prénom</h4></td><td><h4>NOM</h4></td><td><h4>Réservation</h4></td><td><h4>Achat</h4></td><td><h4>Statut</h4></td>
                </tr>

                <?php
                  for ($i = 0; $i < count($soldTickets) && ($i / $type['nbrInPack']) < 10; $i += $type['nbrInPack']) {
                    $ticket = $soldTickets[$i];
                    ?>
                      <tr><td colspan="6">
                        <div style="border: 2px SOLID #333; margin: auto"></div>
                      </td></tr>

                      <tr style="height: 40%">
                        <td><h4><?php echo $ticket['id'] ?></h4></td><td><h4><?php echo $ticket['firstname'] ?></h4></td><td><h4><?php echo $ticket['lastname'] ?></h4></td><td><h4><?php echo 'le ', date('d-m-Y à H:i:s', $ticket['creation_date']); ?></h4></td><td><h4><?php echo 'Payé le ', date('d-m-Y à H:i:s', $ticket['modification_date']); ?></h4></td><td><h4><?php echo ($ticket['login'] == NULL ? 'Extérieur' : 'Etudiant')?></h4></td>
                      </tr>
                    <?php
                  }
                ?>

                <?php
                  for ($i = 0; $i < count($reservedTickets) && ($i / $type['nbrInPack']) < 10; $i += $type['nbrInPack']) {
                    $ticket = $reservedTickets[$i];
                    ?>
                      <tr><td colspan="6">
                        <div style="border: 2px SOLID #333; margin: auto"></div>
                      </td></tr>

                      <tr style="height: 40%">
                        <td><h4><?php echo $ticket['id'] ?></h4></td><td><h4><?php echo $ticket['firstname'] ?></h4></td><td><h4><?php echo $ticket['lastname'] ?></h4></td><td><h4><?php echo 'le ', date('d-m-Y à H:i:s', $ticket['creation_date']); ?></h4></td><td><h4>En cours</h4></td><td><h4><?php echo ($ticket['login'] == NULL ? 'Extérieur' : 'Etudiant')?></h4></td>
                      </tr>
                    <?php
                  }
                ?>
          </table>
          </div>
        <?php
        }
      ?>
    </div>
  </form>
</div>

<div id="bandeau_sell">
  <form name="form_connection" method="post" action='./billetterie.php' style="text-align:center">
    <h3>
      Utilisateurs
    </h3>

    <div id="tickets">
      <?php
        $query = $db->request(
          "SELECT * FROM users WHERE login IS NULL",
          array()
        );

        $externs = $query->rowCount();

        $query = $db->request(
          "SELECT * FROM users WHERE login IS NOT NULL",
          array()
        );

        $students = $query->rowCount();

        $query = $db->request(
          "SELECT SUM(price) AS total FROM users, tickets, types WHERE users.id = tickets.idUser AND tickets.idType = types.id AND login IS NULL",
          array()
        );

        $data = $query->fetch();
        $totalExterns = $data['total'];

        $query = $db->request(
          "SELECT SUM(price) AS total FROM users, tickets, types WHERE users.id = tickets.idUser AND tickets.idType = types.id AND login IS NOT NULL",
          array()
        );

        $data = $query->fetch();
        $totalStudents = $data['total'];
      ?>
      <div class="ticket">
        <table style="border-collapse: collapse; width: 90%; height: 100%; margin: auto">
          <tr><td colspan="6">
            <h3>Nbr inscrits</h3>
          </td></tr>

          <tr style="height: 40%">
            <td><h4><?php echo $students, ' étudiant', ($students > 1 ? 's' : ''); ?></h4></td>
          </tr>

          <tr><td colspan="6">
            <div style="border: 2px SOLID #333; margin: auto"></div>
          </td></tr>

          <tr style="height: 40%">
            <td><h4><?php echo $externs, ' externe', ($externs > 1 ? 's' : ''); ?></h4></td>
          </tr>
        </table>
      </div>

      <div class="ticket">
        <table style="border-collapse: collapse; width: 90%; height: 100%; margin: auto">
          <tr><td colspan="6">
            <h3>Prix moyen</h3>
          </td></tr>

          <tr style="height: 40%">
            <td><h4><?php echo $totalStudents / $students; ?> € par étudiant</h4></td>
          </tr>

          <tr style="height: 40%">
            <td><h4><?php echo $totalExterns / $externs; ?> € par extérieur</h4></td>
          </tr>

          <tr><td colspan="6">
            <div style="border: 2px SOLID #333; margin: auto"></div>
          </td></tr>

          <tr style="height: 40%">
            <td><h4><?php echo ($totalExterns + $totalStudents) / ($externs + $students); ?> € en moyenne</h4></td>
          </tr>
        </table>
      </div>

      <div class="ticket" style="width: 90%">
        <table style="border-collapse: collapse; width: 90%; height: 100%; margin: auto">
          <tr><td colspan="6">
            <h3>10 dernières personnes connectées</h3>
          </td></tr>

          <tr style="height: 40%">
            <td><h4>ID (- Login)</h4></td><td><h4>Prénom</h4></td><td><h4>NOM</h4></td><td><h4>Email</h4></td><td><h4>Dernière connexion</h4></td><td><h4>Statut</h4></td>
          </tr>

          <?php
            $query = $db->request(
              "SELECT * FROM users ORDER BY lastConnection DESC LIMIT 10",
              array()
            );

            $lasts = $query->fetchAll();

            foreach ($lasts as $ticket) {
              ?>
                <tr><td colspan="6">
                  <div style="border: 2px SOLID #333; margin: auto"></div>
                </td></tr>

                <tr style="height: 40%">
                  <td><h4><?php echo $ticket['id'], ($ticket['login'] == NULL ? '' : ' - '.$ticket['login']) ?></h4></td><td><h4><?php echo $ticket['firstname'] ?></h4></td><td><h4><?php echo $ticket['lastname'] ?></h4></td><td><h4><?php echo $ticket['email'] ?></h4></td><td><h4><?php echo 'le ', date('d-m-Y à H:i:s', strtotime($ticket['lastConnection'])); ?></h4></td><td><h4><?php echo ($ticket['login'] == NULL ? 'Extérieur' : 'Etudiant')?></h4></td>
                </tr>
              <?php
            }
          ?>
        </table>
      </div>
    </div>
  </form>
</div>

<?php
  include "ressources/php/footer.php";
?>
