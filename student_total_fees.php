<?php
//ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
require_once("./calculate_fees.php");
$result = check_user($db, "1");

set_time_limit(0);

$errArray = array();
$errFlag = 0;

$student_id = $_POST['no'];

try {

$student_list = array();
$param_array = array("tbl_member.kind = ?");
$value_array = array("3");
$order_array = array("tbl_member.furigana asc");
if ($student_id) {
	$param_array[] = "tbl_member.no = ?";
	$value_array[] = "$student_id"; 
}
$member_list = get_simple_member_list($db, $param_array, $value_array, $order_array, 1);

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="robots" content="index,follow">
<link rel="stylesheet" type="text/css" href="./script/style.css">
<script type="text/javascript" src="./script/calender.js"></script>
<script type = "text/javascript">
<!--
//-->
</script>
</head>
<body>
<div id="header">
	事務システム
</div>
<div id="content" align="center">
<h3>生徒別受講料合計</h3>

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
		<a href="./menu.php">メニューへ戻る</a>
<br><br>
<div class="menu_box">
＊塾、英会話、ピアノ、習い事は受講料、月謝、テキスト代などを含みます。<br>
＊期間講習・土日講習、模試代は塾に含まれます。<br>
＊2016/04以前の入会は全て2016/04で表示されます。<br>
＊古い生徒の入会金はデータベースに記録されていないため０表示です。<br>
</div>
<table border="1" cellpadding="5">
<tr>
<th rowspan=2></th><th rowspan=2>氏名</th><th rowspan=2>入会月</th><th rowspan=2>退会月</th><th rowspan=2>請求総額</th>
<th colspan=7>部門別受講料</th>
</tr>
<tr>
<th>塾</th><th>英会話</th><th>ピアノ</th><th>習い事</th><th>入会金</th><th>月会費</th><th>督促金</th>
</tr>
<?php
foreach ($member_list as $member_no => $member) {

//$log_member_no = $member_no;
	
	$last_total_price = 0;
	$total_price = 0;
	$lesson_total_price = 0;
	$monthly_total_price = 0;
	$textbook_total_price = 0;
	$entrance_fee_total_price = 0;
	$consumption_tax_price = 0;
	$total_consumption_tax_price = 0;
	$season_class_total = 0;
	$price_list = array();
	$price_list['membership_fee'] = 0;
	$price_list['entrance_fee'] = 0;
	$price_list['consumption_tax_price'] = 0;
	$price_list['test_price'] = 0;
	$price_list['tokusoku'] = 0;
	$lesson_array = array();
	foreach ($lesson_list as $lesson_id => $lesson) {
  	$price_list['lesson'][$lesson_id] = 0;
  	$lesson_array[$lesson_id] = array("fees"=>0, "hours"=>0);
  	$price_list['textbook_price'][$lesson_id] = 0;
	}

	$row_no++;
	$join_month = get_student_join_month($db, $member_no);
	
	if ($member['del_flag']==2) {
		$sql = "SELECT FROM_UNIXTIME(MAX(start_timestamp),'%Y/%m') FROM tbl_statement_detail WHERE student_id = \"{$member_no}\" ";
		$stmt = $db->query($sql);
		$rslt = $stmt->fetch(PDO::FETCH_NUM);
		$leave_month = $rslt[0];
	} else {
		$leave_month = '';
	}
	$sql = "SELECT SUM(grand_total_price) FROM tbl_statement WHERE member_no='$member_no'";
	$stmt = $db->query($sql);
	$rslt = $stmt->fetch(PDO::FETCH_NUM);
	$grand_total = $rslt[0];

		$tax_season_class_total = 0;

		$lesson_array = array();
		$consumption_tax_price = 0;
		
    $statement_list = array();
		$sql = "SELECT * FROM tbl_statement where member_no=?";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(1, $tmp_member_no);
		$tmp_member_no = $member_no;
		$stmt->execute();
		$statement_array = $stmt->fetchAll(PDO::FETCH_BOTH);

if ($row_no>0) {
		foreach ($statement_array as $statement) {
			$statement_no = $statement["statement_no"];
			$statement_list[$statement_no] = $statement;

			$sql = "SELECT * FROM tbl_statement_detail where statement_no=?";
			$stmt = $db->prepare($sql);
			$stmt->bindParam(1, $tmp_statement_no);
			$tmp_statement_no = $statement_no;
			$stmt->execute();
			$statement_event_array = $stmt->fetchAll(PDO::FETCH_BOTH);
			$statement_list[$statement_no]['event_list'] = $statement_event_array;
if ($member_no == $log_member_no)	{var_dump($statement_event_array);echo "<BR>";}
		}

		foreach ($statement_list as $statement_no => $statement) {

			$year = $statement['seikyu_year']; $month = $statement['seikyu_month'];
			$cons_tax_rate = get_cons_tax_rate($year, $month);
			
			$total_price = 0;

			// 20160127 消費税金額（生徒ごとに異なる）
			$consumption_tax_price = (int)str_replace(",","",$statement["consumption_tax_price"]);
			//$price_list['consumption_tax_price'] = $price_list['consumption_tax_price'] + $consumption_tax_price;
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
			//$total_price = $total_price + $entrance_fee_price;
			$entrance_fee = (int)str_replace(",","",$statement["entrance_fee"]);
			// 20160705 消費税を入会金に振り分ける
			if ($consumption_tax_price > 0) {
				$tax_entrance_fee = $entrance_fee * $cons_tax_rate;
				$entrance_fee += $tax_entrance_fee;
			}
			$price_list['entrance_fee'] = $price_list['entrance_fee'] + $entrance_fee;
			//$entrance_fee_total_price += $entrance_fee;
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
						if ($member["tax_flag"] == "1") {
							$tax_season_class = $fees * $cons_tax_rate;
							$fees += $tax_season_class;
							$tax_season_class_total += $tax_season_class;
						}
						$season_class_total += $fees ;
						$lesson_array[$lesson_id]['hours'] += $hours;
if ($member_no == $log_member_no)	echo "{$event['date']} fees2 $fees<BR>";
						$total_price += $fees ;
						continue;
				}
				//if (array_key_exists($lesson_id, $price_list['lesson']) === true) {
				// すでに科目がprice_listにあるとき
					//$price_list['lesson'][$lesson_id]['fees'] += $fees;
					//$price_list['lesson'][$lesson_id]['hours'] += $hours;
if ($member_no == $log_member_no)	echo "{$event['date']} fees1 $fees<BR>";
//if ($lesson_id==4) echo "{$lesson_array[$lesson_id]['fees']} += {$fees}<br>";
					$lesson_array[$lesson_id]['fees'] += $fees;
					$lesson_array[$lesson_id]['hours'] += $hours;
					//$total_price = $total_price + $fees;
					//$lesson_total_price = $lesson_total_price + $fees;
				//} else {
				// まだ科目がprice_listにないとき
				//	$price_list['lesson'][$lesson_id] = $fees;
				//}
				//$fees = 0;
			}

		// 月謝
		$calculator = new calculate_fees();
		$result = $calculator->calculate($member_no, $year, $month);
		if ($result == false) {
				array_push($errArray, "{$member['name']}: 月謝エラーが発生しました。");
		} else {
			$lesson_detail_list = $calculator->get_lesson_detail_list();
			foreach ($lesson_array as $lesson_id => $item) {
				$tmp_index= array_search($lesson_id,array_column($lesson_detail_list,'lesson_id'));
//if ($lesson_id==4 && $tmp_index !== false) echo "{$lesson_array[$lesson_id]['monthly_fees']} += {$lesson_detail_list[$tmp_index]['monthly_fees_total']}<br>";
				if ($tmp_index !== false) $lesson_array[$lesson_id]['monthly_fees'] += $lesson_detail_list[$tmp_index]['monthly_fees_total'];
			}
if ($member_no == $log_member_no)	{var_dump($lesson_array);echo "<BR>";}
		}
		unset($calculator);


		// その他項目（これまでの模試代がこちらに含まれている）
		$tax_others = 0;
		$param_array = array("tbl_others.member_no=?", "tbl_others.year=?", "tbl_others.month=?");
		$value_array = array($member_no, $year, $month);
		$order_array = array("tbl_others.year, tbl_others.month, tbl_others.others_no");
		$others_list = get_others_list($db, $param_array, $value_array, $order_array);
		foreach ($others_list as $key => $others) {
			$others_price = (int)str_replace(",","",$others["price"]);
			if ($member["tax_flag"] == "1" && $others["tax_flag"] == null) {
if ($member_no == $log_member_no)	{var_dump($others);echo "<BR>";}	
				$tax_others += floor($others_price * $cons_tax_rate);
				$others_price = $others_price + floor($others_price * $cons_tax_rate);
			}

			if ($others["kind"] == "1") {
				//20180208, 20180509 2018冬期講習春期講習特別対応
				if ($others["name"] == "冬期講習" || $others["name"] == "春期講習") {
					$season_class_total += $others_price;
				} else {
			// 種類が授業の場合
				$lesson_id = $others["lesson_id"];
				$price_list['lesson'][$lesson_id] = $price_list['lesson'][$lesson_id] + $others_price;
				$lesson_total_price = $lesson_total_price + $others_price;
				}
			} else if ($others["kind"] == "2") {
			// 種類がテキストの場合
				//$price_list['textbook_price'] = $price_list['textbook_price'] + $others_price;
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
if ($member_no == $log_member_no)	echo "others $others_price<BR>";
		}

		if ($year<2019 || ($year==2019 && $month<4)) {
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
			$tax_divided = 0;
			foreach ($divided_payment_list as $divided_payment) {
				$lesson_id = $divided_payment["lesson_id"];
				$divided_payment_price = (int)str_replace(",","",$divided_payment["price"]);
				if ($member["tax_flag"] == "1") {
					$tax_divided += $divided_payment_price*$cons_tax_rate;
					$divided_payment_price *= (1.0+$cons_tax_rate);
				}
				$price_list['lesson'][$lesson_id]		+= $divided_payment_price;
				$total_price												+= $divided_payment_price;
	if ($member_no == $log_member_no)	echo "divide $divided_payment_price<BR>";
				$lesson_total_price									+= $divided_payment_price;
	if ($member_no == $log_member_no)	echo "divide {$divided_payment["payment_price"]}<BR>";
				if ($divided_payment["year"] == $year && $divided_payment["month"] == $month) {
					if ($member["tax_flag"] == "1") {
						$tax_season_class_total -= $divided_payment["payment_price"]*$cons_tax_rate;
						$divided_payment["payment_price"] *=(1.0+$cons_tax_rate);
					}
					$price_list['lesson'][$lesson_id]	-= $divided_payment["payment_price"];
					$total_price 											-= $divided_payment["payment_price"];
	if ($member_no == $log_member_no)	echo "divide {$divided_payment["payment_price"]}<BR>";
					$lesson_total_price								-= $divided_payment["payment_price"];
				}
			}
		}

if ($member_no == $log_member_no)	echo "consumption_tax_price $consumption_tax_price <BR>";
if ($member_no == $log_member_no)	echo "tax_entrance_fee $tax_entrance_fee <BR>";
if ($member_no == $log_member_no)	echo "tax_membership_fee $tax_membership_fee <BR>";
if ($member_no == $log_member_no)	echo "tax_others $tax_others <BR>";
if ($member_no == $log_member_no)	echo "tax_divided $tax_divided <BR>";
		// 20160705 消費税の振り分け
		if ($consumption_tax_price > 0) {
			// 授業時間が短い順に昇順にソート
			$result = uasort($lesson_array, "cmp_hours");
			$tmp_tax_total_lesson_fee = 0;
			$tmp_tax_total_monthly_fee = 0;
			foreach ($lesson_array as $lesson_id => $item) {
				$lesson_array[$lesson_id]['fees'] = floor($item['fees'] * (1.0+$cons_tax_rate));
				// 20160810堀内不具合修正　+を追加
				//$tmp_tax_total_lesson_fee = floor($item['fees'] * 0.08);
				$tmp_tax_total_lesson_fee += floor($item['fees'] * $cons_tax_rate);
				$lesson_array[$lesson_id]['monthly_fees'] = floor($item['monthly_fees'] * (1.0+$cons_tax_rate));
 				$tmp_tax_total_monthly_fee += floor($item['monthly_fees'] * $cons_tax_rate);
     }
			// 部門ごとに消費税率をかけ切り捨てる
			// 切り捨てにより消費税合計金額より少ない場合は、授業時間が一番多い部門に加える
      $tax_total_lesson_fee_old = $consumption_tax_price - $tax_entrance_fee - $tax_membership_fee - $tax_others - $tax_season_class_total;
      $tax_total_lesson_fee = $consumption_tax_price - $tax_entrance_fee - $tax_membership_fee - $tax_others - $tax_divided - $tax_season_class_total;
/*
if ($tax_total_lesson_fee_old!=$tax_total_lesson_fee) {
	echo "old: $tax_total_lesson_fee_old = $consumption_tax_price - $tax_entrance_fee - $tax_membership_fee - $tax_others - $tax_season_class_total;<br>";
	echo "new: $tax_total_lesson_fee = $consumption_tax_price - $tax_entrance_fee - $tax_membership_fee - $tax_others - $tax_divided;<br>";
	echo "$tmp_tax_total_lesson_fee+$tmp_tax_total_monthly_fee < $tax_total_lesson_fee<br>";
}
*/
if ($member_no == $log_member_no)	echo "tax_total_lesson_fee $tax_total_lesson_fee <BR>";
if ($member_no == $log_member_no)	echo "tmp_tax_total_lesson_fee $tmp_tax_total_lesson_fee <BR>";
if ($member_no == $log_member_no)	echo "tmp_tax_total_monthly_fee $tmp_tax_total_monthly_fee <BR>";
			if ($tmp_tax_total_lesson_fee+$tmp_tax_total_monthly_fee < $tax_total_lesson_fee) {
				$lesson_array[$lesson_id]['fees'] += ($tax_total_lesson_fee - $tmp_tax_total_lesson_fee - $tmp_tax_total_monthly_fee);
if ($member_no == $log_member_no)	echo "lesson_id $lesson_id lesson-fees {$lesson_array[$lesson_id]['fees']}<BR>";
			}
 		}

		foreach ($lesson_array as $lesson_id => $item) {
			$price_list['lesson'][$lesson_id] += $item['fees'];
			$total_price = $total_price + $item['fees'];
if ($member_no == $log_member_no)	echo "lesson_id $lesson_id lesson-fees {$item['fees']}<BR>";
			$lesson_total_price += $item['fees'];
		}

		foreach ($lesson_array as $lesson_id => $item) {
//if ($lesson_id==4) echo "{$price_list['monthly'][$lesson_id]} += {$item['monthly_fees']}<br>";
			$price_list['monthly'][$lesson_id] += $item['monthly_fees'];
			$total_price = $total_price + $item['monthly_fees'];
if ($member_no == $log_member_no)	echo "lesson_id $lesson_id monthly-fees {$item['monthly_fees']}<BR>";
			$monthly_total_price += $item['monthly_fees'];
		}

		// テキスト代（模試代含む）
		// 20151009 テキスト購入テーブルに科目も登録してあるが集計には使わない
		$param_array = array("tbl_buying_textbook.member_no=?", "tbl_buying_textbook.year=?", "tbl_buying_textbook.month=?");
		$value_array = array($member_no, $year, $month);
		$order_array = array("tbl_buying_textbook.input_year", "tbl_buying_textbook.input_month", "tbl_buying_textbook.input_day", "tbl_buying_textbook.buying_no");
		$buying_textbook_list = get_buying_textbook_list($db, $param_array, $value_array, $order_array);
		//$textbook_price = 0;
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
if ($member_no == $log_member_no)	echo "text {$buying["price"]}<BR>";
		}
		
		if ($year<2016 || ($year==2016 && $month<4)) $grand_total += $total_price;

	}

	$price_list['lesson'][1] += $season_class_total+$price_list['test_price'];

	foreach ($lesson_list as $lesson_id => $lesson) {
		$price_list['lesson'][$lesson_id] += $price_list['textbook_price'][$lesson_id] + $price_list['monthly'][$lesson_id];
	}
}
	
	$diff_grand_total = $grand_total;
	$diff_grand_total -= $price_list['lesson'][1];
	$diff_grand_total -= $price_list['lesson'][2];
	$diff_grand_total -= $price_list['lesson'][3];
	$diff_grand_total -= $price_list['lesson'][4];
	$diff_grand_total -= $price_list['entrance_fee'];
	$diff_grand_total -= $price_list['membership_fee'];
	$diff_grand_total -= $price_list['tokusoku'];
?>
	<tr>
		<td><?= $row_no ?></td>
		<td align="left"><?= $member["name"] ?></td>
		<td align="left"><?= $join_month ?></td>
		<td align="left"><?= $leave_month ?></td>
		<td align="right"><?= $grand_total ?></td>
		<td align="right"><?= $price_list['lesson'][1] ?></td>
		<td align="right"><?= $price_list['lesson'][2] ?></td>
		<td align="right"><?= $price_list['lesson'][3] ?></td>
		<td align="right"><?= $price_list['lesson'][4] ?></td>
		<td align="right"><?= $price_list['entrance_fee'] ?></td>
		<td align="right"><?= $price_list['membership_fee'] ?></td>
		<td align="right"><?= $price_list['tokusoku'] ?></td>
	</tr>
<?php

}

} catch (Exception $e) {
	// 処理を中断するほどの致命的なエラー
	var_dump($e);echo"<br>";echo"<br>";
}

?>
</table>
</div>
</body></html>


