<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
//require_once("./calculate_fees.php");
//$result = check_user($db, "1");

$errArray = array();
$errFlag = 0;

try {

$year = $_POST["y"];
$month = $_POST["m"];
$type = $_POST["t"];
$lesson_id = $_POST["l"];

if (is_null($year) == true || $year == "") {
	$year = $_GET["y"];
}
if (is_null($month) == true || $month == "") {
	$month = $_GET["m"];
}
if (is_null($type) == true || $type == "") {
	$type = $_GET["t"];
}
if (is_null($lesson_id) == true || $lesson_id == "") {
	$lesson_id = $_GET["l"];
}
if ((is_null($year) == true || $year == "") || (is_null($month) == true || $month == "")) {
	throw new Exception('年月が不明です。');
}


$year1 = $year;
$month1 = $month+1;
if	($month1>12) { $year1++; $month1=1; }
$date1 = "$year-".sprintf('%02d',$month)."-11";
$date2 = "$year1-".sprintf('%02d',$month1)."-10";

$year2 = $year;
$month2 = $month-1;
if	($month2<1) { $year2--; $month2=12; }


// 生徒一覧を取得
$student_list = array();
$param_array = array();
$value_array = array();
array_push($param_array, "tbl_member.kind = ?");
array_push($value_array, "3");
array_push($param_array, "name <> ?");
array_push($value_array, "体験生徒");
$order_array = array("tbl_member.furigana asc");
$member_list = get_simple_member_list($db, $param_array, $value_array, $order_array, 2);
if (count($member_list) == 0) {
	$errFlag = 1;
	throw new Exception('生徒が見つかりませんでした。');
}

if ($lesson_id !=0 ) {

switch ($type) {
case 5:
	// 先生
	$sql = "SELECT ".
				"lesson_id as lesson_id, teacher_id as teacher_no ".
 				"FROM tbl_statement_detail ".
 				"WHERE FROM_UNIXTIME(start_timestamp, '%Y') = $year ".
				"AND  FROM_UNIXTIME(start_timestamp, '%m')+0 = $month ".
				"AND absent_flag <> '1' ".
				"AND lesson_id = $lesson_id ".
				" GROUP BY teacher_id";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$list = $stmt->fetchAll(PDO::FETCH_ASSOC);
	break;

case1:
	// 登録生徒
	$sql = "SELECT tbl_member.no FROM tbl_member, tbl_fee ".
			"WHERE tbl_member.kind = 3 ".
			"AND tbl_fee.member_no = tbl_member.no ".
			"AND tbl_fee.lesson_id = $lesson_id ".
			"AND tbl_member.name <> '体験生徒' ".
			"AND ( tbl_member.del_flag = 0 AND LEFT(tbl_member.insert_timestamp,10) <= '$date2' ) ".
			"AND NOT ( tbl_member.del_flag = 2 AND LEFT(tbl_member.update_timestamp,10) < '$date2' ) ".
			"ORDER BY tbl_member.furigana ";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$list = $stmt->fetchAll(PDO::FETCH_ASSOC);
	break;
	
case 2:
	// 生徒
	$sql = "SELECT lesson_id, count(student_id) as num  ".
				"FROM tbl_statement_detail ".
				"WHERE FROM_UNIXTIME(start_timestamp, '%Y') = $year ".
				"AND  FROM_UNIXTIME(start_timestamp, '%m')+0 = $month ".
				"AND absent_flag <> '1' ".
				"AND lesson_id = $lesson_id ".
				" GROUP BY student_id".
				" ORDER BY student_id";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$list = $stmt->fetchAll(PDO::FETCH_BOTH);
	break;

case 3:
	// 入会者
	$sql = "SELECT tbl_member.no FROM tbl_member, tbl_statement_detail ".
			"WHERE tbl_member.kind = 3 ".
			"AND tbl_member.del_flag = 0 ".
			"AND tbl_member.name <> '体験生徒' ".
			"AND tbl_statement_detail.student_id = tbl_member.no ".
			"AND tbl_statement_detail.lesson_id = $lesson_id ".
			"AND tbl_statement_detail.start_timestamp = ( SELECT MIN(start_timestamp) FROM tbl_statement_detail WHERE student_id = tbl_member.no ) ".
			"AND FROM_UNIXTIME(tbl_statement_detail.start_timestamp,'%Y/%m') = '$year/".sprintf('%02d',$month)."' ".
			"ORDER BY tbl_member.furigana ";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$list = $stmt->fetchAll(PDO::FETCH_ASSOC);
	break;

case 4:
	// 前月退会者
	$sql = "SELECT tbl_member.no FROM tbl_member, tbl_statement_detail ".
			"WHERE tbl_member.kind = 3 ".
			"AND tbl_member.del_flag = 2 ".
			"AND tbl_member.name <> '体験生徒' ".
			"AND tbl_statement_detail.student_id = tbl_member.no ".
			"AND tbl_statement_detail.lesson_id = $lesson_id ".
			"AND tbl_statement_detail.start_timestamp = ( SELECT MAX(start_timestamp) FROM tbl_statement_detail WHERE student_id = tbl_member.no ) ".
			"AND FROM_UNIXTIME(tbl_statement_detail.start_timestamp,'%Y/%m') = '$year/".sprintf('%02d',$month2)."' ".
			"ORDER BY tbl_member.furigana ";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$list = $stmt->fetchAll(PDO::FETCH_ASSOC);
	break;
}

} else {

switch ($type) {
case 5:
	// 先生
	$sql = "SELECT ".
				"lesson_id as lesson_id, teacher_id as teacher_no ".
 				"FROM tbl_statement_detail ".
 				"WHERE FROM_UNIXTIME(start_timestamp, '%Y') = $year ".
				"AND  FROM_UNIXTIME(start_timestamp, '%m')+0 = $month ".
				"AND absent_flag <> '1' ".
				"GROUP BY teacher_id ";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$list = $stmt->fetchAll(PDO::FETCH_BOTH);
	break;

case 1:
	// 登録生徒
	$sql = "SELECT tbl_member.no FROM tbl_member ".
			"WHERE tbl_member.kind = 3 ".
			"AND tbl_member.name <> '体験生徒' ".
			"AND ( tbl_member.del_flag = 0 AND LEFT(tbl_member.insert_timestamp,10) <= '$date2' ) ".
			"AND NOT ( tbl_member.del_flag = 2 AND LEFT(tbl_member.update_timestamp,10) < '$date2' ) ".
			"ORDER BY tbl_member.furigana ";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$list = $stmt->fetchAll(PDO::FETCH_ASSOC);
	break;
	
case 2:
	// 生徒
	$sql = "SELECT lesson_id, count(student_id) as num  ".
				"FROM tbl_statement_detail ".
				"WHERE FROM_UNIXTIME(start_timestamp, '%Y') = $year ".
				"AND  FROM_UNIXTIME(start_timestamp, '%m')+0 = $month ".
				"AND absent_flag <> '1' ".
				" GROUP BY student_id".
				" ORDER BY student_id";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$list = $stmt->fetchAll(PDO::FETCH_BOTH);
	break;

case 3:
	// 入会者
	$sql = "SELECT tbl_member.no FROM tbl_member, tbl_statement_detail ".
			"WHERE tbl_member.kind = 3 ".
			"AND tbl_member.del_flag = 0 ".
			"AND tbl_member.name <> '体験生徒' ".
			"AND tbl_statement_detail.student_id = tbl_member.no ".
			"AND tbl_statement_detail.start_timestamp = ( SELECT MIN(start_timestamp) FROM tbl_statement_detail WHERE student_id = tbl_member.no ) ".
			"AND FROM_UNIXTIME(tbl_statement_detail.start_timestamp,'%Y/%m') = '$year/".sprintf('%02d',$month)."' ".
			"ORDER BY tbl_member.furigana ";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$list = $stmt->fetchAll(PDO::FETCH_ASSOC);
	break;

case 4:
	// 前月退会者
	$sql = "SELECT tbl_member.no FROM tbl_member, tbl_statement_detail ".
			"WHERE tbl_member.kind = 3 ".
			"AND tbl_member.del_flag = 2 ".
			"AND tbl_member.name <> '体験生徒' ".
			"AND tbl_statement_detail.student_id = tbl_member.no ".
			"AND tbl_statement_detail.start_timestamp = ( SELECT MAX(start_timestamp) FROM tbl_statement_detail WHERE student_id = tbl_member.no ) ".
			"AND FROM_UNIXTIME(tbl_statement_detail.start_timestamp,'%Y/%m') = '$year/".sprintf('%02d',$month2)."' ".
			"ORDER BY tbl_member.furigana ";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$list = $stmt->fetchAll(PDO::FETCH_ASSOC);
	break;
}

}
$list = array_unique($list,SORT_REGULAR);

switch ($type) {
case 1:	$title="{$year}年{$month}月<br>登録生徒リスト"; break;
case 2:	$title="{$year}年{$month}月<br>受講生徒リスト"; break;
case 3:	$title="{$year}年{$month}月<br>入会者リスト"; break;
case 4:	$title="{$year2}年{$month2}月<br>退会者リスト"; break;
case 5:	$title="{$year}年{$month}月<br>先生リスト"; break;
}

} catch (Exception $e) {
	// 処理を中断するほどの致命的なエラー
	array_push($errArray, $e->getMessage());
}

$lesson_list[0] = '全体';

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="robots" content="index,follow">
</head>
<body>
<div id="content" align="center">
<?php
	if (count($errArray) > 0) {
		foreach( $errArray as $error) {
?>
			<font color="red" size="3"><?= $error ?></font><br><br>
<?php
		}
?>
		<a href="./menu.php">メニューへ戻る</a>
		<br>
<?php
		exit();
	}


?>
<br><br><br><br>
<?= $title ?><br>
<?= $lesson_list[$lesson_id] ?>

<table width='150' border="1">
<?php
foreach ($list as $member) {
	echo "<tr><td>";
	echo $member_list[$member['no']]['name'];
	echo "</td></tr>";
}
?>
</table>
</div>

</body></html>

