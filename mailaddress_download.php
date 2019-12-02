<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
$result = check_user($db, "1");

$errArray = array();
$errFlag = 0;

$year = date("Y");
$month = date("n");
$month = $month-1;
if ($month<1) { $year--; $month+=12; }

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF8">
<meta name="robots" content="noindex,nofollow">
<title>事務システム</title>
<script type = "text/javascript">
<!--
function download()
{
	var url;
	url = "./mailaddress_download.php?down=1";
	url += '&y='+document.getElementsByName('y')[0].value;
	url += '&m='+document.getElementsByName('m')[0].value;
	url += '&type='+document.getElementById('form').type.value;
	url += '&lesson='+document.getElementById('form').lesson.value;
	document.location = url;
}
//-->
</script>
<link rel="stylesheet" type="text/css" href="./script/style.css">
<script type="text/javascript" src="./script/calender.js"></script>
</head>
<body>

<div id="header">
	事務システム 
</div>

<div id="content" align="center">

<h3>メールアドレスダウンロード</h3>
<a href="menu.php">メニューへ戻る</a><br><br>

<?php
	if (count($errArray) > 0) {
		foreach( $errArray as $error) {
?>
			<font color="red" size="3"><?= $error ?></font><br>
<?php
		}
		exit();
	}
?>
<form id="form" method="get" action="mailaddress_download1.php">
<table>
<tr><th align="left">年月指定</th></tr>
<tr><td>
<input type="text" name="y" value="<?php echo $year; ?>" size="4">年&nbsp;
<input type="text" name="m" value="<?php echo $month; ?>" size="4">月&nbsp;
</td></tr>
<tr><th>　</th></tr>
<tr><th align="left">対象生徒指定</th></tr>
<tr><td>
<table>
<tr><td><input type="radio" name="type" value="1" checked>指定月受講者</td></tr>
<tr><td><input type="radio" name="type" value="2">指定月以降入会者</td></tr>
<tr><td><input type="radio" name="type" value="3">指定月受講者及び指定月以降入会者</td></tr>
</table>
</td></tr>
<tr><th>　</th></tr>
<tr><th align="left">部門指定</th></tr>
<tr><td>
<table>
<tr><td><input type="radio" name="lesson" value="0" checked>全部門</td></tr>
<tr><td><input type="radio" name="lesson" value="1">塾</td></tr>
<tr><td><input type="radio" name="lesson" value="2">英会話</td></tr>
<tr><td><input type="radio" name="lesson" value="3">ピアノ</td></tr>
<tr><td><input type="radio" name="lesson" value="4">習い事</td></tr>
</table>
</td></tr>
</table>
<br>
<input type="submit" value="実行">
</form>

</div>
</body>
</html>