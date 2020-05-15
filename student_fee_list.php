<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
$result = check_user($db, "1");

$course_list = get_course_list($db);

$sort_type = 1;
if ($_GET["sort_type"]) {
	$sort_type = trim($_GET["sort_type"]);
}

$search_grade = "";
$search_name = "";
if ($_POST["search_grade"] !== "") {
	$search_grade = trim($_POST["search_grade"]);
}
if ($_POST["search_name"]) {
	$search_name = trim($_POST["search_name"]);
}
if ($_POST["genseito"]=='0') {
	$genseito = '0';
} else {
	$genseito = '1';
}
if ($_POST["zenseito"]=='1') {
	$zenseito = '1';
} else {
	$zenseito = '0';
}
$tax_mode = '1';
if ($_POST["tax_mode"]) {
	$tax_mode = $_POST["tax_mode"];
}

$param_array = array();
$value_array = array();
array_push($param_array, "tbl_member.kind = ?");
array_push($value_array, '3');
if ($search_grade !== "") {	// $search_gradeが""と0未登録を区別するためif($search_grade)は使わない
	if ($search_grade == "0") {
		array_push($param_array, "(tbl_member.grade = ? or tbl_member.grade IS NULL)");
	} else {
		array_push($param_array, "tbl_member.grade = ?");
	}
	array_push($value_array, $search_grade);
}
if ($search_name) {
	array_push($param_array," tbl_member.name like concat('%',?,'%') ");
	array_push($value_array, $search_name);
}
if ($tax_mode == '2') {
	array_push($param_array, "tbl_member.tax_flag = '1'");
} else if ($tax_mode == '3') {
	array_push($param_array, "tbl_member.tax_flag = '0'");
}
if ($lms_mode) {
ob_start();
echo date("----------- Y/m/d H:i:s \n")."<br>";
var_dump( $_GET );echo"<br>";
$tmpdata = ob_get_contents();
ob_end_clean();
file_put_contents('./log-20200511', $tmpdata, FILE_APPEND);
	$student_id = str_pad($_GET['student_id'], 6, 0, STR_PAD_LEFT);
	array_push($param_array," tbl_member.no = '$student_id' ");
}

// 20150816 ふりがなの50音順にソートする
$order_array = array("tbl_member.furigana asc");
/*
if ($sort_type && $sort_type == "2") {
	// 登録のとき
	$order_array = array("tbl_member.update_timestamp desc");
} else {
	//$order_array = array("tbl_member.sei", "tbl_member.mei"); //tbl_member.gradeがstringのためキャストする
	$order_array = array("CAST(tbl_member.grade AS SIGNED)", "tbl_member.sei", "tbl_member.mei"); //tbl_member.gradeがstringのためキャストする
}
*/
// 生徒一覧を取得
if ($genseito=='1' && $zenseito=='0') {
	$all_student_flag = "0";	// 現生徒を抽出
	$student_list = get_member_list($db, $param_array, $value_array, $order_array, $all_student_flag);
} else if ($genseito=='1' && $zenseito=='1') {
	$all_student_flag = "1";	// 前生徒と現生徒を抽出
	$student_list = get_member_list($db, $param_array, $value_array, $order_array, $all_student_flag);
} else if ($genseito=='0' && $zenseito=='1') {
	$all_student_flag = "3";	// 前生徒を抽出
	$student_list = get_member_list($db, $param_array, $value_array, $order_array, $all_student_flag);
}
$now_count = count($student_list);

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF8">
<meta name="robots" content="noindex,nofollow">
<title>事務システム</title>
<script type = "text/javascript">
<!--

function edit_member(no) {
	document.forms["student_list"].elements["no"].value = no;
	document.forms["student_list"].action = "student_fee_edit.php";
	document.forms["student_list"].submit();
}

function entry_season_class(no) {
	document.forms["student_list"].elements["no"].value = no;
	document.forms["student_list"].elements["class_type"].value = "season_class";
	document.forms["student_list"].action = "season_class_entry.php";
	document.forms["student_list"].submit();
}

function entry_sat_sun_class(no) {
	document.forms["student_list"].elements["no"].value = no;
	document.forms["student_list"].elements["class_type"].value = "sat_sun_class";
	document.forms["student_list"].action = "season_class_entry.php";
	document.forms["student_list"].submit();
}

function edit_season_class(no) {
	document.forms["student_list"].elements["no"].value = no;
	document.forms["student_list"].action = "season_class.php";
	document.forms["student_list"].submit();
}

function edit_entrance_fee(no) {
	document.forms["student_list"].elements["no"].value = no;
	document.forms["student_list"].action = "entrance_fee_edit.php";
	document.forms["student_list"].submit();
}

function edit_buying_textbook(no) {
	document.forms["student_list"].elements["no"].value = no;
	document.forms["student_list"].action = "buying_textbook_edit2.php";
	document.forms["student_list"].submit();
}

function edit_others(no) {
	document.forms["student_list"].elements["no"].value = no;
	document.forms["student_list"].action = "others_edit.php";
	document.forms["student_list"].submit();
}

function total_fees(no) {
	document.forms["student_list"].elements["no"].value = no;
	document.forms["student_list"].action = "student_total_fees.php";
	document.forms["student_list"].submit();
}

function edit_divided_payment(no) {
	document.forms["student_list"].elements["no"].value = no;
	document.forms["student_list"].elements["mode"].value = "new";
  document.forms["student_list"].method = "get";
	document.forms["student_list"].action = "divided_payment_list.php";
	document.forms["student_list"].submit();
}

function search_clear() {
	document.forms["student_list"].elements["search_name"].value = "";
	document.forms["student_list"].elements["search_grade"].value = "";
	document.forms["student_list"].submit();
}

function dispGenseito(flag) {
	if(flag) {
		document.forms["student_list"].elements["genseito"].value = "1";
	} else {
		document.forms["student_list"].elements["genseito"].value = "0";
	}
	document.forms["student_list"].submit();
}

function dispZenseito(flag) {
	if(flag) {
		document.forms["student_list"].elements["zenseito"].value = "1";
	} else {
		document.forms["student_list"].elements["zenseito"].value = "0";
	}
	document.forms["student_list"].submit();
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

<?php if (!$lms_mode) { ?>
<h3>生徒の登録 - 生徒一覧</h3>

<!--
<a href="student_fee_add.php">新規登録へ</a>&nbsp;&nbsp;
-->
<a href="check_cid.php">宛先登録チェックへ</a>&nbsp;&nbsp;
<a href="menu.php">メニューへ戻る</a><br><br>
<?php } ?>

<form method="post" name="student_list" action="student_fee_list.php">
	<input type="hidden" name="no" value="">	<?php /* 編集ボタンクリック時にstudent_noをセットする */ ?>
	<input type="hidden" name="mode" value="">	<?php /* 編集ボタンクリック時にmodeをセットする */ ?>
	<input type="hidden" name="genseito" value="<?= $genseito ?>">
	<input type="hidden" name="zenseito" value="<?= $zenseito ?>">
	<input type="hidden" name="class_type" value="">

<?php if (!$lms_mode) { ?>
<table border="1">
	<tr>
		<th>学年</th>
		<td>
				<select name="search_grade">
				<option value=""></option><?php /* 未選択 */ ?>
		<?php
			foreach ($grade_list as $key => $name) {
		?>
				<option value="<?=$key?>"<?php if ($search_grade != "" && $search_grade == $key) { echo "selected"; } ?>>
				<?= $name ?></option>
		<?php
			}
		?>
				</select>
		</td>
		<th>氏名</th>
		<td><input type="text" name="search_name" value="<?= $search_name; ?>">※氏名の部分一致で検索します</td>
	</tr>
	<tr>
		<td colspan="4" align="center">
			<input type="submit" value="検索" onclick="search_member()">&nbsp;&nbsp;
			<input type="button" value="検索解除" onclick="search_clear()">
		</td>
	</tr>
</table>
<br>
教室欄の<font color="red">赤字表示授業料</font>は仮登録です。生徒・科目ボタンをクリックし正規の授業料を登録してください。
<br>
<table>
<tr><td>表示生徒：</td>
<td>
<input type="checkbox" onclick="dispGenseito(this.checked)" <?php if ($genseito=="1") echo "checked"; ?>>入会者（現生徒）&nbsp;&nbsp;
<input type="checkbox" onclick="dispZenseito(this.checked)" <?php if ($zenseito=="1") echo "checked"; ?>>退会者（前生徒）&nbsp;&nbsp;（退会者の欄はグレーで表示されます。）
</td></tr>
<tr><td>消費税：</td>
<td>
<input type="radio" name="tax_mode" value="1" onchange="document.student_list.submit()" <?php if ($tax_mode=="1") echo "checked"; ?>>税抜・税込表示&nbsp;&nbsp;
<input type="radio" name="tax_mode" value="2" onchange="document.student_list.submit()" <?php if ($tax_mode=="2") echo "checked"; ?>>税抜生徒のみ表示&nbsp;&nbsp;
<input type="radio" name="tax_mode" value="3" onchange="document.student_list.submit()" <?php if ($tax_mode=="3") echo "checked"; ?>>税込生徒のみ表示&nbsp;&nbsp;
</td></tr>
</table>
<br>
<?= $now_count?>件&nbsp;<br>
<?php } ?>
<table border="1">
<tr>
<!--<th>編集中</th>-->
<th>氏名</th>
<th>学年</th>
<th>入会月</th>
<!--<th>学年</th>-->
<th>教室</th>
<th colspan="8">登録・変更</th>
</tr>
	<?php
			foreach ($student_list as $item) {
			if ($item["del_flag"]=='0') {
				echo "<tr>";
			} else {
				echo "<tr bgcolor=\"#eeeeee\">";
			}
	?>
			<td><?= $item["name"] ?></td>
			<td><?= $grade_list[$item['grade']] ?></td>
			<td><?= get_student_join_month($db,$item['no']) ?></td>
<!--
			<td align="center">
				<?php if ($student["no"] != "" && $item['no'] == $student["no"]) { echo "▼"; } ?>
			</td>
			<td><?= $item["sei"]." ".$item["mei"] ?></td>
			<td align="center"><?php if ($item["grade"]) { echo $grade_list[$item["grade"]]; } ?></td>
-->
			<td align="center">
			<?php
			if (count($item["fee_list"]) == 0) {
			?>
      	<font color="#ff00000">未登録</font>
			<?php
			} else {
				$i = 0;
       	foreach ($item["fee_list"] as $fee_array) {
					//if ($i != 0) echo "&nbsp;<font color = 'red'><b>／</b></font>&nbsp;";
					if ($i != 0) echo "<br>";
			?>
					<font color=<?= $fee_array['temp_flag']?'red':'black' ?>>
					<?= $fee_array["lesson_name"] ?>
			<?php
					if ($fee_array["subject_id"] != "0") {
						// コースの場合
						//if ($fee_array["type_id"] == "4") {
						if ($fee_array["course_id"] > 3) {
							echo "(".$course_list[$fee_array["course_id"]]["course_name"]."&nbsp;".$fee_array["subject_name"].")&nbsp;".str_replace('.00','',$fee_array["fee"])."円";
						} else {
							echo "(".$fee_array["subject_name"].")&nbsp;".str_replace('.00','',$fee_array["fee"])."円";
						}
					} else {
						echo "&nbsp;".str_replace('.00','',$fee_array["fee"])."円";
					}
					echo "</font>";
					$i++;
				}
			}
			?>
			</td>
			<td align="center">
			<input type="button" value="生徒・科目" onclick="edit_member('<?= $item['no']?>')" target="_blank"> <?php /* ボタンにはnameを付けないこと。jsに渡すnoは'で囲むことStringとして渡すため*/ ?>
			</td>
			<td align="center">
			<input type="button" value="期間講習" onclick="entry_season_class('<?= $item['no']?>')" target="_blank"> <?php /* ボタンにはnameを付けないこと。jsに渡すnoは'で囲むことStringとして渡すため*/ ?>
<!--  2017年 期間講習対応
			<input type="button" value="登録・変更" onclick="edit_season_class('<?= $item['no']?>')" target="_blank"> <?php /* ボタンにはnameを付けないこと。jsに渡すnoは'で囲むことStringとして渡すため*/ ?>
-->
			</td>
			<td align="center">
			<input type="button" value="土日講習" onclick="entry_sat_sun_class('<?= $item['no']?>')" target="_blank"> <?php /* ボタンにはnameを付けないこと。jsに渡すnoは'で囲むことStringとして渡すため*/ ?>
			</td>
			<td align="center">
			<input type="button" value="入会金" onclick="edit_entrance_fee('<?= $item['no']?>')"> <?php /* ボタンにはnameを付けないこと。jsに渡すnoは'で囲むことStringとして渡すため*/ ?>
<!--
			<a href="others_edit.php?no=<?= $item["no"]?>&y=<?= $year ?>&m=<?= $month ?>" target="_blank">登録・変更</a>
-->
			</td>
			<td align="center">
			<input type="button" value="テキスト代" onclick="edit_buying_textbook('<?= $item['no']?>')"> <?php /* ボタンにはnameを付けないこと。jsに渡すnoは'で囲むことStringとして渡すため*/ ?>
<!--
			<a href="buying_textbook_edit.php?no=<?= $item["no"]?>&y=<?= $year ?>&m=<?= $month ?>" target="_blank">登録・変更</a>
-->
			</td>
			<td align="center">
			<input type="button" value="月謝調整" onclick="edit_others('<?= $item['no']?>')"> <?php /* ボタンにはnameを付けないこと。jsに渡すnoは'で囲むことStringとして渡すため*/ ?>
<!--
			<a href="others_edit.php?no=<?= $item["no"]?>&y=<?= $year ?>&m=<?= $month ?>" target="_blank">登録・変更</a>
-->
			</td>
			<td align="center">
<!--
			<input type="button" value="登録・変更" onclick="edit_divided_payment('<?= $item['no']?>')"> <?php /* ボタンにはnameを付けないこと。jsに渡すnoは'で囲むことStringとして渡すため*/ ?>
			<a href="others_edit.php?no=<?= $item["no"]?>&y=<?= $year ?>&m=<?= $month ?>">登録・変更</a>
			<input type="button" value="登録・変更" onclick="javascript:location.href='divided_payment_list.php?no=<?=$item['no']?>'" target="_blank"> <?php /* ボタンにはnameを付けないこと。jsに渡すnoは'で囲むことStringとして渡すため*/ ?>
-->
<!--
			<a href='divided_payment_list.php?no=<?=$item['no']?>' target="_blank">変更・削除</a>
-->
			<input type="button" value="授業料分割支払" onclick="edit_divided_payment('<?= $item['no']?>')"> <?php /* ボタンにはnameを付けないこと。jsに渡すnoは'で囲むことStringとして渡すため*/ ?>
			</td>
			<td align="center">
			<input type="button" value="受講料合計" onclick="total_fees('<?= $item['no']?>')"> <?php /* ボタンにはnameを付けないこと。jsに渡すnoは'で囲むことStringとして渡すため*/ ?>
			</td>
		</tr>
	<?php
			}
	?>
</table>

<?php if ($lms_mode) { ?>
<br><input type="button" onclick="window.close()" value="閉じる">
<?php } ?>

</form>
</div>

<div id="footer">
</div>

</body>
</html>
