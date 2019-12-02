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

	// 先生一覧を取得
	$param_array = array();
	$value_array = array();
//	if ($lesson_id != "0") {
//		$param_array = array("tbl_teacher.lesson_id=?");
//		$value_array = array($lesson_id);
//	}
	$teacher_list = get_teacher_list($db, $param_array, $value_array,  array("tbl_teacher.furigana"));
	//$teacher_list = get_teacher_list($db, array(), array(), array());
	if (count($teacher_list) == 0) {
		throw new Exception('先生リストが取得できませんでした。'.__LINE__);
	}

	$teacher_array = array();

	// 授業時間
	$param_array = array();
	$value_array = array();
	if ($lesson_id != "0") {
		$param_array = array("view_lesson.lesson_id=?");
		$value_array = array($lesson_id);
	}
	$lesson_data = get_lesson_data($db, $year, $month, $param_array, $value_array);
	//var_dump($lesson_data);
	if ($lesson_data === false) {
		$message = '<br>対象年月の授業データを取得できませんでした。'.__LINE__.'<br>';
		$message .= '<a href="./save_statement.php?y='.$year.'&m='.$month.'">'.$year.'年'.$month.'月の請求データを作成する</a>';
	  throw new Exception($message);
	}
	$total_work_hours = 0;
	$total_lesson_fees = 0;
	$total_rieki_price = 0;
	foreach ($lesson_data as $data) {
//var_dump($data["lesson_hours"]);
		if (isset($teacher_array[$data["teacher_id"]]) === false) {
			$teacher_array[$data["teacher_id"]] = array("lesson_id"=>"", "no"=>"", "name"=>"", "furigana"=>"", "work_hours"=>0, "lesson_fees"=>0, "salary_price"=>0);
		}
		$teacher_array[$data["teacher_id"]]["lesson_id"] = $data["lesson_id"];
		$teacher_array[$data["teacher_id"]]["no"] = $data["teacher_id"];
		$teacher_array[$data["teacher_id"]]["name"] = $teacher_list[$data["teacher_id"]]["name"];
		$teacher_array[$data["teacher_id"]]["furigana"] = $teacher_list[$data["teacher_id"]]["furigana"];
		$teacher_array[$data["teacher_id"]]["work_hours"] = $teacher_array[$data["teacher_id"]]["work_hours"] + $data["lesson_hours"];
		$teacher_array[$data["teacher_id"]]["lesson_fees"] = $teacher_array[$data["teacher_id"]]["lesson_fees"] + $data["lesson_fees"];
		$total_work_hours += $data["lesson_hours"];
		$total_lesson_fees += $data["lesson_fees"];
	}

	// 給与合計
	$param_array = array();
	$value_array = array();
	if ($lesson_id != "0") {
		$param_array = array("tbl_teacher.lesson_id=?");
		$value_array = array($lesson_id);
	}
	$salary_data = get_salary_data($db, $year, $month, $param_array, $value_array);
	//if (count($salary_data) == 0) {
	//	throw new Exception('給与データが取得できませんでした。'.__LINE__);
	//}
	$total_salary_price = 0;
	//var_dump($salary_data);
	foreach ($salary_data as $data) {
		if (isset($teacher_array[$data["teacher_no"]]) === false) {
			$teacher_array[$data["teacher_no"]] = array("lesson_id"=>"", "no"=>"", "name"=>"", "furigana"=>"", "work_hours"=>0, "lesson_fees"=>0, "salary_price"=>0);
		}
		$teacher_array[$data["teacher_no"]]["salary_price"] = $teacher_array[$data["teacher_no"]]["salary_price"] + $data["salary_price"];
		$total_salary_price += $data["salary_price"];
	}

	// 先生名でソート
	//$result = usort($teacher_array, "cmp_teacher_no");
	$result = usort($teacher_array, "cmp_teacher_furigana");

	$total_rieki_price = $total_lesson_fees - $total_salary_price;

} catch (Exception $e) {
	// 処理を中断するほどの致命的なエラー
	array_push($errArray, $e->getMessage());
}

function cmp_teacher_no($a, $b) {
	if ($a["no"] == $b["no"]) {
		return 0;
	}
	return ($a["no"] > $b["no"]) ? +1 : -1;
}

function cmp_teacher_furigana($a, $b) {
	if ($a["lesson_id"] == $b["lesson_id"]) {
		if ($a["furigana"] == $b["furigana"]) {
			return 0;
		}
		return ($a["furigana"] > $b["furigana"]) ? +1 : -1;
		}
	return ($a["lesson_id"] > $b["lesson_id"]) ? +1 : -1;
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
<th width="80px">表示順</th><th width="80px">部門</th><th width="150px">先生名</th><th width="80px">授業時間</th><th width="80px">売上</th><th width="80px">給料</th><th width="80px">利益</th>
</tr>
<?php
$i=1;
foreach ($teacher_array as $no=>$data) {
?>
	<tr>
		<td align="center"><?= $i ?></td>
		<td align="center"><?= $lesson_list[$data["lesson_id"]] ?></td>
		<td align="left"><?= $data["name"] ?></td>
		<td align="right"><?= number_format($data["work_hours"],2,".",","); ?></td>
		<td align="right"><?= number_format($data["lesson_fees"]); ?></td>
		<td align="right"><?= number_format($data["salary_price"]); ?></td>
		<td align="right"><?= number_format($data["lesson_fees"]-$data["salary_price"]); ?></td>
	</tr>
<?php
	$i++;
}
?>
	<tr>
		<td align="right"></td>
		<td align="right"></td>
		<td align="right">計</td>
		<td align="right"><?= number_format($total_work_hours,2,".",","); ?></td>
		<td align="right"><?= number_format($total_lesson_fees); ?></td>
		<td align="right"><?= number_format($total_salary_price); ?></td>
		<td align="right"><?= number_format($total_rieki_price); ?></td>
	</tr>
</table>


</div>

</body></html>

