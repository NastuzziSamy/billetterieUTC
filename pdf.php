<?php
  include "ressources/php/include.php";
  include "ressources/php/phpqrcode/qrlib.php";
  include "ressources/php/fpdf/FPDF.php";

  function generateTag($nbr) {
    $chaine = "ABCDEFGHIJKLMNOPQRSUTVWXYZ";
    $length = strlen($chaine);
    $str = '';

    for ($i = 0; $i < $nbr; $i++)
      $str .= $chaine[rand(0, ($length - 1))];

    $query = $GLOBALS['db']->request(
      'SELECT id FROM tags WHERE shortTag = ?',
      array($str)
    );

    if ($query->rowCount() == 0)
      return $str;
    else
      return generateTag($nbr);
  }

  function generatePage($db, $pdf, $ticket, $nbr) {
    if (!$ticket['sellToStudentsOnly'] && !$ticket['sellToContributers'] && ($ticket['lastname'] == NULL || $ticket['firstname'] == NULL || $ticket['birthdate'] == NULL || $ticket['lastname'] == '' || $ticket['firstname'] == '' || $ticket['birthdate'] == '')) {
      header('Location: ./account.php');
      exit;
    }

    $query = $db->request(
      'SELECT * FROM tags WHERE idTicket = ?',
      array($ticket['id'])
    );

    if ($query->rowCount() == 0) {
      $ticket['shortTag'] = generateTag(8);

      $db->request(
        'INSERT INTO tags VALUES(NULL, ?, ?, ?, NULL, 0)',
        array($ticket['id'], $ticket['shortTag'], time())
      );
    }
    else {
      $data = $query->fetch();
      $ticket['shortTag'] = $data['shortTag'];

      if (!$data['isValidated']) {
        $db->request(
          'UPDATE tags SET modificationDate = ? WHERE id = ?',
          array(time(), $data['id'])
        );
      }
    }

    $qrCode = sys_get_temp_dir().'/'.$ticket['shortTag'].'.png';

    QRcode::png(json_encode(array(
      "id" => $ticket['shortTag'],
      "system" => "lightupcity",
      "username" => (($ticket['sellToStudentsOnly'] || $ticket['sellToContributers']) && $_SESSION['login'] != NULL ? $_SESSION['login'] : '')
    )), $qrCode, QR_ECLEVEL_L, 2);

    $pdf->AddPage();
    $pdf->Image("ressources/img/billet_".($ticket['sellToStudentsOnly'] || $ticket['sellToContributers'] ? "etu" : "ext").".png", 0, 0, 210, 297);
    $pdf->SetFont('Product Sans', 'B', 20);
    $pdf->Cell(110, 10, utf8_decode('Compiègne en Lumière'), 0, 1, 'C');
    $pdf->SetFont('Product Sans', '', 8);
    $pdf->Cell(110, 2, utf8_decode('Samedi 24 fébrier 2018'), 0, 1, 'C');
    $pdf->SetFont('Product Sans', 'B', 22);
    $pdf->Cell(110, 20, utf8_decode($ticket['lastname']), 0, 1, 'C');
    $pdf->Cell(110, -5, utf8_decode($ticket['firstname']), 0, 1, 'C');
    $pdf->SetFont('Product Sans', '', 14);
    $pdf->Cell(110, 25, utf8_decode($ticket['name'].($ticket['nbrInPack'] > 1 ? ' (Personne '.$nbr.')' : '').' - ').($ticket['price'] > 0 ? utf8_decode(floor($ticket['price'] / $ticket['nbrInPack']) == ($ticket['price'] / $ticket['nbrInPack']) ? ($ticket['price'] / $ticket['nbrInPack']) : round($ticket['price'] / $ticket['nbrInPack'], 1).' ').chr(128) : 'Gratuit'), 0, 1, 'C');
    $pdf->SetFont('Product Sans', '', 9);
    $pdf->Cell(100, -4, utf8_decode($_SESSION['lastname'] == $ticket['lastname'] && $_SESSION['firstname'] == $ticket['firstname'] ? '' : 'Acheté par '.$_SESSION['lastname'].' '.$_SESSION['firstname']), 0, 0, 'L'); // Faire la gestion du parrain
    $pdf->Cell(13, -4, utf8_decode($ticket['sellToStudentsOnly'] || $ticket['sellToContributers'] ? ($_SESSION['isAdult'] ? 'Majeur' : 'Mineur (à vérfier)') : (time() - strtotime($ticket['birthdate']) > 18 * 365.25 * 24 * 60 * 60 ? 'Majeur - ' : 'Mineur - ').date('d/m/Y', strtotime($ticket['birthdate']))), 0, 0, 'R');
    $pdf->SetFont('Product Sans', 'B', 10);
    $pdf->Text(155, 9, utf8_decode($ticket['shortTag']));
    $pdf->Image($qrCode, 132.5, 14, 63, 63);

    unlink($qrCode);
  }

  if (!isset($_SESSION['id']) || !isset($_GET['id'])) {
    header('Location: ./account.php');
    exit;
  }

  $query = $db->request(
    'SELECT tickets.*, types.name, types.price, types.sellToStudentsOnly, types.sellToTremplinOnly, types.sellToContributers, types.nbrInPack FROM tickets, types WHERE tickets.id >= ? AND idUser = ? AND tickets.idType = types.id',
    array($_GET['id'], $_SESSION['id'])
  );

  if ($query->rowCount() == 0) {
    header('Location: ./account.php');
    exit;
  }

  $tickets = $query->fetchAll();

  $pdf = new \fpdf\FPDF('P','mm','A4');
  $pdf->AddFont('Product Sans', '', 'Product Sans Regular.php');
  $pdf->AddFont('Product Sans', 'B', 'Product Sans Bold.php');

  $currentPack = $tickets[0]['nbrInPack'];
  $nbr = 1;
  $max = $_GET['id'] == -1 ? count($tickets) : $tickets[0]['nbrInPack'];

  for ($i = 0; $i < $max; $i++) {
    if ($currentPack != $tickets[$i]['nbrInPack']) {
      $currentPack = $tickets[$i]['nbrInPack'];
      $nbr = 1;
    }

    generatePage($db, $pdf, $tickets[$i], $nbr++);
  }

  $name = sys_get_temp_dir().'/'.$_SESSION['id'].'.pdf';
  $pdf->Output($name, 'F');

  header("Content-Type: application/pdf");
  header('Content-Disposition:attachment; filename="Compiegne en Lumiere - '.($_GET['id'] == -1 ? 'Tous mes billets' : ($tickets[0]['nbrInPack'] == 1 ? $tickets[0]['lastname'].' '.$tickets[0]['firstname'] : $tickets[0]['name'])).'.pdf"');
  header('Content-Length: '.filesize($name));

  readfile($name);
  unlink($name);

  exit;
?>
