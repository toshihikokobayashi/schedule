<?php 
ini_set( 'display_errors', 0 );
require_once "./const/const.inc";
require_once "./func.inc";
require_once("./const/login_func.inc");
require_once("./const/token.php");
ini_set('include_path', CLIENT_LIBRALY_PATH);
require_once "Google/autoload.php";
set_time_limit(60);

// ****** メイン処理ここから ******

$result = check_user($db, "1");

$year = trim($_POST['y']);
$month = trim($_POST['m']);
$kari_ignore = trim($_POST['kari_ignore']);
$trial_ignore = trim($_POST['trial_ignore']);

// 20150807追加
$err_flag = false;
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

// 20160522 セッション管理を追加
$db->beginTransaction();
$result = set_current_session();

require_once("./get_calender_data.inc");

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

<h3>生徒の月謝計算 - データ取り込み</h3>
	<h4><font color="red">Google カレンダーの授業実績データをデータべースに取り込むことができませんでした。</font></h4>

<?php
	if (count($errArray) > 0) {
		foreach( $errArray as $error) {
?>
			<font color="red"><?= $error ?></font><br><br>
<?php
		}
	}
?>
	再度、Googleカレンダーの授業実績データを取り込んでください。<br><br>
	<form method="post" action="get_calender_data.php">
	<input type="hidden" name="y" value="<?= $year ?>">
	<input type="hidden" name="m" value="<?= $month ?>">
	<input type="submit" value="授業実績データの再取り込み">
	</form>

<?php
} else {
?>
<script type = "text/javascript">
	location.href="./edit_calender_data.php?y=<?= $year ?>&m=<?= $month ?>&kari_ignore=<?= $kari_ignore?>";
</script>
<?php
}
?>



</div>
</body>
</html>