<?php
ini_set( 'display_errors', 0 );
require_once "./const/const.inc";                                                                                            
require_once "./func.inc";
require_once("./const/login_func.inc");

ini_set('include_path', CLIENT_LIBRALY_PATH);
require_once "Google/autoload.php";
set_time_limit(60);

// ****** メイン処理ここから ******

$err_flag = false;
$errArray = array();

$result = check_user($db, "1");

$year = trim($_GET['y']);
$month = trim($_GET['m']);
$kari_ignore = trim($_GET['kari_ignore']);
$nocheck = trim($_GET['nocheck']);

// 20150807追加
if (isset($year) === false || empty($year) === true) {
	$err_flag = true;
} else {
	if (preg_match("/^[0-9]+$/", $year) !== 1 || $year < 2015) {
		$err_flag = true;
	}
}
if (isset($month) === false || empty($month) === true) {
	$err_flag = true;
} else {
	if (preg_match("/^[0-9]+$/", $month) !== 1 || $month < 1 || $month > 12) {
		$err_flag = true;
	}
}
//if (isset($kari_ignore) === false || empty($kari_ignore) === true) {
//	$err_flag = true;
//} else {
	if (preg_match("/^[0,1]{1}$/", $kari_ignore) !== 1) {
		$err_flag = true;
	}
//}
if ($err_flag == true) {
	header('location: menu.php');
	exit();
}


$db->beginTransaction();

$result = check_current_session();

require_once "./edit_calender_data.inc";

if ($err_flag == true) {
	$db->rollBack();
} else {
	$db->commit();
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF8">
<meta name="robots" content="noindex,nofollow">
<title>事務システム</title>
<style type="text/css">
<!--
 -->
</style>
<script type = "text/javascript">
<!--
-->
</script>
<link rel="stylesheet" type="text/css" href="./script/style.css">
<script type="text/javascript" src="./script/calender.js"></script>
</head>
<body>
<div align="center">

<?php
if ($err_flag == true) {
?>

<h3>生徒の月謝計算 - データ編集</h3>
	<h4><font color="red">データを編集することができませんでした。</font></h4>

<?php
	if (count($errArray) > 0) {
		foreach( $errArray as $error) {
?>
			<font color="red"><?= $error ?></font><br><br>
<?php
		}
	}
?>
	Googleカレンダーからのデータ取得から、やり直してください。<br><br>
	<form method="post" action="get_calender_data.php">
	<input type="hidden" name="y" value="<?= $year ?>">
	<input type="hidden" name="m" value="<?= $month ?>">
	<input type="hidden" name="kari_ignore" value="<?= $kari_ignore ?>">
	<input type="submit" value="カレンダーデータの再取得">
	</form>

<?php
} else {
?>
<h3>生徒の月謝計算 - データ編集</h3>
	<h4><font color="blue">データを編集することができました。</font></h4>
	次にデータのチェックをします。<br>
	<a href="./check_calender_data.php?y=<?= $year ?>&m=<?= $month ?>&kari_ignore=<?= $kari_ignore ?><?= $nocheckoption ?>">&nbsp;>> データのチェックへ</a>

<br><br>
先頭にアスタリスクがついていて無視された予定のタイトルです。<br>
<?php
	if (count($asterisk_array) > 0) {
?>
<table>
<?php
		foreach($asterisk_array as $asterisk_title) {
?>
<tr><td>
			<?= $asterisk_title ?>
</td></tr>
<?php
		}
?>
</table>
<?php
	}
?>


<!--
	<form method="post" action="student_list.php">
	<input type="text" name="y" value="<?= $year ?>" size="4">年&nbsp;
	<input type="text" name="m" value="<?= $month ?>" size="4">月<br>
	<input type="submit" value="一覧表示">&nbsp;&nbsp;<a href="menu.php">メニューへ戻る</a>
	</form>
-->

<!--
20150818
<div class="menu_box">
生徒の一覧を表示する場合は、<a href="./student_list.php?y=<?= $year ?>&m=<?= $month ?>">こちら</a>です。<br>
すべての生徒の明細書を１つのPDFファイルに出力する場合は、<a href="./all_output_pdf.php?y=<?=$year?>&m=<?=$month?>">こちら</a>です。<br>
</div>

<div class="menu_box">
部門別受講料を表示する場合は、<a href="./total_list.php?y=<?= $year ?>&m=<?= $month ?>">こちら</a>です。<br>
</div>

<br>
<a href="menu.php">メニューへ戻る</a>
-->

<?php
}
?>



</div>
</body>
</html>