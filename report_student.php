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
	$lesson_id = $_POST["l"];

	if (is_null($year) == true || $year == "") {
		$year = $_GET["y"];
	}
	if (is_null($month) == true || $month == "") {
		$month = $_GET["m"];
	}
	if (is_null($lesson_id) == true || $lesson_id == "") {
		$lesson_id = $_GET["l"];
	}

	if ((is_null($year) == true || $year == "") || (is_null($month) == true || $month == "")) {
		throw new Exception('年月が不明です。');
	}
	if (is_null($lesson_id) == true || $lesson_id == "") {
		throw new Exception('部門が不明です。');
	}

	// 生徒一覧を取得
	$student_list = array();
	$param_array = array();
	$value_array = array();
	array_push($param_array, "tbl_member.kind = ?");
	array_push($value_array, "3");
	array_push($param_array, "name <> ?");
	array_push($value_array, "体験生徒");
	//if ($cond_name != "") {
	// 検索時
	//	array_push($param_array," tbl_member.name like concat('%',?,'%') ");
	//	array_push($value_array,$cond_name);
	//}
	// 20150816 ふりがなの50音順にソートする
	$order_array = array("tbl_member.furigana asc");
	$member_list = get_simple_member_list($db, $param_array, $value_array, $order_array);
	if (count($member_list) == 0) {
		$errFlag = 1;
		throw new Exception('生徒が見つかりませんでした。');
	}

	$param_array = array();
	$value_array = array();
	if ($lesson_id != "0") {
		$param_array = array("tbl_statement_detail.lesson_id=?");
		$value_array = array($lesson_id);
	}
	$student_data = get_student_data($db, $year, $month, $param_array, $value_array);
	//var_dump($student_data);
	if ($student_data === false) {
		$message = '<br>対象年月の請求データを作成してください。<br>';
		$message .= '<a href="./save_statement.php?y='.$year.'&m='.$month.'">'.$year.'年'.$month.'月の請求データを作成する</a>';
	  throw new Exception($message);
	}

	$total_lesson_hours = 0;
	$total_lesson_fees = 0;
	$student_array = array();
	foreach ($student_data as $data) {
		if (isset($student_array[$data["lesson_id"]][$data["no"]]) === false) {
			$student_array[$data["lesson_id"]][$data["no"]] = array("lesson_id"=>$data["lesson_id"], "student_no"=>$data["no"], "lesson_hours"=>0, "lesson_fees"=>0);
		}
		$student_array[$data["lesson_id"]][$data["no"]]["lesson_hours"] = $student_array[$data["lesson_id"]][$data["no"]]["lesson_hours"] + $data["lesson_hours"];
		$student_array[$data["lesson_id"]][$data["no"]]["lesson_fees"] = $student_array[$data["lesson_id"]][$data["no"]]["lesson_fees"] + $data["lesson_fees"];
		$total_lesson_hours += $data["lesson_hours"];
		$total_lesson_fees += $data["lesson_fees"];
	}
	//var_dump($student_array);
} catch (Exception $e) {
	// 処理を中断するほどの致命的なエラー
	array_push($errArray, $e->getMessage());
}


function get_student_data(&$db, $year, $month, $param_array=array(), $value_array=array()) {
	try {
		$sql = "SELECT ".
					"tbl_statement_detail.lesson_id, ".
					"tbl_member.no, ".
					"tbl_member.name, ".
					"FROM_UNIXTIME(tbl_statement_detail.start_timestamp, '%Y') as year, ".
					"FROM_UNIXTIME(tbl_statement_detail.start_timestamp, '%m')+0 as month, ".
					"FROM_UNIXTIME(tbl_statement_detail.start_timestamp, '%d')+0 as day, ".
					"tbl_statement_detail.start_timestamp, ".
					"tbl_statement_detail.end_timestamp, ".
					"tbl_statement_detail.fees as lesson_fees, ".
					"(tbl_statement_detail.end_timestamp-tbl_statement_detail.start_timestamp)/(60*60) as lesson_hours ".
					"FROM tbl_statement_detail, tbl_member ".
					"WHERE tbl_statement_detail.student_id = tbl_member.no ".
					"AND FROM_UNIXTIME(tbl_statement_detail.start_timestamp, '%Y') = ? ".
					"AND FROM_UNIXTIME(tbl_statement_detail.start_timestamp, '%m')+0 = ? ";
		if(count($param_array) > 0){
	  	$sql .= " AND " . join(" AND ",$param_array);
	  }
    $sql .= "AND tbl_statement_detail.absent_flag <> '1' ".
					"ORDER BY tbl_statement_detail.lesson_id, tbl_member.furigana asc";
//echo $sql;
		$stmt = $db->prepare($sql);
		$tmp_value_array = array_merge(array($year, $month),$value_array);
//var_dump($tmp_value_array);
		$stmt->execute($tmp_value_array);
		$student_data = $stmt->fetchAll(PDO::FETCH_BOTH);
		if (count($student_data) < 1) {
			return false;
	  }
	} catch (Exception $e) {
		//array_push($errArray, $e->getMessage());
//var_dump($e);
  	return false;
	}
	if (count($student_data) < 1) {
		$student_data = array();
	}
	return $student_data;
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="robots" content="index,follow">
<link rel="stylesheet" type="text/css" href="./script/style.css">
<script type="text/javascript" src="./script/calender.js"></script>
<script type = "text/javascript">
</script>
</head>
<body>
<div id="header">
	事務システム
</div>
<div id="content" align="center">
<h3></h3>

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

<!--<a href="./menu.php">メニューへ戻る</a><br>-->
<a href="./report.php?y=<?=$year?>&m=<?=$month?>">レポートのトップへ戻る</a><br>

<h3><?= $year ?>年<?= $month ?>月</h3>
<?php
//$lesson_name = "";
//if (count($report_list) > 0) {
//	$lesson_name = $lesson_list[$student_data[0]["lesson_id"]];
//}
?>
<table border="1">
<tr>
<th width="80px">表示順</th><th width="80px">部門</th><th width="150px">生徒名</th><th width="80px">授業時間</th><th width="80px">授業料金</th>
</tr>
<?php
$i=1;
foreach ($student_array as $lesson_id => $data_array) {
	foreach ($data_array as $student_no => $data) {
?>
	<tr>
		<td align="center"><?= $i ?></td>
		<td align="center"><?= $lesson_list[$data["lesson_id"]] ?></td>
		<td align="left"><?= $member_list[$data["student_no"]]["name"] ?></td>
		<td align="right"><?= number_format($data["lesson_hours"],2,".",","); ?></td>
		<td align="right"><?= number_format($data["lesson_fees"]); ?></td>
	</tr>
<?php
		$i++;
	}
}
?>
	<tr>
		<td align="right"></td>
		<td align="right"></td>
		<td align="right">計</td>
		<td align="right"><?= number_format($total_lesson_hours,2,".",",") ?></td>
		<td align="right"><?= number_format($total_lesson_fees) ?></td>
	</tr>
</table>


</div>

</body></html>

