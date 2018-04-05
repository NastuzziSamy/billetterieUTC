<?php
	include "ressources/php/include.php";
  include "ressources/php/phpqrcode/qrlib.php";
  include "ressources/php/fpdf/FPDF.php";

	function generatePageH($db, $pdf, $badge) {
		$pdf->AddPage('L');
		$pdf->Image('ressources/img/badge1.png', 0, 0);
		$pdf->Image('ressources/img/photos/'.$badge['pictureName'].'.png', 218, 35, 80.5, 80.5);
		$pdf->SetFont('Product Sans', 'B', 50);
		$pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(200, 90, utf8_decode($badge['firstname']), 0, 1, 'C');
		$pdf->Cell(200, -55, utf8_decode($badge['lastname']), 0, 1, 'C');
		$pdf->SetFont('Product Sans', '', 50);
		$pdf->Cell(200, 115, utf8_decode($badge['isResp'] ? 'Responsable' : $badge['role']), 0, 1, 'C');
    $pdf->Cell(291.5, 6, utf8_decode($badge['access']), 0, 1, 'R');
  }

	function generatePageV($db, $pdf, $badge) {
		$pdf->AddPage();
		$pdf->Image('ressources/img/badge2.png', 0, 0);

		if ($badge['pictureName'] != NULL)
			if (file_exists('ressources/img/photos/'.$badge['pictureName'].'.png'))
				$pdf->Image('ressources/img/photos/'.$badge['pictureName'].'.png', 57, 26, 118, 118);
			else
				$pdf->Image('ressources/img/photos/'.$badge['pictureName'].'.jpg', 57, 26, 118, 118);
		else if ($badge['login'] != NULL) {
			try {
				$pdf->Image('https://demeter.utc.fr/portal/pls/portal30/portal30.get_photo_utilisateur?username='.$badge['login'], 69, 26, 95, 118, 'JPEG');
			}
			catch (Exception $e) {}
		}
		$pdf->SetFont('Product Sans', 'B', 75);
		$pdf->SetTextColor(255, 255, 255);
		$pdf->Cell(217, 155, '', 0, 1, 'C');
    $pdf->Cell(217, 25, utf8_decode($badge['firstname']), 0, 1, 'C');
		$pdf->Cell(217, 30, utf8_decode($badge['lastname']), 0, 1, 'C');
		$pdf->SetFont('Product Sans', '', 40);
		$pdf->Cell(217, 30, utf8_decode($badge['isResp'] ? 'Responsable' : ''), 0, 1, 'C');
		$pdf->Cell(217, 5, utf8_decode($badge['role']), 0, 1, 'C');
		$pdf->Cell(217, 43, '', 0, 1, 'C');
		if (empty($badge['access']))
			$pdf->Cell(213, 0, utf8_decode('Aucun'), 0, 1, 'R');
		else
    	$pdf->Cell(213, 0, utf8_decode($badge['access']), 0, 1, 'R');
  }

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

	$query = $db->query('SELECT * FROM badges WHERE role = "Oise Media"');
	$badges = $query->fetchAll();

	if (isset($_GET['h']) && $_GET['h'] == 1)
		$pdf = new \fpdf\FPDF('P', 'mm', array(312, 187));
	else
		$pdf = new \fpdf\FPDF('P', 'mm', array(232, 328));

	$pdf->AddFont('Product Sans', '', 'Product Sans Regular.php');
  $pdf->AddFont('Product Sans', 'B', 'Product Sans Bold.php');

	foreach ($badges as $badge) {
		if (isset($_GET['h']) && $_GET['h'] == 1)
			generatePageH($db, $pdf, $badge);
		else
			generatePageV($db, $pdf, $badge);
	}

	//$pdf->Output('Compiegne en Lumiere - Badges.pdf', 'D');
	$pdf->Output('Compiegne en Lumiere - Badges.pdf', 'I');
	exit;
?>
