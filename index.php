<?php
error_reporting(E_WARNING);

$action = $_GET['action'] ?? null;

$labels = array_values(array_filter(
	$_GET['label'] ?? [],
	function($v) {
		return strlen(trim($v)) > 0;
	}
));

if ($action == 'wait' || ($action == 'pdf' && count($labels) === 0)) {
	echo "Warte auf eine Eingabe...";
}
else if ($action == 'pdf') {
	require_once('TCPDF/tcpdf.php');

	$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

	$pdf->setPrintHeader(false);
	$pdf->setPrintFooter(false);
	$pdf->SetMargins($marginH, $marginV, $marginH);

	$pdf->AddPage();

	// 1 x 5 labels per page
	$maxLabelNo = 5;
	$labelNo = min(count($labels), $maxLabelNo);
	// A4: 210 x 297mm
	$A4Y = 297;
	$A4X = 210;
	// Label: 100 x 52mm
	$labelY = 52;
	$labelX = 100;

	$marginY = 15;
	$marginX = ($A4X - $labelX) / 2; // (210mm - 100mm) / 2 = 55mm

	$fontSizeMM = $labelY / 7;
	$fontSize = $pdf->getHTMLUnitToUnits($fontSizeMM . 'mm', 1, 'pt', true);
	$pdf->SetFont('dejavusans', '', $fontSize);

	$pixelSize = 0.1;

	$solidStyle = array('width' => $pixelSize, 'dash' => 0, 'color' => array(0));
	$dottedStyle = array('width' => $pixelSize, 'dash' => 0/* "3,10" */, 'color' => array(220));

	// Draw vertical lines for cutting (beginning of page)
	$rightX = $marginX + $labelX;
	$pdf->SetLineStyle($solidStyle);
	$pdf->Line($marginX, 0, $marginX, $marginY);
	$pdf->Line($rightX, 0, $rightX, $marginY);

	for($i = 0; $i < $labelNo; $i++) {
		$label = $labels[$i];
		$x = $marginX;
		$y = $marginY + $i * $labelY;

		// Draw horizontal lines for cutting
		$pdf->SetLineStyle($solidStyle);
		$pdf->Line(0, $y, $marginX, $y);
		$pdf->Line($marginX + $labelX, $y, $A4X, $y);

		if ($i+1 == $labelNo) {
			// Draw further horizontal lines for cutting
			$bottomY = $marginY + ($i+1) * $labelY;
			$pdf->Line(0, $bottomY, $marginX, $bottomY);
			$pdf->Line($marginX + $labelX, $bottomY, $A4X, $bottomY);

			// Draw vertical lines for cutting (end of page)
			$pdf->Line($marginX, $bottomY, $marginX, $A4Y);
			$pdf->Line($rightX, $bottomY, $rightX, $A4Y);
		}

		$borderPositions = $i == 0 ? 'LTRB' : 'LRB'; // Avoid to paint borders twice (top & bottom)
		// Draw text
		$pdf->MultiCell(
			$labelX, $labelY, // size
			$label, // text
			// 0, // no border
			// 1, // rectangle border
			array($borderPositions => $dottedStyle), // dotted light grey border
			'C', // Horizontal align center
			false, // No background color
			2, // position is below afterwards
			$x, $y, // Position
			true, // reset last cell height
			false, // don't stretch text
			false, // is not HTML
			false, // auto padding
			$labelY, // maximum height = height
			'M', // Vertical align middle
			true // Fit content to cell (i.e. make text smaller if required)
		);
	}

	$time = time();
	$pdf->Output("labels-{$time}.pdf", 'I'); // I = Return Inline in Browser
}
else {
	?>
	<!DOCTYPE html>
	<html lang="de">
		<head>
			<title>Labels drucken</title>
			<meta charset="utf-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dejavu-sans@1.0.0/css/dejavu-sans.css">
			<style type="text/css">
				* {
					font-family: 'DejaVu Sans', sans-serif;
				}
				html {
					height: 100%;
				}
				body {
					display: flex;
					height: 100%;
					margin: 0;
				}
				.editor, .pdf {
					width: 50%;
					height: 100%;
					border: 0;
					box-sizing: border-box;
				}
				.editor {
					overflow: auto;
					padding: 1em;
				}
				.pdf {
					border-left: 5px solid #ccc;
				}
				h1 {
					margin: 0 0 0.1em 0;
				}
				h2 {
					margin: 0.75em 0 0.1em 0;
				}
				textarea {
					width: 99%;
				}
				.submit {
					float: right;
					height: 90%;
					font-size: 0.8em;
				}
			</style>
		</head>
		<body onbeforeprint="alert('Bitte die Druckfunktion im rechten Bereich nutzen!')" onload="document.getElementsByTagName('form')[0].submit()">
			<form method="GET" action="index.php" target="pdf" class="editor" onformchange="this.submit()">
				<h1>Labels drucken v1.0.0</h1>
				<small>Bitte ins rechte Feld klicken um die Label zu generieren!</small>
				<input type="hidden" name="action" value="pdf" />
				<?php for($i = 1; $i <= 5; $i++) { ?>
					<h2>Label <?=$i?></h2>
					<textarea name="label[]" rows="5" onblur="this.form.submit()"></textarea>
				<?php } ?>
			</form>
			<iframe src="index.php?action=wait" name="pdf" class="pdf"></iframe>
		</body>
	</html>
	<?php
}
