<?php
ini_set( 'display_errors', 0 );
require_once "./const/const.inc";
require_once "./func.inc";
require_once("./const/login_func.inc");

mb_regex_encoding("UTF-8");

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


$y1=$year; $m1=$month-1; if ($m1<1) { $y1--; $m1=12; }

$date_list = $date_list_array["$year/$month"];
if (!$date_list)	$date_list = $date_list_array["$y1/$m1"];
if (!$date_list)	$date_list = array();

$season_class_date_list = $date_list;
$date_list = array_merge($date_list, $sat_sun_class_date_list);
$date_list = array_filter($date_list,function($d){global $year,$month; return (str_replace('/0','/',substr($d,0,7))=="$year/$month");});

$date_list_string = "("; $flag=0;
foreach ($date_list as $item) {
	if ($flag==0) { $date_list_string .= "'$item'"; } else { $date_list_string .= ",'$item'"; }
	$flag = 1;
}
$date_list_string = $date_list_string.")";


//$db->beginTransaction();
$result = check_current_session();
$tid = '';

require_once "./check_calender_data.inc";

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
<h3>生徒の月謝計算 - データチェック</h3>

<?php
if ($err_flag == true) {
?>
	<h4><font color="red">データをチェックすることができませんでした。</font></h4>

<?php
	if (count($errArray) > 0) {
		foreach( $errArray as $error) {
?>
			<font color="red"><?= $error ?></font><br><br>
<?php
		}
	}
?>
	Googleカレンダーからのデータ取り込みから、やり直してください。<br><br>
	<form method="post" action="get_calender_data.php">
	<input type="hidden" name="y" value="<?= $year ?>">
	<input type="hidden" name="m" value="<?= $month ?>">
	<input type="hidden" name="kari_ignore" value="<?= $kari_ignore ?>">
	<input type="submit" value="カレンダーデータの再取得">
	</form>
<?php
}else if (count($errArray) > 0) {
?>
	<h3>データに問題がありました。</h3>
	<font color="red">カレンダーの予定を修正して、もう一度、カレンダーからデータを取り込んでください。</font><br><br>
<table border="1">
	<tr>
		<th>カレンダー名</th><th>日にち</th><th>開始時間</th><th>終了時間</th><th>タイトル</th><th>エラー</th>
	</tr>
<?php
	echo count($errArray)."件";
	foreach ($errArray as $error) {
?>
	<tr>
		<td>
			<?= $error["calender_summary"]?>
		</td>
		<td>
			<?= $error["date"]?>
		</td>
		<td>
			<?= $error["start_time"]?>
		</td>
		<td>
			<?= $error["end_time"]?>
		</td>
		<td>
			<?= $error["summary"]?>
		</td>
		<td>
			<?= $error["message"]?>
		</td>
	</tr>
<?php
	}
?>
</table>
	<br>
<!--
	<form method="post" action="get_calender_data.php">
	<input type="hidden" name="y" value="<?= $year ?>">
	<input type="hidden" name="m" value="<?= $month ?>">
	<input type="hidden" name="kari_ignore" value="<?= $kari_ignore ?>">
	<input type="submit" value="カレンダーデータを再度取り込む">&nbsp;&nbsp;<a href="menu.php">メニューへ戻る</a>&nbsp;&nbsp;<a href="student_fee_list.php">生徒の登録へ(料金も登録)</a>
	</form>
-->
<a href="menu.php">メニューへ戻る</a>&nbsp;&nbsp;<a href="student_fee_list.php">生徒の登録へ(料金も登録)</a><br><br>

<!-- 20150819 暫定対応 -->
<!-- 20160331 *と（仮）を読み込まない機能を追加したため、エラーを無視して進める場合を削除 -->
<!--
（上のエラーを無視して、月謝計算を進める場合）<br>
<form method="post" name="search_form" action="./student_list.php">
<input type="hidden" name="y" value="<?=$year?>">
<input type="hidden" name="m" value="<?=$month?>">
生徒氏名：<input type="text" name="cond_name" value="">
<input type="submit" value="&nbsp;&nbsp;検&nbsp;索&nbsp;&nbsp;">
</form>

（上のエラーを無視して、休み一覧を表示する場合）<br>
休み回数一覧を表示する場合は、<a href="./absent_list.php?y=<?= $year ?>&m=<?= $month ?>">こちら</a>です。<br>
-->

<?php
}else if (count($warning_Array) > 0) {
?>
	<h3>警告</h3>
	<font color="red">以下の予定が季節講習・土日講習予定と重複しています。問題ないことを先生にご確認ください。</font><br>
	問題なければ、<a href="./save_statement.php?y=<?= $year ?>&m=<?= $month ?>" target="_blank">こちら</a>から請求データ作成に進めます。<br><br>
<a href="menu.php">メニューへ戻る</a><br><br>
<table border="1">
	<tr>
		<th>カレンダー名</th><th>日にち</th><th>開始時間</th><th>終了時間</th><th>タイトル</th><th>警告</th>
	</tr>
<?php
	echo count($warning_Array)."件";
	foreach ($warning_Array as $error) {
?>
	<tr>
		<td>
			<?= $error["calender_summary"]?>
		</td>
		<td>
			<?= $error["date"]?>
		</td>
		<td>
			<?= $error["start_time"]?>
		</td>
		<td>
			<?= $error["end_time"]?>
		</td>
		<td>
			<?= $error["summary"]?>
		</td>
		<td>
			<?= $error["message"]?>
		</td>
	</tr>
<?php
	}
?>
</table>
<?php
} else {
?>
	<h4><font color="blue">データに問題はありませんでした。</font></h4>
	<div class="menu_box">
	<br>
	◇ １．先生の勤務時間チェックをする場合、２．休みの回数チェックをする場合、<br>
  &nbsp;&nbsp;&nbsp;&nbsp;３．月謝金額お知らせメールを送信する場合、４．部門別受講料を算出する場合は、<br>
	&nbsp;&nbsp;&nbsp;&nbsp;<a href="./save_statement.php?y=<?= $year ?>&m=<?= $month ?>" target="_blank">こちら</a>から請求データを作成してください。<br><br>
	</div>
<!--
	<div class="menu_box">
	<br>
	◇ 生徒の一覧を表示する場合は、<a href="./student_list.php?y=<?= $year ?>&m=<?= $month ?>" target="_blank">こちら</a>です。<br><br>
	◇ すべての生徒の明細書を１つのPDFファイルに出力する場合は、<a href="./all_output_pdf.php?y=<?=$year?>&m=<?=$month?>" target="_blank">こちら</a>です。<br><br>
	</div>
-->
<!--
	<div class="menu_box">
	部門別受講料を表示する場合は、<a href="./total_list.php?y=<?= $year ?>&m=<?= $month ?>">こちら</a>です。<br>
	</div>
-->
<!--
	<div class="menu_box">
	<br>
	◇ 休み回数一覧を表示する場合は、<a href="./absent_list.php?y=<?= $year ?>&m=<?= $month ?>" target="_blank">こちら</a>です。<br><br>
	◇ 月謝金額お知らせメール送信の準備をする場合は、<a href="./output_fee.php?y=<?= $year ?>&m=<?= $month ?>" target="_blank">こちら</a>です。<br>
　　&nbsp;<font color="red">※月謝金額お知らせメール送信の準備には、１分ぐらい時間がかかります。<br>
　　　　処理が終了しますまでしばらくお待ちください。</font><br><br>
	</div>
-->
	<br>
	<a href="menu.php">メニューへ戻る</a>
<?php
}
?>

</div>
</body>
</html>