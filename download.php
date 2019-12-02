<?php

// ダウンロードさせるファイル名
$fname = $_GET['f'];
if (!$fname) { exit; }

$year  = $_GET['y'];
$month = $_GET['m'];
if ($month<10) $month = "0".$month;
if (!$year) { exit; }
if (!$month) { exit; }

$month1 = $_GET['m1'];
$day1   = $_GET['d1'];


if ($month1) {
	if (!$day1) { exit; }
	$str1 = sprintf( '3,%02d%02d,', $month1, $day1 );
	$downname = "rakuten".$year.$month.".csv" ;
	// ヘッダ
	header("Content-Type: application/octet-stream");
	// ダイアログボックスに表示するファイル名
	header("Content-Disposition: attachment; filename=$downname");
	// 対象ファイルを出力する。
	$fp = @fopen("./tmp/".$fname, "r");
	while( $str2 = fgets( $fp, 200 ) ){
		echo $str1.$str2;
	}
	fclose($fp);
	
} else {
	
	$downname = "kinmujikan".$year.$month.".csv" ;
	// ヘッダ
	header("Content-Type: application/octet-stream");
	// ダイアログボックスに表示するファイル名
	header("Content-Disposition: attachment; filename=$downname");
	// 対象ファイルを出力する。
	readfile("./tmp/".$fname);

}

exit;
?>
