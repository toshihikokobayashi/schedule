<?php
ini_set( 'display_errors', 0 );
//ini_set('display_errors', 'on');
//error_reporting(E_ALL);
//error_reporting(E_ALL ^E_NOTICE ^E_DEPRECATED);	

require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");

$result = check_user($db, "1");

$file_name = trim($_GET['nm']);

$err_flag = false;
if (isset($file_name) === false || empty($file_name) === true) {
	$err_flag = true;
} else {
	if (preg_match("/^fee_[0-9]{6}_[0-9]+$/", $file_name) !== 1) {
		$err_flag = true;
	}
}

// 読み込むPDFファイルを指定
list($text,$month,$no) = explode("_",$file_name);
$file = './fees_pdf/'.$month.'/'.$file_name.'.pdf';

//echo $file;

// PDFを出力する
header("Content-Type: application/pdf");
 
// ファイルを読み込んで出力
readfile($file);
 
exit();
?>