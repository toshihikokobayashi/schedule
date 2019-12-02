<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
$result = check_user($db, "1");

$errArray = array();

$year = $_POST["y"];
$month = $_POST["m"];

if (is_null($year) == true || $year == "") {
	$year = $_GET["y"];
}
if (is_null($month) == true || $month == "") {
	$month = $_GET["m"];
}
if ((is_null($year) == true || $year == "") || (is_null($month) == true || $month == "")) {
	$errArray[] = '年月が不明です。';
}

if ($_POST['mode'] == 'transport') {
	$_SESSION['ulogin'] = array('teacher_id' => $_POST["no"]);
	session_write_close();
	$teacher_acount = 2; $mode='transport';
//	$last_month = strtotime(date('Y-m-1') . '-1 month');
//	$year = date('Y', $last_month); $month = date('n', $last_month);
	require_once("./teacher_edit.php");
	exit;
}

if ($_POST["no"]) {
		$_SESSION['ulogin'] = array('teacher_id' => $_POST["no"]);
		session_write_close();
		header('location: ../../sakura-teacher/check_work_time.php?y='.$year.'&m='.$month);
		exit;
}
session_write_close();

if ($_POST['check']=="出席簿登録確認") {
	$mode = 'check';
}

$course_list = get_course_list($db);

if ($_POST["search_name"]) {
	$search_name = trim($_POST["search_name"]);
}

$param_array = array();
$value_array = array();
if ($search_name) {
	array_push($param_array," tbl_teacher.name like concat('%',?,'%') ");
	array_push($value_array, $search_name);
}

// ふりがなの50音順にソートする
$order_array = array("tbl_teacher.furigana asc");

// 先生一覧を取得
	$all_flag = "0";	// 現先生を抽出
	$teacher_list = get_teacher_list($db, $param_array, $value_array, $order_array, $all_flag);

try {
	
	if ($_POST['fixed']=='確定') {
		$db->beginTransaction();
		$db->query("INSERT INTO tbl_fixed (year, month, fixed, insert_timestamp) VALUES (\"$year\", \"$month\", 1, now())");
		$db->commit();
	} else if ($_POST['unfixed']=='確定解除') {
		$db->beginTransaction();
		$db->query("DELETE FROM  tbl_fixed WHERE year=\"$year\" AND month=\"$month\"");
		$db->commit();
	}

	$stmt = $db->query("SELECT fixed FROM tbl_fixed WHERE year=\"$year\" AND month=\"$month\"");
	$rslt = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($rslt['fixed']) $fixed = 1;
	
	$stmt = $db->query("SELECT teacher_id, status FROM tbl_transport_status WHERE year=\"$year\" AND month=\"$month\"");
	$transport_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$transport_status = array_combine(array_column($transport_status, 'teacher_id'), array_column($transport_status, 'status'));

} catch (Exception $e) {
	echo $e->getMessage();
	$db->rollback();
	exit();
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF8">
<meta name="robots" content="noindex,nofollow">
<title>事務システム</title>
<script type = "text/javascript">
function search_clear() {
	document.forms["teacher_list"].elements["search_name"].value = "";
	document.forms["teacher_list"].submit();
}
function attendance_record(no) {
	document.forms["teacher_list"].elements["no"].value = no;
	document.forms["teacher_list"].target = "_blank";
	document.forms["teacher_list"].submit();
	document.forms["teacher_list"].elements["no"].value = '';
	document.forms["teacher_list"].target = "_self";
}
function transport_cost(no) {
	document.forms["teacher_list"].elements["mode"].value = "transport";
	document.forms["teacher_list"].elements["no"].value = no;
	document.forms["teacher_list"].submit();
}
var pay_display_flag = 0;
function pay_display() {
	var pay = document.getElementsByName('pay');
	for (var i=0;i<pay.length;i++) {
		pay[i].style.display = (pay_display_flag)? "":"none";
	}
	pay_display_flag = 1-pay_display_flag;
	return false;
}
</script>
<link rel="stylesheet" type="text/css" href="./script/style.css">
<script type="text/javascript" src="./script/calender.js"></script>
</head>
<body>

<div id="header">
	事務システム 
</div>


<div id="content" align="center">

<h3>出席簿</h3>
<h3><?= $year ?>年<?= $month ?>月</h3>

<a href="menu.php">メニューへ戻る</a><br><br>

<?php
	if (count($errArray) > 0) {
		foreach( $errArray as $error) {
?>
			<font color="red" size="3"><?= $error ?></font><br><br>
<?php
		}
		exit();
	}
?>

<form method="post" name="teacher_list" action="teacher_work_time_check_list.php">
<input type="hidden" name="y" value="<?= $year ?>">
<input type="hidden" name="m" value="<?= $month ?>">
<input type="hidden" name="no" value="">
<input type="hidden" name="mode" value="">

<table border="1">
	<tr>
		<th>講師名</th>
		<td><input type="text" name="search_name" value="<?= $search_name; ?>">※氏名の部分一致で検索します</td>
	</tr>
	<tr>
		<td colspan="4" align="center">
			<input type="submit" value="検索">&nbsp;&nbsp;
			<input type="button" value="検索解除" onclick="search_clear()">
		</td>
	</tr>
</table>

<br>
<div class="menu_box">
	<font color="black" size="-1">
	■&nbsp;&nbsp;出席簿ボタンをクリックすると別ウィンドウに出席簿画面が表示されます。<br>
	■&nbsp;&nbsp;出席簿登録確認の前にカレンダーの取り込みを行ってください。<br>
	■&nbsp;&nbsp;出席簿登録確認ボタンをクリックし全員"OK"の場合、確定ボタンにより確定できます。<br>
	</font>
</div>
<input type="submit" id="check" name="check" value="出席簿登録確認">
<input type="submit" id="fixed" name="fixed" value="確定" disabled>
<?= $fixed?'確定済み':'未確定' ?>
　　　　　　　　<input type="submit" id="unfixed" name="unfixed" value="確定解除" onclick="return window.confirm('確定を解除してよろしいですか？')">
<br>
<table border="1">
<tr>
<th>講師名</th><th></th><th>出欠確認</th><th colspan="2">交通費</th>
</tr>
<?php
try {
	foreach ($teacher_list as $item) {
?>
	<tr>
		<td><font color=<?= $color ?>><?= $item["name"] ?></font></td>
		<td align="center"><input type="button" value="出席簿" onclick="attendance_record(<?= $item['no'] ?>)"></td>
<?php
		if ($mode == 'check') {
			$opts = array(
					'http'=>array(
							'method' => 'GET',
							'header' => "Content-Type: text/html; charset=UTF8\r\n"
										. "Cookie: PHPSESSID={$_COOKIE['PHPSESSID']}\r\n"
						)
				);
			$url = "http://{$_SERVER['HTTP_HOST']}".str_replace('/sakura/schedule/'.basename(__FILE__),'/sakura-teacher/check_work_time.php',$_SERVER["REQUEST_URI"]);
			$res = file_get_contents("$url?y=$year&m=$month&tid={$item['no']}&mode=check",false,stream_context_create($opts));
			$strpos1 = strpos($res, '<table ');
			$strpos2 = strpos($res, '</table>')+8;
			$ress[] = "<br><h3>{$item['name']}</h3>".substr($res, $strpos1, $strpos2-$strpos1);

			$ret = '';

			if (preg_match_all('|<select(.*?)</select>|su',$res,$match)) {
				foreach ($match[1] as $str) {
					if (strpos($str, 'selected')===false) {
						$ret .= '未登録あり　';
						break;
					} else {
						
					}
				}
			}
			if (strpos($res,'background-color:#FFFF00')!==false) $ret .= '登録不一致あり　';
			if (strpos($res,$item["name"])===false) $ret .= 'エラー発生　';

			if (!$ret) $ret = 'OK'; else { $ret = "<font color=\"red\">$ret</font>"; $flag_NG = 1; }
			echo "<td>$ret</td>";
		} else {
			if ($fixed) {
				echo "<td>OK</td>";
			} else {
				$flag_NG = 1;
				echo "<td></td>";
			}
		}
		echo "<td align=\"center\"><input type=\"button\" value=\"交通費\" onclick=\"transport_cost({$item['no']})\"></td>";
		$transport_status_index = $transport_status[$item['no']];
		if (!$transport_status_index) $transport_status_index = 0;
		echo "<td>{$transport_status_str[$transport_status_index]}</td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "</form>";
	
	echo "<script type = \"text/javascript\">";
	echo "<!--\r\n";
	if ($flag_NG) {
		echo "document.getElementById('check').disabled=false;";
	} else {
		echo "document.getElementById('check').disabled=true;";
	}
	if ($flag_NG || $fixed) {
		echo "document.getElementById('fixed').disabled=true;";
	} else {
		echo "document.getElementById('fixed').disabled=false;";
	}
	echo "//-->\r\n";
	echo "</script>";

foreach($ress as $res) {
	$res = preg_replace('|<option[^>]+?selected>(.*?)</option>.*?</select>|su', '</select>$1', $res);
	$res = preg_replace('|<select(.*?)</select>|su', '', $res);
	echo $res;
}

} catch (Exception $e) {
	echo $e->getMessage();
	exit();
}
?>
</div>
<br><br>

<div id="footer">
</div>

<script type = "text/javascript">
<!--
pay_display();
//-->
</script>

</body>
</html>
