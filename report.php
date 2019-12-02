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
$mnum = $_POST["mnum"];

if (is_null($year) == true || $year == "") {
	$year = $_GET["y"];
}
if (is_null($month) == true || $month == "") {
	$month = $_GET["m"];
}
if (is_null($mnum) == true || $mnum == "") {
	$mnum = $_GET["mnum"];
}
if ((is_null($year) == true || $year == "") || (is_null($month) == true || $month == "")) {
	throw new Exception('年月が不明です。');
}

if (!$mnum) { $mnum=1; }

// 先生一覧を取得
$teacher_list = get_teacher_list($db, array(), array(), array());

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

//$db->beginTransaction();

$tmp_teacher_list = array();
$tmp_teacher = array();

$report_list = array();
//var_dump($lesson_data);


$year0 = $year;
$month0 = $month;

for ($mindex=0;$mindex<$mnum;$mindex++) {

$total_teacher_num = 0;
$total_student_num = 0;
$total_lesson_hours = 0;
$total_lesson_fees = 0;
$total_salary_price = 0;

$year = $year0 - floor($mindex/12); $month = $month0 - ($mindex%12);
if ($month<1) { $month += 12; $year -= 1; }

$year1 = $year;
$month1 = $month+1;
if	($month1>12) { $year1++; $month1=1; }
$date1 = "$year-".sprintf('%02d',$month)."-11";
$date2 = "$year1-".sprintf('%02d',$month1)."-10";

$year2 = $year;
$month2 = $month-1;
if	($month2<1) { $year2--; $month2=12; }

$cons_tax_rate = get_cons_tax_rate($year, $month);

for ($lesson_id=1;$lesson_id<=4;$lesson_id++) {

	$teacher_num = 0;
	$student_num = 0;
	$lesson_hours = 0;
	$lesson_fees = 0;
	$salary_price = 0;

	// 授業データを取得
	$lesson_data = get_lesson_data($db, $year, $month, array('lesson_id=?'), array("$lesson_id"));

	foreach ($lesson_data as $one_lesson) {
		// 時間数
		$lesson_hours = $lesson_hours + $one_lesson["lesson_hours"];

		// 月謝金額（授業料金のみ）
		$lesson_fees = $lesson_fees + $one_lesson["lesson_fees"];
	}

	// 先生数
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
	$teacher_data = $stmt->fetchAll(PDO::FETCH_BOTH);
	$teacher_num = count($teacher_data);

	// 登録生徒数
	$sql = "SELECT tbl_member.no FROM tbl_member, tbl_fee ".
			"WHERE tbl_member.kind = 3 ".
			"AND tbl_fee.member_no = tbl_member.no ".
			"AND tbl_fee.lesson_id = $lesson_id ".
			"AND tbl_member.name <> '体験生徒' ".
			"AND ( tbl_member.del_flag = 0 AND LEFT(tbl_member.insert_timestamp,10) <= '$date2' ) ".
			"AND NOT ( tbl_member.del_flag = 2 AND LEFT(tbl_member.update_timestamp,10) < '$date2' ) ";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$student_now_num = count(array_unique($students,SORT_REGULAR));
	
	
	// 生徒数
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
	$student_data = $stmt->fetchAll(PDO::FETCH_BOTH);
	$student_num = count($student_data);

	// 入会者数
/*
	$sql = "SELECT tbl_member.no FROM tbl_member, tbl_fee ".
			"WHERE tbl_member.kind = 3 ".
			"AND tbl_member.del_flag = 0 ".
			"AND tbl_fee.member_no = tbl_member.no ".
			"AND tbl_fee.lesson_id = $lesson_id ".
			"AND tbl_member.name <> '体験生徒' ".
			"AND LEFT(tbl_member.insert_timestamp,10) BETWEEN '$date1' AND '$date2' ";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$student_new_num = count(array_unique($students,SORT_REGULAR));
*/
	$sql = "SELECT tbl_member.no FROM tbl_member, tbl_statement_detail ".
			"WHERE tbl_member.kind = 3 ".
			"AND tbl_member.del_flag = 0 ".
			"AND tbl_member.name <> '体験生徒' ".
			"AND tbl_statement_detail.student_id = tbl_member.no ".
			"AND tbl_statement_detail.lesson_id = $lesson_id ".
			"AND tbl_statement_detail.start_timestamp = ( SELECT MIN(start_timestamp) FROM tbl_statement_detail WHERE student_id = tbl_member.no ) ".
			"AND FROM_UNIXTIME(tbl_statement_detail.start_timestamp,'%Y/%m') = '$year/".sprintf('%02d',$month)."' ";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$student_new_num = count(array_unique($students,SORT_REGULAR));

	// 前月退会者数
/*
	$sql = "SELECT tbl_member.no FROM tbl_member, tbl_fee ".
			"WHERE tbl_member.kind = 3 ".
			"AND tbl_member.del_flag = 1 ".
			"AND tbl_fee.member_no = tbl_member.no ".
			"AND tbl_fee.lesson_id = $lesson_id ".
			"AND tbl_member.name <> '体験生徒' ".
			"AND LEFT(tbl_member.update_timestamp,10) BETWEEN '$date1' AND '$date2' ";
*/
	$sql = "SELECT tbl_member.no FROM tbl_member, tbl_statement_detail ".
			"WHERE tbl_member.kind = 3 ".
			"AND tbl_member.del_flag = 2 ".
			"AND tbl_member.name <> '体験生徒' ".
			"AND tbl_statement_detail.student_id = tbl_member.no ".
			"AND tbl_statement_detail.lesson_id = $lesson_id ".
			"AND tbl_statement_detail.start_timestamp = ( SELECT MAX(start_timestamp) FROM tbl_statement_detail WHERE student_id = tbl_member.no ) ".
			"AND FROM_UNIXTIME(tbl_statement_detail.start_timestamp,'%Y/%m') = '$year/".sprintf('%02d',$month2)."' ";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$student_old_num = count(array_unique($students,SORT_REGULAR));

	// 給与合計
	$salary_data = get_salary_data($db, $year, $month, array("tbl_teacher.lesson_id=?"), array($lesson_id));
	$salary_price = 0;
		foreach ($salary_data as $data) {
	 		$salary_price = $salary_price + $data["salary_price"];
		}

	// 合計
	$total_student_num += $student_num;
	$total_teacher_num += $teacher_num;
	$total_lesson_hours += $lesson_hours;
	$total_lesson_fees += $lesson_fees;
	$total_salary_price += $salary_price;

	$report_list[$lesson_id][$mindex] = array(
			"lesson_id"=>$lesson_id,
			"teacher_num"=>$teacher_num,
			"student_num"=>$student_num,"student_now_num"=>$student_now_num,"student_new_num"=>$student_new_num,"student_old_num"=>$student_old_num,
			"lesson_hours"=>$lesson_hours,
			"lesson_fees"=>$lesson_fees,
			"salary_price"=>$salary_price
			);
}

	// 先生数
	$sql = "SELECT ".
				"lesson_id as lesson_id, teacher_id as teacher_no ".
 				"FROM tbl_statement_detail ".
 				"WHERE FROM_UNIXTIME(start_timestamp, '%Y') = $year ".
				"AND  FROM_UNIXTIME(start_timestamp, '%m')+0 = $month ".
				"AND absent_flag <> '1' ".
				" GROUP BY teacher_id";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$teacher_data = $stmt->fetchAll(PDO::FETCH_BOTH);
	$teacher_num = count($teacher_data);

	// 登録生徒数
	$sql = "SELECT tbl_member.no FROM tbl_member ".
			"WHERE tbl_member.kind = 3 ".
			"AND tbl_member.name <> '体験生徒' ".
			"AND ( tbl_member.del_flag = 0 AND LEFT(tbl_member.insert_timestamp,10) <= '$date2' ) ".
			"AND NOT ( tbl_member.del_flag = 2 AND LEFT(tbl_member.update_timestamp,10) < '$date2' ) ";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$student_now_num = count(array_unique($students,SORT_REGULAR));
	
	// 生徒数
	$sql = "SELECT lesson_id, count(student_id) as num  ".
				"FROM tbl_statement_detail ".
				"WHERE FROM_UNIXTIME(start_timestamp, '%Y') = $year ".
				"AND  FROM_UNIXTIME(start_timestamp, '%m')+0 = $month ".
				"AND absent_flag <> '1' ".
				" GROUP BY student_id".
				" ORDER BY student_id";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$student_data = $stmt->fetchAll(PDO::FETCH_BOTH);
	$student_num = count($student_data);

	// 入会者数
	$sql = "SELECT tbl_member.no FROM tbl_member, tbl_statement_detail ".
			"WHERE tbl_member.kind = 3 ".
			"AND tbl_member.del_flag = 0 ".
			"AND tbl_member.name <> '体験生徒' ".
			"AND tbl_statement_detail.student_id = tbl_member.no ".
			"AND tbl_statement_detail.start_timestamp = ( SELECT MIN(start_timestamp) FROM tbl_statement_detail WHERE student_id = tbl_member.no ) ".
			"AND FROM_UNIXTIME(tbl_statement_detail.start_timestamp,'%Y/%m') = '$year/".sprintf('%02d',$month)."' ";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$student_new_num = count(array_unique($students,SORT_REGULAR));

	// 前月退会者数
	$sql = "SELECT tbl_member.no FROM tbl_member, tbl_statement_detail ".
			"WHERE tbl_member.kind = 3 ".
			"AND tbl_member.del_flag = 2 ".
			"AND tbl_member.name <> '体験生徒' ".
			"AND tbl_statement_detail.student_id = tbl_member.no ".
			"AND tbl_statement_detail.start_timestamp = ( SELECT MAX(start_timestamp) FROM tbl_statement_detail WHERE student_id = tbl_member.no ) ".
			"AND FROM_UNIXTIME(tbl_statement_detail.start_timestamp,'%Y/%m') = '$year/".sprintf('%02d',$month2)."' ";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$student_old_num = count(array_unique($students,SORT_REGULAR));

	// 給与合計
	$salary_data = get_salary_data($db, $year, $month, array("tbl_teacher.lesson_id=?"), array($lesson_id));
	$salary_price = 0;
		foreach ($salary_data as $data) {
	 		$salary_price = $salary_price + $data["salary_price"];
		}
	
	$report_list[0][$mindex] = array(
			"lesson_id"=>0,
			"teacher_num"=>$teacher_num,
			"student_num"=>$student_num,"student_now_num"=>$student_now_num,"student_new_num"=>$student_new_num,"student_old_num"=>$student_old_num,
			"lesson_hours"=>$total_lesson_hours,
			"lesson_fees"=>$total_lesson_fees,
			"salary_price"=>$total_salary_price
			);

//var_dump($report_list);


	// 生徒情報（受講している教室と科目情報を含む）を取得
	$student_list = array();
	$param_array = array();
	$value_array = array();
	$order_array = array();
	array_push($param_array, "tbl_member.kind = ?");
	array_push($value_array, "3");
	$all_student_flag = "1";	// 前生徒と現生徒を抽出
	$member_list = get_simple_member_list($db, $param_array, $value_array, $order_array, $all_student_flag);

	$last_total_price = 0;
	$total_price = 0;
	$lesson_total_price = 0;
	$textbook_total_price = 0;
	$consumption_tax_price = 0;
	$total_consumption_tax_price = 0;
	$season_class_total = 0;

	$price_list = array();
	$price_list['membership_fee'] = 0;
	$price_list['entrance_fee'] = 0;
	$price_list['consumption_tax_price'] = 0;
	$price_list['test_price'] = 0;
	$lesson_array = array();
	foreach ($lesson_list as $lesson_id => $lesson) {
  	$price_list['lesson'][$lesson_id] = 0;
  	$lesson_array[$lesson_id] = array("fees"=>0, "hours"=>0);
  	$price_list['textbook_price'][$lesson_id] = 0;
	}

	$sql = "SELECT * FROM tbl_statement where seikyu_year=? and seikyu_month=?";
	$stmt = $db->prepare($sql);
	$stmt->bindParam(1, $tmp_year);
	$stmt->bindParam(2, $tmp_month);
	$tmp_year = $year;
	$tmp_month = $month;
	$stmt->execute();
	$statement_array = $stmt->fetchAll(PDO::FETCH_BOTH);
	if (count($statement_array) < 1) {
		$message = '<br>対象年月の明細書を保存してから部門別合計を算出してください。<br>';
		$message .= '<a href="./save_statement.php?y='.$year.'&m='.$month.'">'.$year.'年'.$month.'月の明細書を保存する</a>';
    throw new Exception($message);
	}

	foreach ($member_list as $member_no => $member) {

		$tax_season_class_total = 0;

		$lesson_array = array();
		$consumption_tax_price = 0;

    $statement_list = array();
		$sql = "SELECT * FROM tbl_statement where member_no=? and  seikyu_year=? and seikyu_month=?";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(1, $tmp_member_no);
		$stmt->bindParam(2, $tmp_year);
		$stmt->bindParam(3, $tmp_month);
		$tmp_member_no = $member_no;
		$tmp_year = $year;
		$tmp_month = $month;
		$stmt->execute();
		$statement_array = $stmt->fetchAll(PDO::FETCH_BOTH);

		if (count($statement_array) > 0) {
			$statement_no = $statement_array[0]["statement_no"];
			$statement_list[$statement_no] = $statement_array[0];

			$sql = "SELECT * FROM tbl_statement_detail where statement_no=?";
			$stmt = $db->prepare($sql);
			$stmt->bindParam(1, $tmp_statement_no);
			$tmp_statement_no = $statement_no;
			$stmt->execute();
			$statement_event_array = $stmt->fetchAll(PDO::FETCH_BOTH);
			$statement_list[$statement_no]['event_list'] = $statement_event_array;
		}

		foreach ($statement_list as $statement_no => $statement) {

			// 20160127 消費税金額（生徒ごとに異なる）
			$consumption_tax_price = (int)str_replace(",","",$statement["consumption_tax_price"]);
			$total_consumption_tax_price = $total_consumption_tax_price + $consumption_tax_price;

			// 月会費
			$membership_fee = (int)str_replace(",","",$statement["membership_fee"]);
			// 20160705 消費税を月会費に振り分ける
			if ($consumption_tax_price > 0) {
				$tax_membership_fee = $membership_fee * $cons_tax_rate;
				$membership_fee = $membership_fee + $tax_membership_fee;
			}
			$price_list['membership_fee'] = $price_list['membership_fee'] + $membership_fee;
			$total_price = $total_price + $membership_fee;

			// 入会金
			$entrance_fee = (int)str_replace(",","",$statement["entrance_fee"]);
			// 20160705 消費税を入会金に振り分ける
			if ($consumption_tax_price > 0) {
				$tax_entrance_fee = $entrance_fee * $cons_tax_rate;
				$entrance_fee += $tax_entrance_fee;
			}
			$price_list['entrance_fee'] = $price_list['entrance_fee'] + $entrance_fee;
			$total_price = $total_price + $entrance_fee;

			// 科目別授業料
			$hours = 0;
			$fees = 0;
			$lesson_array = array();
			foreach ($lesson_list as $lesson_id => $lesson) {
		  	$lesson_array[$lesson_id] = array("fees"=>0, "hours"=>0);
			}

			$tax_season_class_total = 0;
			
			foreach ($statement["event_list"] as $event) {
				$lesson_id = $event["lesson_id"];
				$fees = str_replace(",","",$event["fees"]);
				$hours = str_replace(",","",$event["diff_hours"]);
				
				if (($event['course_id'] == 4) || 	// 夏期講習
						($event['course_id'] == 5) || 	// 冬期講習
						($event['course_id'] == 6)) { 	// 春期講習
						if ($consumption_tax_price > 0) {
							$tax_season_class = $fees * $cons_tax_rate;
							$fees += $tax_season_class;
							$tax_season_class_total += $tax_season_class;
						}
						$season_class_total += $fees ;
						$total_price += $fees ;
						continue;
				}
					$lesson_array[$lesson_id]['fees'] += $fees;
					$lesson_array[$lesson_id]['hours'] += $hours;
			}
		}

		// その他項目（これまでの模試代がこちらに含まれている）
		$tax_others = 0;
		$param_array = array("tbl_others.member_no=?", "tbl_others.year=?", "tbl_others.month=?");
		$value_array = array($member_no, $year, $month);
		$order_array = array("tbl_others.year, tbl_others.month, tbl_others.others_no");
		$others_list = get_others_list($db, $param_array, $value_array, $order_array);
		foreach ($others_list as $key => $others) {
			$others_price = (int)str_replace(",","",$others["price"]);
			if ($consumption_tax_price > 0 && $others["tax_flag"] == null) {
				$tax_others += floor($others_price * $cons_tax_rate);
				$others_price = $others_price + floor($others_price * $cons_tax_rate);
			}

			if ($others["kind"] == "1") {
			// 種類が授業の場合
				//20180208, 20180509 2018冬期講習春期講習特別対応
				if ($others["name"] == "冬期講習" || $others["name"] == "春期講習") {
					$season_class_total += $others_price;
				} else {
					$lesson_id = $others["lesson_id"];
					$price_list['lesson'][$lesson_id] = $price_list['lesson'][$lesson_id] + $others_price;
					$lesson_total_price = $lesson_total_price + $others_price;
				}
			} else if ($others["kind"] == "2") {
			// 種類がテキストの場合
				$lesson_id = $others["lesson_id"];
				$price_list['textbook_price'][$lesson_id] = $price_list['textbook_price'][$lesson_id] + $others["price"];
				$textbook_total_price = $textbook_total_price + $others_price;
			} else if ($others["kind"] == "3") {
			// 種類が模試の場合
				$price_list['test_price'] = $price_list['test_price'] + $others_price;
			// 20160103追加
			} else if ($others["kind"] == "4") {
			// 種類が月会費の場合
				$price_list['membership_fee'] = $price_list['membership_fee'] + $others_price;
			} else if ($others["kind"] == "5") {
			// 種類が入会金の場合
				$price_list['entrance_fee'] = $price_list['entrance_fee'] + $others_price;
			} else if ($others["kind"] == "6") {
			// 種類が督促金の場合
				$price_list['tokusoku'] = $price_list['tokusoku'] + $others_price;
			} else if ($others["kind"] == "7") {
			// 20170830 2017夏期講習の特別対応
				$season_class_total += $others_price;
			} else if ($others["kind"] == "8") {
			// 過払い調整
				continue;
			}

			$total_price = $total_price + $others_price;
		}

		// 20160705 消費税の振り分け
		if ($consumption_tax_price > 0) {
			// 授業時間が短い順に昇順にソート
			$result = uasort($lesson_array, "cmp_hours");
			$tmp_tax_total_lesson_fee = 0;
			foreach ($lesson_array as $lesson_id => $item) {
				$lesson_array[$lesson_id]['fees'] = floor($item['fees'] * (1.0 + $cons_tax_rate));
				$tmp_tax_total_lesson_fee += floor($item['fees'] * $cons_tax_rate);
      }
			// 部門ごとに消費税率をかけ切り捨てる
			// 切り捨てにより消費税合計金額より少ない場合は、授業時間が一番多い部門に加える
      $tax_total_lesson_fee = $consumption_tax_price - $tax_entrance_fee - $tax_membership_fee - $tax_others - $tax_season_class_total;
			if ($tmp_tax_total_lesson_fee < $tax_total_lesson_fee) {
				$lesson_array[$lesson_id]['fees'] += ($tax_total_lesson_fee - $tmp_tax_total_lesson_fee);
			}
 		}

		// 授業料分割払い（対象年月に支払った分だけでよい：確認済み）
		$param_array = array("tbl_divided_payment.member_no=?", "tbl_divided_payment_detail.payment_year=?", "tbl_divided_payment_detail.payment_month=?");
		$value_array = array($member_no, $year, $month);
		$order_array = array("tbl_divided_payment.payment_no","tbl_divided_payment_detail.time_no");
		$sql = 
			"SELECT
					tbl_divided_payment.payment_no as payment_no,
					tbl_divided_payment.member_no as member_no,
					tbl_divided_payment.year as year,
					tbl_divided_payment.month as month,
					tbl_divided_payment.lesson_id as lesson_id,
					tbl_divided_payment.type_id as type_id,
					tbl_divided_payment.time as time,
					tbl_divided_payment.payment_price as payment_price,
					tbl_divided_payment.memo as memo,
					tbl_divided_payment_detail.payment_no as payment_no,
					tbl_divided_payment_detail.time_no as time_no,
					tbl_divided_payment_detail.payment_year as payment_year,
					tbl_divided_payment_detail.payment_month as payment_month,
					tbl_divided_payment_detail.price as price
			 FROM tbl_divided_payment, tbl_divided_payment_detail";
		$sql .= " where tbl_divided_payment_detail.payment_no = tbl_divided_payment.payment_no";
	  if(count($param_array) > 0){
	    $sql .= " and " . join(" and ",$param_array);
	  }
	  if(count($order_array) > 0){
	    $sql .= "	order by " . join(" , ",$order_array);
	  }
		else {
			$sql .= "	order by tbl_divided_payment.year, tbl_divided_payment.month";
		}
		$stmt = $db->prepare($sql);
		$stmt->execute($value_array);
		$divided_payment_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach ($divided_payment_list as $divided_payment) {
			$lesson_id = $divided_payment["lesson_id"];
			$divided_payment_price = (int)str_replace(",","",$divided_payment["price"]);
			$price_list['lesson'][$lesson_id]		+= $divided_payment_price;
			$total_price												+= $divided_payment_price;
			$lesson_total_price									+= $divided_payment_price;
			if ($divided_payment["year"] == $year && $divided_payment["month"] == $month) {
				$price_list['lesson'][$lesson_id]	-= $divided_payment["payment_price"];
				$total_price 											-= $divided_payment["payment_price"];
				$lesson_total_price								-= $divided_payment["payment_price"];
			}
		}

		foreach ($lesson_array as $lesson_id => $item) {
			$price_list['lesson'][$lesson_id] += $item['fees'];
			$total_price = $total_price + $item['fees'];
			$lesson_total_price = $lesson_total_price + $item['fees'];
		}

		// テキスト代（模試代含む）
		// 20151009 テキスト購入テーブルに科目も登録してあるが集計には使わない
		$param_array = array("tbl_buying_textbook.member_no=?", "tbl_buying_textbook.year=?", "tbl_buying_textbook.month=?");
		$value_array = array($member_no, $year, $month);
		$order_array = array("tbl_buying_textbook.input_year", "tbl_buying_textbook.input_month", "tbl_buying_textbook.input_day", "tbl_buying_textbook.buying_no");
		$buying_textbook_list = get_buying_textbook_list($db, $param_array, $value_array, $order_array);
		foreach ($buying_textbook_list as $buying) {
			if ($buying["kind"] == "2") {
			// 種類がテキストの場合
				$lesson_id = $buying["lesson_id"];
				$price_list['textbook_price'][$lesson_id] = $price_list['textbook_price'][$lesson_id] + $buying["price"];
				$textbook_total_price = $textbook_total_price + $buying["price"];
			} else if ($buying["kind"] == "3") {
			// 種類が模擬テストの場合
				$price_list['test_price'] = $price_list['test_price'] + $buying["price"];
			}
			$total_price = $total_price + $buying["price"];
		}
	}


	$last_total_price = $total_price;

	$report_list[1][$mindex]["lesson_fees"] = $price_list['lesson'][1]+$season_class_total;
	$report_list[2][$mindex]["lesson_fees"] = $price_list['lesson'][2];
	$report_list[3][$mindex]["lesson_fees"] = $price_list['lesson'][3];
	$report_list[4][$mindex]["lesson_fees"] = $price_list['lesson'][4];
	
	$report_list[1][$mindex]["textbook_price"] = $price_list['textbook_price'][1];
	$report_list[2][$mindex]["textbook_price"] = $price_list['textbook_price'][2];
	$report_list[3][$mindex]["textbook_price"] = $price_list['textbook_price'][3];
	$report_list[4][$mindex]["textbook_price"] = $price_list['textbook_price'][4];
	
	$report_list[1][$mindex]["test_price"] = $price_list['test_price'];
	
	$report_list[0][$mindex]["lesson_fees"] = $lesson_total_price+$season_class_total;
	$report_list[0][$mindex]["textbook_price"] = $textbook_total_price;
	$report_list[0][$mindex]["test_price"] = $price_list['test_price'];
	$report_list[0][$mindex]["entrance_fee"] = $price_list['entrance_fee'];
	$report_list[0][$mindex]["membership_fee"] = $price_list['membership_fee'];
	$report_list[0][$mindex]["last_total_price"] = $last_total_price;


} // $mindex

} catch (Exception $e) {
	// 処理を中断するほどの致命的なエラー
	array_push($errArray, $e->getMessage());
}


function cmp_hours($a, $b) {
      if ($a["hours"] == $b["hours"]) {
		    return 0;
      }
      return ($a["hours"] > $b["hours"]) ? +1 : -1;
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
		<a href="./menu.php">メニューへ戻る</a><br>
<?php
if ($mnum == 1) {
	echo "<h3>{$year0}年{$month0}月</h3>";
} else {
	$year = $year0 - floor(($mnum-1)/12)*12; $month = $month0 - (($mnum-1)%12);
	if ($month<1) { $month += 12; $year -= 1; }
	echo "<h3>{$year}年{$month}月　～　{$year0}年{$month0}月</h3>";
}

echo "<iframe name='list_frame' width='200' height='100%' align='right'></iframe>";

foreach ($report_list as $lesson_id=>$data0) {
	if ($lesson_id==0) { continue; }
?>

<h3><?= $lesson_list[$data0[0]["lesson_id"]] ?></h3>

<table border="1">
<tr>
<th width="80px">年/月</th><th width="80px">時間</th>
<th width="80px">登録生徒数</th><th width="80px">入会者数</th><th width="80px">前月退会者数</th>
<th width="80px">受講生徒数</th><th width="80px">先生数</th>
<th width="80px">授業料</th><th width="80px">テキスト代</th><th width="80px">模擬試験代</th><th width="80px">売上</th>
<th width="80px">給与</th><th width="80px">利益</th>
</tr>
<?php
$t_lesson_hours = 0;
$t_student_new_num = 0;
$t_student_old_num = 0;
$t_lesson_fees = 0;
$t_textbook_price = 0;
$t_test_price = 0;
$t_total1 = 0;
$t_salary_price = 0;
$t_profit = 0;
for ($mindex=0;$mindex<$mnum;$mindex++) {
	$data = $data0[$mindex];
	$year = $year0 - floor($mindex/12); $month = $month0 - ($mindex%12);
	if ($month<1) { $month += 12; $year -= 1; }
	$total1=$data["lesson_fees"]+$data['textbook_price']+(($lesson_id==1)?number_format($data['test_price']):0);
?>
	<tr>
		<td align="right"><?= "$year/$month" ?></td>
		<td align="right"><?= number_format($data["lesson_hours"],2,".",",") ?></td>
		<td align="right"><?= number_format($data["student_now_num"]) ?></td>
		<td align="right"><a href="./report_student_list.php?y=<?=$year?>&m=<?=$month?>&t=3&l=<?=$lesson_id?>" target="list_frame"><?= number_format($data["student_new_num"]) ?></a></td>
		<td align="right"><a href="./report_student_list.php?y=<?=$year?>&m=<?=$month?>&t=4&l=<?=$lesson_id?>" target="list_frame"><?= number_format($data["student_old_num"]) ?></a></td>
		<td align="right"><?= number_format($data["student_num"]) ?></td>
		<td align="right"><?= number_format($data["teacher_num"]) ?></td>
		<td align="right"><a href="./report_student.php?y=<?=$year?>&m=<?=$month?>&l=<?=$data["lesson_id"]?>"><?= number_format($data["lesson_fees"]) ?></a></td>
		<td align="right"><?= number_format($data['textbook_price']) ?></td>
		<td align="right"><?= ($lesson_id==1)?number_format($data['test_price']):'-' ?></td>
		<td align="right"><?= number_format($total1) ?></td>
		<td align="right"><a href="./report_teacher.php?y=<?=$year?>&m=<?=$month?>&l=<?=$data["lesson_id"]?>">0</a></td>
		<td align="right"></td>
	</tr>
<?php
	$t_lesson_hours += $data["lesson_hours"];
	$t_student_new_num += $data["student_new_num"];
	$t_student_old_num += $data["student_old_num"];
	$t_lesson_fees += $data["lesson_fees"];
	$t_textbook_price += $data["textbook_price"];
	$t_test_price += $data["test_price"];
	$t_total1 += $total1;
	$t_salary_price += $data["salary_price"];
	$t_profit += $total1-$data["salary_price"];
}
if ($mnum>1) {
?>
<tr>
	<td align="right">計</td>
	<td align="right"><?= number_format($t_lesson_hours,2,".",",") ?></td>
	<td align="right">-</td>
	<td align="right"><?= number_format($t_student_new_num) ?></td>
	<td align="right"><?= number_format($t_student_old_num) ?></td>
	<td align="right">-</td>
	<td align="right">-</td>
	<td align="right"><?= number_format($t_lesson_fees) ?></td>
	<td align="right"><?= number_format($t_textbook_price) ?></td>
	<td align="right"><?= ($lesson_id==1)?number_format($t_test_price):'-' ?></td>
	<td align="right"><?= number_format($t_total1) ?></td>
	<td align="right"></td>
	<td align="right"></td>
</tr>
<?php
}
?>
</table>
<?php
}
?>

<br>
<h3>全体</h3>
<table border="1">
<tr>
<th width="80px">年/月</th><th width="80px">時間</th>
<th width="80px">登録生徒数</th><th width="80px">入会者数</th><th width="80px">前月退会者数</th>
<th width="80px">受講生徒数</th><th width="80px">先生数</th>
<th width="80px">授業料</th><th width="80px">テキスト代</th><th width="80px">模擬試験代</th>
<th width="80px">入会金</th><th width="80px">月会費</th><th width="80px">売上</th>
<th width="80px">給与</th><th width="80px">利益</th>
</tr>
<?php
$t_lesson_hours = 0;
$t_student_new_num = 0;
$t_student_old_num = 0;
$t_lesson_fees = 0;
$t_textbook_price = 0;
$t_test_price = 0;
$t_entrance_fee = 0;
$t_membership_fee = 0;
$t_total1 = 0;
$t_salary_price = 0;
$t_profit = 0;
for ($mindex=0;$mindex<$mnum;$mindex++) {
	$data = $report_list[0][$mindex];
	$year = $year0 - floor($mindex/12); $month = $month0 - ($mindex%12);
	if ($month<1) { $month += 12; $year -= 1; }
	$total1=$data["lesson_fees"]+$data['textbook_price']+(($lesson_id==1)?number_format($data['test_price']):0);
?>
	<tr>
		<td align="right"><?= "$year/$month" ?></td>
		<td align="right"><?= number_format($data["lesson_hours"],2,".",",") ?></td>
		<td align="right"><?= number_format($data["student_now_num"]) ?></td>
		<td align="right"><a href="./report_student_list.php?y=<?=$year?>&m=<?=$month?>&t=3&l=0" target="list_frame"><?= number_format($data["student_new_num"]) ?></a></td>
		<td align="right"><a href="./report_student_list.php?y=<?=$year?>&m=<?=$month?>&t=4&l=0" target="list_frame"><?= number_format($data["student_old_num"]) ?></a></td>
		<td align="right"><?= number_format($data["student_num"]) ?></td>
		<td align="right"><?= number_format($data["teacher_num"]) ?></td>
		<td align="right"><a href="./report_student.php?y=<?=$year?>&m=<?=$month?>&l=0"><?= number_format($data["lesson_fees"]) ?></a></td>
		<td align="right"><?= number_format($data['textbook_price']) ?></td>
		<td align="right"><?= number_format($data['test_price']) ?></td>
		<td align="right"><?= number_format($data['entrance_fee']) ?></td>
		<td align="right"><?= number_format($data['membership_fee']) ?></td>
		<td align="right"><?= number_format($data["last_total_price"]) ?></td>
		<td align="right"><a href="./report_teacher.php?y=<?=$year?>&m=<?=$month?>&l=0">0</a></td>
		<td align="right"></td>
	</tr>
<?php
	$t_lesson_hours += $data["lesson_hours"];
	$t_student_new_num += $data["student_new_num"];
	$t_student_old_num += $data["student_old_num"];
	$t_lesson_fees += $data["lesson_fees"];
	$t_textbook_price += $data["textbook_price"];
	$t_test_price += $data["test_price"];
	$t_entrance_fee += $data["entrance_fee"];
	$t_membership_fee += $data["membership_fee"];
	$t_total1 += $data["last_total_price"];
	$t_salary_price += $data["salary_price"];
	$t_profit += $data["last_total_price"]-$data["salary_price"];
}
if ($mnum>1) {
?>
<tr>
	<td align="right">計</td>
	<td align="right"><?= number_format($t_lesson_hours,2,".",",") ?></td>
	<td align="right">-</td>
	<td align="right"><?= number_format($t_student_new_num) ?></td>
	<td align="right"><?= number_format($t_student_old_num) ?></td>
	<td align="right">-</td>
	<td align="right">-</td>
	<td align="right"><?= number_format($t_lesson_fees) ?></td>
	<td align="right"><?= number_format($t_textbook_price) ?></td>
	<td align="right"><?= number_format($t_test_price) ?></td>
	<td align="right"><?= number_format($t_entrance_fee) ?></td>
	<td align="right"><?= number_format($t_membership_fee) ?></td>
	<td align="right"><?= number_format($t_total1) ?></td>
	<td align="right"></td>
	<td align="right"></td>
</tr>
<?php
}
?>
</table>
<br><br>
<table>
<tr><td>
（注意）<br>
・部門別の登録生徒数、入会者数、退会者数、先生数は重複を含みます。<br>
・授業料は期間講習を含みます。<br>
・現在、給与計算は未実装です。<br>
</td></tr>
</table>
</div>

</body></html>

