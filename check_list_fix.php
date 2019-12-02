<?php
include "../TCPDF/tcpdf.php";

$errArray = array();

$year = $_POST["y"];
$month = $_POST["m"];

$tcpdf = new TCPDF();
$tcpdf->AddPage();
$tcpdf->SetFont("kozgopromedium", "", 10);

$opts = array(
		'http'=>array(
				'method' => 'GET',
				'header' => "Content-Type: text/html; charset=UTF8\r\n"
							. "Cookie: PHPSESSID={$_COOKIE['PHPSESSID']}\r\n"
			)
	);
$url = "https://{$_SERVER['HTTP_HOST']}".str_replace(basename(__FILE__),'check_list.php',$_SERVER["REQUEST_URI"]);
$htmlout = file_get_contents("$url?y=$year&m=$month&pdf=1",false,stream_context_create($opts));
if ($htmlout) {
	$file = __DIR__."/pay_pdf/pay-$year-$month.pdf";
	if (file_exists($file)) unlink($file);
	$tcpdf->writeHTML($htmlout);
	$tcpdf->Output($file,'F');
	if (!file_exists($file)) {
		$errArray[] = 'PDF出力エラー';
	}
} else {
	$errArray[] = '給与計算エラー';
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="robots" content="index,follow">
<link rel="stylesheet" type="text/css" href="./script/style.css">
</head>
<body>
<div id="header">
	事務システム
</div>
<div id="content" align="center">
<h3>講師・事務員の給与計算</h3>

<h3><?= $year ?>年<?= $month ?>月</h3>

<?php
if (count($errArray) > 0) {
		$errFlag = 1;
?>
<table>
<?php
		foreach( $errArray as $error) {
?>
			<tr><td><font color="red" size="3"><?= $error ?></font></td></tr>
<?php
		}
?>
</table>
<a href="./menu.php">メニューへ戻る</a><br>
<?php
		exit();
	}
?>
確定しました。<br>

<a href="./menu.php">メニューへ戻る</a><br>
</body>
</html>