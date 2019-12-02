<?php
//ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
require_once("./calculate_fees.php");
$result = check_user($db, "1");

$errArray = array();
$errFlag = 0;

try {

$year = $_POST["y"];
$month = $_POST["m"];

if (is_null($year) == true || $year == "") {
	$year = $_GET["y"];
}
if (is_null($month) == true || $month == "") {
	$month = $_GET["m"];
}
if ((is_null($year) == true || $year == "") || (is_null($month) == true || $month == "")) {
	throw new Exception('年月が不明です。');
}

$cond_name = "";
if (isset($_POST["cond_name"])) {
	$cond_name = trim($_POST["cond_name"]);
}

// 生徒情報（受講している教室と科目情報を含む）を取得
$student_list = array();
$param_array = array();
$value_array = array();
array_push($param_array, "tbl_member.kind = ?");
array_push($value_array, "3");
if ($cond_name != "") {
// 検索時
	array_push($param_array," tbl_member.name like concat('%',?,'%') ");
	// 20150817 修正
	//array_push($value_array,str_replace(" " , "　" , $cond_name));
	array_push($value_array,$cond_name);
}
// 20150816 ふりがなの50音順にソートする
$order_array = array("tbl_member.furigana asc");

$member_list = get_simple_member_list($db, $param_array, $value_array, $order_array);
if (count($member_list) == 0) {
	$errFlag = 1;
	throw new Exception('生徒が見つかりませんでした。');
}
/*
	//$absent_cond = "absent_flag = '1' or absent_flag = '2' or absent1_num > 0 or absent2_num > 0";
	$absent_cond = "absent_flag = '1'";
	$sql = "SELECT * FROM tbl_event where event_year=? and event_month=? and (".$absent_cond.")";
	$stmt = $db->prepare($sql);
	$stmt->bindParam(1, $tmp_year);
	$stmt->bindParam(2, $tmp_month);
	$tmp_year = $year;
	$tmp_month = $month;
	$stmt->execute();
	$absent_event_array = $stmt->fetchAll(PDO::FETCH_BOTH);
	foreach ($absent_event_array as $absent_event) {
  	//$absent_event_list[$absent_event["member_no"]] = array("absent1_flag"=>,"absent2_flag"=>);
	}
*/

	// ファミリーの場合も、マンツーマンと同じように扱う（仮）


//SELECT member_no, count(*) as absent1_flag_num FROM tbl_event,tbl_member WHERE event_year='2015' AND event_month='9' AND absent_flag = '1' AND tbl_member.member_no = tbl_event.member GROUP BY member_no ORDER BY tbl_member.furigana;

		//$absent_cond = "absent_flag = '1'";
		//$sql = "SELECT member_no, count(*) as absent1_flag_num FROM tbl_event WHERE event_year=? and event_month=? and absent_flag = '1' GROUP BY member_no";
		$sql = "SELECT tbl_event.member_no as member_no, tbl_member.name as member_name, count(*) as absent1_flag_num";
		$sql .= " FROM tbl_event,tbl_member";
		$sql .= " WHERE tbl_event.event_year=? AND tbl_event.event_month=? AND tbl_event.absent_flag = '1' AND tbl_member.no = tbl_event.member_no";
		$sql .= " GROUP BY tbl_event.member_no ORDER BY tbl_member.furigana";

		$sql = "SELECT tbl_event.member_no as member_no, tbl_member.name as member_name, count(*) as absent1_flag_num";
		$sql .= " FROM tbl_event,tbl_member";
		$sql .= " WHERE tbl_event.event_year=? AND tbl_event.event_month=? AND tbl_event.absent_flag = '1' AND tbl_member.no = tbl_event.member_no";
		$sql .= " GROUP BY tbl_event.member_no ORDER BY tbl_member.furigana";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(1, $tmp_year);
		$stmt->bindParam(2, $tmp_month);
		$tmp_year = $year;
		$tmp_month = $month;
		$stmt->execute();
		$absent1_array = $stmt->fetchAll(PDO::FETCH_BOTH);
		$absent1_flag_num_array = array();
		foreach ($absent1_array as $absent1) {
    	$absent1_flag_num_array[$absent1["member_no"]] = $absent1["absent1_flag_num"];
		}


//var_dump($absent1_flag_num_array);


		$sql = "SELECT tbl_event.member_no as member_no, count(*) as absent2_flag_num";
		$sql .= " FROM tbl_event,tbl_member";
		$sql .= " WHERE tbl_event.event_year=? AND tbl_event.event_month=? AND tbl_event.absent_flag = '2' AND tbl_member.no = tbl_event.member_no";
		$sql .= " GROUP BY tbl_event.member_no ORDER BY tbl_member.furigana";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(1, $tmp_year);
		$stmt->bindParam(2, $tmp_month);
		$tmp_year = $year;
		$tmp_month = $month;
		$stmt->execute();
		$absent2_array = $stmt->fetchAll(PDO::FETCH_BOTH);
		$absent2_flag_num_array = array();
		foreach ($absent2_array as $absent2) {
    	$absent2_flag_num_array[$absent2["member_no"]] = $absent2["absent2_flag_num"];
		}

//var_dump($absent2_flag_num_array);


		$sql = "SELECT tbl_event.member_no as member_no, count(*) as alternative_flag_num";
		$sql .= " FROM tbl_event,tbl_member";
		$sql .= " WHERE tbl_event.event_year=? AND tbl_event.event_month=? AND tbl_event.alternative_flag = '1' AND tbl_member.no = tbl_event.member_no";
		$sql .= " GROUP BY tbl_event.member_no ORDER BY tbl_member.furigana";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(1, $tmp_year);
		$stmt->bindParam(2, $tmp_month);
		$tmp_year = $year;
		$tmp_month = $month;
		$stmt->execute();
		$alternative_array = $stmt->fetchAll(PDO::FETCH_BOTH);
		$alternative_flag_num_array = array();
		foreach ($alternative_array as $alternative) {
    	$alternative_flag_num_array[$alternative["member_no"]] = $alternative["alternative_flag_num"];
		}


	$absent_list = array();
	$total_num_absent1_flag = 0;
	$total_num_absent2_flag = 0;
	$total_num_alternative_flag = 0;
	foreach ($member_list as $member_no => $member) {
		$tmp_absent1_flag_num = 0;
		$tmp_absent2_flag_num = 0;
		$tmp_alternative_flag_num = 0;
		if (isset($absent1_flag_num_array[$member_no]) && empty($absent1_flag_num_array[$member_no]) === false) {
    	$tmp_absent1_flag_num = $absent1_flag_num_array[$member_no];
			$total_num_absent1_flag = $total_num_absent1_flag + $tmp_absent1_flag_num;
		}
		if (isset($absent2_flag_num_array[$member_no]) && empty($absent2_flag_num_array[$member_no]) === false) {
    	$tmp_absent2_flag_num = $absent2_flag_num_array[$member_no];
			$total_num_absent2_flag = $total_num_absent2_flag + $tmp_absent2_flag_num;
		}
		if (isset($alternative_flag_num_array[$member_no]) && empty($alternative_flag_num_array[$member_no]) === false) {
    	$tmp_alternative_flag_num = $alternative_flag_num_array[$member_no];
			$total_num_alternative_flag = $total_num_alternative_flag + $tmp_alternative_flag_num;
		}
		if ($tmp_absent1_flag_num == 0 && $tmp_absent2_flag_num == 0) {
		//if ($tmp_absent1_flag_num == 0 && $tmp_absent2_flag_num == 0 && $tmp_alternative_flag_num == 0) {
		//if ($tmp_absent1_flag_num < 2 && $tmp_absent2_flag_num < 2) {
    	continue;
		}
		$absent_list[$member_no] = array("name"=>$member["name"],
															"absent1_flag_num"=>$tmp_absent1_flag_num,
															"absent2_flag_num"=>$tmp_absent2_flag_num,
															"alternative_flag_num"=>$tmp_alternative_flag_num,
															);
	}



$sql = "SELECT e.cal_summary, e.cal_evt_summary, e.cal_id, e.course_id, e.event_end_timestamp, e.event_start_timestamp, ".
		"e.grade, e.lesson_id, e.member_cal_name, e.member_no, e.recurringEvent, e.subject_id, e.trial_flag, ".
		"e.absent_flag, e.event_id, m.name, m.furigana, m.grade, e.grade as tgrade, e.place_id ".
		"FROM tbl_event e LEFT OUTER JOIN tbl_member m ".
		"on e.member_no=m.no where e.event_year=? and e.event_month=? ".
		"order by e.event_start_timestamp, e.cal_evt_summary";
$stmt = $db->prepare($sql);
$stmt->execute(array($year, $month));
$event_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($event_list as &$event) {
	
	$event["date"] = date("n月j日", $event['event_start_timestamp']);
	$event["time"] = date("H:i", $event['event_start_timestamp']) ." ～ ". date("H:i", $event['event_end_timestamp']);

			// 教室
			$lesson_name = $lesson_list[$event["lesson_id"]];
			// 科目
			if ($event["subject_id"] == "0") {
				$subject_name = "　";
			} else {
				if ($event["subject_id"] == 8) {
					$subject_name = "　";
				} else {
					$subject_name = $subject_list[$event["subject_id"]];
				}
			}
			// タイプ
			if ($event["course_id"] == "0") {
				$course_name = "";
			} else {
				$course_name = $course_list[$event["course_id"]]["course_name"];
			}

	$event["course_name"]  = $course_name;
	$event["lesson_name"]  = $lesson_name;
	$event["subject_name"] = $subject_name;
	
	foreach ($work_type_list as $key=>$work_type) {
		if (!$work_type) { continue; }
		if (strpos($event['cal_evt_summary'], $work_type)!==false) {
			$event["course_name"]  = '';
			$event["lesson_name"]  = $lesson_name;
			$event["subject_name"] = $work_type;
			$event["work_type"]    = $key;
			break;
		}
	}
	
	$name = $event["name"];
	$name0 = $name;
	if ($name=='体験生徒') {
		$name0 = $event['member_cal_name'];
		while (isset($attendStatusCal[$event['date']][$event['time']][$name])) {
			$name.='!';
		}
	}
	$attendStatusCal[$event['date']][$event['time']][$name] = '';
	$str0 = str_replace(array('　','（','）','：','︰'), array(' ','(',')',':',':'), $event['cal_evt_summary']);
	if (preg_match('/(グループ|Group)/iu',$str0) || preg_match('/(ファミリー|family)/iu',$str0)) {
		$ret = preg_match_all('/\((.*?)\)/u', $str0, $blocks);
		if (!$ret) { $blocks[1]=array($str0); }
	} else {
		$blocks[1]=array($str0);
	}
	if (!preg_match('/(ファミリー|family)/iu',$str0)) {
		foreach ($blocks[1] as $key=>$block) {
			$ret = preg_match( '/([^():]+?)様([A-Za-z ]+)?/u', $block, $name_cal );
			if (!$ret) { continue; }
			if (str_replace(' ','',$name0) != str_replace(' ','',$name_cal[1])) { continue; }
			$event['eng_name'] = $name_cal[2];
			if (preg_match('/^休み[1１]\s*:/u',$block)) { $attendStatusCal[$event['date']][$event['time']][$name] = '休み１'; }
			if (preg_match('/^休み[2２]\s*:/u',$block)) { $attendStatusCal[$event['date']][$event['time']][$name] = '休み２'; }
			if (preg_match('/^振替\s*:/u',$block))      { $attendStatusCal[$event['date']][$event['time']][$name] = '振替'; }
			if (preg_match('/:\s*当日/u',$block))      { $attendStatusCal[$event['date']][$event['time']][$name] .= '当日'; }
			if (preg_match('/:\s*休講/u',$block))      { $attendStatusCal[$event['date']][$event['time']][$name] .= '休講'; }
			if (preg_match('/^absent1\s*:/iu',$block))  { $attendStatusCal[$event['date']][$event['time']][$name] = 'Absent1'; }
			if (preg_match('/^absent2\s*:/iu',$block))  { $attendStatusCal[$event['date']][$event['time']][$name] = 'Absent2'; }
			if (preg_match('/^alternative\s*:/iu',$block)) { $attendStatusCal[$event['date']][$event['time']][$name] = 'make-up'; }
			if (preg_match('/^make.?up\s*:/iu',$block)) { $attendStatusCal[$event['date']][$event['time']][$name] = 'make-up'; }
			if (preg_match('/:\s*today/iu',$block))    { $attendStatusCal[$event['date']][$event['time']][$name] .= ' Today'; }
			if (preg_match('/:\s*No.*class/iu',$block)) { $attendStatusCal[$event['date']][$event['time']][$name] .= ' No class'; }
			if (preg_match('/:\s*規定回数以上/u',$block))    { $attendStatusCal[$event['date']][$event['time']][$name] .= '規定回数以上'; }
			if (preg_match('/:\s*over.*?limit/iu',$block))    { $attendStatusCal[$event['date']][$event['time']][$name] .= ' over limit'; }
		}
	} else {
		$allPreFix = '';
		if (preg_match('/^休み[1１]\s*:/u',$blocks[1][0])) { $allPreFix = '休み１'; }
		if (preg_match('/^休み[2２]\s*:/u',$blocks[1][0])) { $allPreFix = '休み２'; }
		if (preg_match('/^振替\s*:/u',$blocks[1][0]))     { $allPreFix = '振替'; }
		if (preg_match('/^absent1\s*:/iu',$blocks[1][0]))     { $allPreFix = 'Absent1'; }
		if (preg_match('/^absent2\s*:/iu',$blocks[1][0]))     { $allPreFix = 'Absent2'; }
		if (preg_match('/^alternative\s*:/iu',$blocks[1][0])) { $allPreFix = 'make-up'; }
		if (preg_match('/^make.?up\s*:/iu',$blocks[1][0]))    { $allPreFix = 'make-up'; }
		$allPostFix = '';
		if (preg_match('/ 様 .*:\s*当日/u',$blocks[1][0])) { $allPostFix = '当日'; }
		if (preg_match('/ 様 .*:\s*休講/u',$blocks[1][0])) { $allPostFix = '休講'; }
		if (preg_match('/ 様 .*:\s*today/iu',$blocks[1][0])) { $allPostFix = ' Today'; }
		if (preg_match('/ 様 .*:\s*No.*class/iu',$blocks[1][0])) { $allPostFix = ' No class'; }
		if (preg_match('/ 様 .*:\s*規定回数以上/u',$blocks[1][0])) { $allPostFix = '規定回数以上'; }
		if (preg_match('/ 様 .*:\s*over.*?limit/iu',$blocks[1][0])) { $allPostFix = ' over limit'; }
		$attendStatusCal[$event['date']][$event['time']][$name] = $allPreFix.$allPostFix;
		if (!$attendStatusCal[$event['date']][$event['time']][$name]) {
			switch ($event['absent_flag']) {
				case 1:	$attendStatusCal[$event['date']][$event['time']][$name] = '休み１'; break;
				case 2:	$attendStatusCal[$event['date']][$event['time']][$name] = '休み２'; break;
			}
		}
/*
		$str0 = $blocks[1][0];
		$ret = preg_match_all('/(\S+)/u', $str0, $blocks);
		if (!$ret) { $blocks[1]=array($str0); }
		$flag = 0;
		$event['eng_name'] = '';
		foreach ($blocks[1] as $key=>$block) {
			if ($block == '様') { $flag = 1; continue; }
			if ($flag == 0) {
				$name0 = $block;
				$tmp = preg_replace( "/(\s*休み[12１２]\s*:\s*|\s*振替\s*:\s*|:\s*当日|:\s*休講|:\s*規定回数以上)/u", "", $name0 );
				if ($tmp!==false) {$name0=$tmp;}
				$tmp = preg_replace( "/(\s*absent[12]\s*:\s*|\s*alternative\s*:\s*|\s*make.?up\s*:\s*|:\s*today|:\s*over.*?limit|:\s*no.*?class)/iu", "", $name0 );
				if ($tmp!==false) {$name0=$tmp;}
				if ($key==0) {
					$family_name = $name0;
				} else {
					$name0 = $family_name.' '.$name0;
					$attendStatusCal[$event['date']][$event['time']][$name0] = '';
					if ($allPreFix) { $attendStatusCal[$event['date']][$event['time']][$name0] = $allPreFix; }
					else if (preg_match('/^休み[1１]\s*:/u',$block)) { $attendStatusCal[$event['date']][$event['time']][$name0] = '休み１'; }
					else if (preg_match('/^休み[2２]\s*:/u',$block)) { $attendStatusCal[$event['date']][$event['time']][$name0] = '休み２'; }
					else if (preg_match('/^振替\s*:/u',$block))     { $attendStatusCal[$event['date']][$event['time']][$name0] = '振替'; }
					else if (preg_match('/^absent1\s*:/iu',$block))  { $attendStatusCal[$event['date']][$event['time']][$name0] = 'Absent1'; }
					else if (preg_match('/^absent2\s*:/iu',$block))  { $attendStatusCal[$event['date']][$event['time']][$name0] = 'Absent2'; }
					else if (preg_match('/^alternative\s*:/iu',$block)) { $attendStatusCal[$event['date']][$event['time']][$name0] = 'make-up'; }
					else if (preg_match('/^make.?up\s*:/iu',$block)) { $attendStatusCal[$event['date']][$event['time']][$name0] = 'make-up'; }
					if ($allPostFix) { $attendStatusCal[$event['date']][$event['time']][$name0] .= $allPostFix; }
					else if (preg_match('/:\s*当日/u',$block))      { $attendStatusCal[$event['date']][$event['time']][$name0] .= '当日'; }
					else if (preg_match('/:\s*休講/u',$block))      { $attendStatusCal[$event['date']][$event['time']][$name0] .= '休講'; }
					else if (preg_match('/:\s*today/iu',$block))    { $attendStatusCal[$event['date']][$event['time']][$name0] .= ' Today'; }
					else if (preg_match('/:\s*No.*class/iu',$block)) { $attendStatusCal[$event['date']][$event['time']][$name0] .= ' No class'; }
					else if (preg_match('/:\s*規定回数以上/u',$block)) { $attendStatusCal[$event['date']][$event['time']][$name0] .= '規定回数以上'; }
					else if (preg_match('/:\s*over.*?limit/iu',$block)) { $attendStatusCal[$event['date']][$event['time']][$name0] .= ' over limit'; }
				}
			} else {
				$event['eng_name'] .= $block.' ';
			}
		}
*/
	}
			
	$event["comment"] = $comment;
	$event['diff_hours'] = ($event["event_end_timestamp"] - $event["event_start_timestamp"]) /  (60*60);;
}
unset($event);

} catch (Exception $e) {
	// 処理を中断するほどの致命的なエラー
	array_push($errArray, $e->getMessage());
}




?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="robots" content="index,follow">
<link rel="stylesheet" type="text/css" href="./script/style.css">
<script type="text/javascript" src="./script/calender.js"></script>
<script type = "text/javascript">
<!--
function search_reset()
{
	document.search_form.cond_name = '';
	document.search_form.submit();
}
//-->
</script>
</head>
<body>
<div id="header">
	事務システム
</div>
<div id="content" align="center">
<h3>休み回数一覧</h3>

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
		<a href="./menu.php">メニューへ戻る</a><br>

<h3><?= $year ?>年<?= $month ?>月</h3>
<?php
if (count($absent_list) == 0) {
?>
休まれた生徒さんはいらっしゃいませんでした。

<?php
} else if (count($absent_list) > 0) {
?>
※休みがある生徒さんを、名前の50音順に表示します。<br>
※「休み1」は「休み1休講」を含みません。<br>
※「休み2」は「休み2当日」「休み2規定回数以上」を含みません。<br><br>
<table border="1" cellpadding="5">
<tr>
	<th>氏名</th><!--<th>休み１の回数</th><th>休み２の回数</th><th>振替の回数</th>-->
	<th>休み１</th><th>休み１休講</th><th>休み２</th><th>休み２当日</th><th>休み２規定回数以上</th>
</tr>
<?php
foreach ($absent_list as $absent) {
	$c_absent1=''; $c_absent2=''; $c_alternat=''; $c_absent1_no_class=''; $c_absent2_today=''; $c_absent2_over='';
	foreach ($attendStatusCal as $date=>$item1) {
	foreach ($item1 as $time=>$item2) {
		$name = $absent['name'];
		while (isset($item2[$name])) {
			switch ($item2[$name]) {
				case '休み１':							$c_absent1++; break;
				case '休み２':							$c_absent2++; break;
				case '振替':								$c_alternat++; break;
				case '休み１休講':					$c_absent1_no_class++; break;
				case '休み２当日':					$c_absent2_today++; break;
				case '休み２規定回数以上':	$c_absent2_over++; break;
				case 'Absent1':							$c_absent1++; break;
				case 'Absent2':							$c_absent2++; break;
				case 'make-up':							$c_alternat++; break;
				case 'Absent1 No class':		$c_absent1_no_class++; break;
				case 'Absent2 Today':				$c_absent2_today++; break;
				case 'Absent2 over limit':	$c_absent2_over++; break;
			}
			$name .= '!';
		}
	}}	
?>
	<tr>
		<td align="left"><?php echo $absent["name"]; ?></td>
<!--
		<td align="right"><?php echo $absent["absent1_flag_num"]."回"; ?></td>
		<td align="right"><?php echo $absent["absent2_flag_num"]."回"; ?></td>
-->
		<td align="right"><?php echo $c_absent1.""; ?></td>
		<td align="right"><?php echo $c_absent1_no_class.""; ?></td>
		<td align="right"><?php echo $c_absent2.""; ?></td>
		<td align="right"><?php echo $c_absent2_today.""; ?></td>
		<td align="right"><?php echo $c_absent2_over.""; ?></td>
		<!--<td align="right"><?php echo $absent["alternative_flag_num"].""; ?></td>-->
	</tr>
<?php
}
?>
<!--
<tr>
		<td align="left"><b>合計</b></td>
		<td align="right"><b><?php echo $total_num_absent1_flag."回"; ?></b></td>
		<td align="right"><b><?php echo $total_num_absent2_flag."回"; ?></b></td>
		<td align="right"><b><?php echo $total_num_alternative_flag."回"; ?></b></td>

</tr>
-->


</table>
<?php
}
?>


</div>

</body></html>

