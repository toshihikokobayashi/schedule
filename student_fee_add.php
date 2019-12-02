<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
$result = check_user($db, "1");

$course_list = get_course_list($db);
$teacher_list = get_teacher_list($db);

$errArray = array();

if (isset($_POST['add'])) {
	$action = 'add';
} else {
	$action = "";
}

$student["grade_adj"]=0;

if ($action == 'add') {
// 登録処理

	$student = array();
	$student["no"] = trim($_POST["no"]);
	$student["name"] = trim($_POST["name"]);
	$student["furigana"] = trim($_POST["furigana"]);
	//$student["mei"] = trim($_POST["mei"]);
	//$student["sei"] = trim($_POST["sei"]);
	$student["grade"] = array_search($_POST["grade"],$grade_list);
	$student["membership_fee"] = trim($_POST["membership_fee"]);
	$student["sheet_id"] = trim($_POST["sheet_id"]); // 任意項目
	$student["cid"] = trim($_POST["cid"]); // 任意項目
	$student["del_flag"] = trim($_POST["del_flag"]);
	$student["tax_flag"] = 1;
	$student["jyukensei"] = $_POST["jyukensei"]?1:0;

	$student['birth_year'] =  $_POST['birth_year'];
	$student['birth_month'] = $_POST['birth_month'];
	$student['birth_day'] =   $_POST['birth_day'];
	$student['grade_adj'] =   $_POST['grade_adj'];
	$student['gender'] =   $_POST['gender'];
	$student['mail_address'] =      trim($_POST['mail_address']);

	if (empty($student["grade"]) === true || $student["grade"] == "") {
		$student["grade"] = null;
	}
	if (empty($student["cid"]) === true || $student["cid"] == "") {
		$student["cid"] = null;
	}
	if (empty($student["sheet_id"]) === true || $student["sheet_id"] == "") {
		$student["sheet_id"] = null;
	}

	// 20150813
	//$row_no_array = $_POST["row_no"];
	$fee_no_array = $_POST["fee_no"];
	$lesson_id_array = $_POST["lesson_id"];
	$subject_id_array = $_POST["subject_id"];
	//$type_id_array = $_POST["type_id"];
	$course_id_array = $_POST["course_id"];
	$teacher_id_array = $_POST["teacher_id"];
	$fee_array = $_POST["fee"];
	$family_minus_price_array = $_POST["family_minus_price"];

	$fee_list = array();
	//foreach ($row_no_array as $row_no) {
	// 20150813 真ん中の行を取消した後登録しようとすると、必須入力エラーが出て登録できなかったため修正した。
	foreach ($fee_no_array as $no => $fee_no) {
		$row = array();
		//if ($lesson_id_array[$no] != "" || $subject_id_array[$no] != "0" || $type_id_array[$no] != "" || $fee_array[$no] != "") {
		//if ($lesson_id_array[$no] != "" || $subject_id_array[$no] != "0" || $course_id_array[$no] != "" || $fee_array[$no] != "") {
		// 20160525修正 $teacher_id_array[$no]を条件に追加
		if ($lesson_id_array[$no] != "" || $subject_id_array[$no] != "0" || $course_id_array[$no] != "" || $teacher_id_array[$no] != "" || $fee_array[$no] != "") {
			$row["fee_no"] = $fee_no_array[$no];
			$row["lesson_id"] = $lesson_id_array[$no];
			$row["subject_id"] = $subject_id_array[$no];
			//$row["type_id"] = $type_id_array[$no];
			$row["course_id"] = $course_id_array[$no];
			$row["teacher_id"] = $teacher_id_array[$no];
			$row["fee"] = $fee_array[$no];
			$row["family_minus_price"] = $family_minus_price_array[$no];
			if (isset($row["family_minus_price"]) === false || $row["family_minus_price"] == "") {
				$row["family_minus_price"] = 0;
			}
			$fee_list[] = $row;
		}

	$m_fee_no_array = $_POST["m_fee_no"];
	$m_lesson_id_array = $_POST["m_lesson_id"];
	$m_subject_id_array = $_POST["m_subject_id"];
	$m_course_id_array = $_POST["m_course_id"];
	$m_fee_array = $_POST["m_fee"];
	$m_minus_price_array = $_POST["m_minus_price"];
	
	$m_fee_list = array();
	foreach ($m_fee_no_array as $no => $fee_no) {
		$row = array();
		if ($m_lesson_id_array[$no] != "" || $m_subject_id_array[$no] != "0" || $m_course_id_array[$no] != "" || $m_fee_array[$no] != "") {
			$row["fee_no"] = $m_fee_no_array[$no];
			$row["lesson_id"] = $m_lesson_id_array[$no];
			$row["subject_id"] = $m_subject_id_array[$no];
			$row["course_id"] = $m_course_id_array[$no];
			$row["fee"] = $m_fee_array[$no];
			$row["minus_price"] = $m_minus_price_array[$no];
			if (isset($row["minus_price"]) === false || $row["minus_price"] == "") {
				$row["minus_price"] = 0;
			}
			$m_fee_list[] = $row;
		}
	}
}
	$student["fee_list"] = $fee_list;
	$student["m_fee_list"] = $m_fee_list;

	// 入力チェック処理
	$result = check_student($db, $errArray, $student);

	$result = check_fee_list($db, $errArray, $student["no"], $student["fee_list"]);
	if (count($errArray) == 0) {
	// 入力エラーがない場合
		$errFlag = 0;
		try{
			$db->beginTransaction();
			$student_no = insert_student($db, $student);
			if (!$student_no) $errFlag = 1;
			$result = edit_fee($db, $student_no, $student["fee_list"],$errArray);
			if (!$result) $errFlag = 1;
			$result = edit_m_fee($db, $student_no, $student["m_fee_list"], $errArray);
			if (!$result) $errFlag = 1;
		}catch (PDOException $e){
			$errFlag = 1;
			array_push($errArray, "登録中にエラーが発生しました。");
		  print('Error:'.$e->getMessage());
		}
		if ($errFlag == 0) {
			$db->commit();
			header('Location: student_fee_list.php?sort_type=2');
			exit;
		} else {
			$db->rollback();
		}
	}

	// エラー時、編集画面を再表示する
	// 再表示時に、料金の新規追加行を表示する

	// 20160525修正 "teacher_id"=>"0"を""に変更
	//array_push($student["fee_list"], array("fee_no"=>"", "lesson_id"=>"", "subject_id"=>"0", "course_id"=>"", "teacher_id"=>"0", "fee"=>"", "family_minus_price"=>""));
	array_push($student["fee_list"], array("fee_no"=>"", "lesson_id"=>"", "subject_id"=>"0", "course_id"=>"", "teacher_id"=>"", "fee"=>"", "family_minus_price"=>""));
	array_push($student["m_fee_list"], array("fee_no"=>"", "lesson_id"=>"", "subject_id"=>"0", "course_id"=>"", "fee"=>"", "minus_price"=>""));

} else {
	// 初期表示処理

	$student["no"] = "";
	$student["name"] = "";
	$student["furigana"] = "";
	//$student["sei"] = "";
	//$student["mei"] = "";
	$student["grade"] = "";
	$student["membership_fee"] = null;
	$student["sheet_id"] = "";
	$student["cid"] = "";
	$student["del_flag"] = "0";	// 初期値：現生徒
	$student["tax_flag"] = 1;

	// 20160525修正 "teacher_id"=>"0"を""に変更
	//$student["fee_list"][0] = array("fee_no"=>"", "lesson_id"=>"", "subject_id"=>"0", "course_id"=>"", "teacher_id"=>"0", "fee"=>"", "family_minus_price"=>"");
	$student["fee_list"][0] = array("fee_no"=>"", "lesson_id"=>"", "subject_id"=>"0", "course_id"=>"", "teacher_id"=>"", "fee"=>"", "family_minus_price"=>"");
	$student["m_fee_list"][0] = array("fee_no"=>"", "lesson_id"=>"", "subject_id"=>"0", "course_id"=>"", "fee"=>"", "minus_price"=>"");

}


?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF8">
<meta name="robots" content="noindex,nofollow">
<title>事務システム</title>
<script type = "text/javascript">
<!--
//function reset_member() {
//	init_member();
//	document.forms["student_form"].submit();
//}
//
//function init_member() {
//	document.forms["student_form"].elements["no"].value = "";
//	document.forms["student_form"].elements["sei"].value = "";
//	document.forms["student_form"].elements["mei"].value = "";
//	document.forms["student_form"].elements["grade"].value = "";
//}
//-->
var grade_list=[<?php foreach($grade_list as $val){echo('"'.$val.'",');} ?> 0];
function set_grade() {
	var birth_year  = document.forms["student_form"].elements["birth_year"].value;
	var birth_month = document.forms["student_form"].elements["birth_month"].value;
	var birth_day   = document.forms["student_form"].elements["birth_day"].value;
	if (birth_year > 0 && birth_month > 0 && birth_day > 0) {
		var cdate = new Date();
		var grade = cdate.getFullYear()-5-birth_year;
		if (birth_month<4) { grade++; }
		if (birth_month==4 && birth_day==1) { grade++; }
		if (cdate.getMonth()<3) { grade--; }
		grade += Number(document.forms["student_form"].elements["grade_adj"].value);
		if (grade<1) { grade=1; }
		if (grade>14) { grade=14; }
		document.forms["student_form"].elements["grade"].value = grade_list[grade];
	} else {
		document.forms["student_form"].elements["grade"].value = grade_list[0];
	}
}
function add_check() {
	var grade  = document.forms["student_form"].elements["grade"].value;
	var jyukensei  = document.forms["student_form"].elements["jyukensei"].checked;
	switch (grade) {
		case "小4":
		case "小5":
		case "小6":
			if (jyukensei) {
				return window.confirm("受験生でよろしいですか？");
			} else {
				return window.confirm("受験生でなくてよろしいですか？");
			}
		default:
			return true;
	}
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

<h3>生徒の登録 - 新規登録</h3>

<a href="student_fee_list.php">生徒一覧へ</a>&nbsp;&nbsp;
<a href="check_cid.php">宛先登録チェックへ</a>&nbsp;&nbsp;
<a href="menu.php">メニューへ戻る</a><br><br>

<?php
	if (count($errArray) > 0) {
		foreach( $errArray as $error) {
?>
			<font color="red" size="5"><?= $error ?></font><br>
<?php
		}
?>
	<br>
<?php
	}
?>

<form method="post" name="student_form" action="student_fee_add.php" onsubmit="return add_check()">
	<input type="hidden" name="no" value="<?=$student["no"]?>">
	<input type="hidden" name="del_flag" value="0">

	<table>
	<tr>
  <td align="center">
		新規登録する場合は、生徒情報とその生徒の料金情報を入力して、登録ボタンを押してください。<br>
		<input type="submit" name="add" value="登録">
		<input type="reset" name="reset" value="リセット">
	</td>
	</tr>
	</table>

	<br>

	<table id="form">
<!--
	<tr>
	<th><font color="red">*</font>&nbsp;姓</th><td><input type="text" name="sei" size="20" value="<?=$student["sei"]?>"></td>
	</tr>
	<tr>
	<th><font color="red">*</font>&nbsp;名</th><td><input type="text" name="mei" size="20" value="<?=$student["mei"]?>"></td>
	</tr>
-->
	<tr>
	<th><font color="red">*</font>&nbsp;名前</th>
	<td>
		<input type="text" name="name" size="20" value="<?=$student["name"]?>">
		<font color="red" size="-1">名字と名前の間に半角スペースを入れてください</font>
	</td>
	</tr>
	<tr>
	<th><font color="red">*</font>&nbsp;ふりがな</th>
	<td>
		<input type="text" name="furigana" size="35" value="<?=$student["furigana"]?>">
		<font color="red" size="-1">名字と名前の間に半角スペースを入れてください</font>
	</td>
	</tr>
	<tr>
	<th><font color="red">*</font>&nbsp;月会費</th><td><input type="text" name="membership_fee" size="20" value="<?=$student["membership_fee"]?>">円</td>
	</tr>
	<tr>
	<th><font color="red">*</font>&nbsp;生年月日</th><td>
	<select name="birth_year" onchange="set_grade()">
	<option value="0"></option>
<?php
	for ($i=date('Y');$i>1990;$i--) { echo "	<option value=\"$i\"".($i==$student["birth_year"]?' selected':'').">$i</option>"; }
	echo "<option value=\"1990\"".($i==$student["birth_year"]?' selected':'').">1990以前</option>";
?>
	</select>年
	<select name="birth_month" onchange="set_grade()">
	<option value="0"></option>
<?php
	for ($i=1;$i<=12;$i++) { echo "	<option value=\"$i\"".($i==$student["birth_month"]?' selected':'').">$i</option>"; }
?>
	</select>月
	<select name="birth_day" onchange="set_grade()">
	<option value="0"></option>
<?php
	for ($i=1;$i<=31;$i++) { echo "	<option value=\"$i\"".($i==$student["birth_day"]?' selected':'').">$i</option>"; }
?>
	</select>日
		&nbsp;&nbsp;
  学年：<input type="text" name="grade" readonly="readonly" size="2" value="<?= $grade_list[$student["grade"]] ?>">
	（補正
	<select name="grade_adj" onchange="set_grade()">
	<option value="3"<?= ($student["grade_adj"]==3?' selected':'') ?>>+3</option>
	<option value="2"<?= ($student["grade_adj"]==2?' selected':'') ?>>+2</option>
	<option value="1"<?= ($student["grade_adj"]==1?' selected':'') ?>>+1</option>
	<option value="0"<?= ($student["grade_adj"]==0?' selected':'') ?>></option>
	<option value="-1"<?= ($student["grade_adj"]==-1?' selected':'') ?>>-1</option>
	<option value="-2"<?= ($student["grade_adj"]==-2?' selected':'') ?>>-2</option>
	<option value="-3"<?= ($student["grade_adj"]==-3?' selected':'') ?>>-3</option>
	</select>
	）
		&nbsp;&nbsp;
	<input type="checkbox" name="jyukensei" <?= $student["jyukensei"]?"checked":"" ?>>受験生
	</td>
	</tr>
	<tr><th>性別</th><td>
	<select name="gender">
	<option value=""></option>
	<option value="M" <?= ($student['gender']=='M')?'selected':'' ?>>男</option>
	<option value="F" <?= ($student['gender']=='F')?'selected':'' ?>>女</option>
	</select>
	</td><tr>
	<th>授業料の税種別</th><td>
<!--
		<select name="tax_flag">
		<option value="0" <?php if ($student["tax_flag"] === 0) { echo "selected"; } ?>>税込</option>
		<option value="1" <?php if ($student["tax_flag"] === 1) { echo "selected"; } ?>>税抜</option>
		</select>
-->
	税抜
	</td>
	</tr>
	<tr>
	<th>メールアドレス</th><td><input type="text" name="mail_address" size="60" value="<?=$student["mail_address"]?>"></td>
	</tr>
	<tr>
	<th>CID</th><td><input type="text" name="cid" size="60" value="<?=$student["cid"]?>"></td>
	</tr>
	<tr>
	<th>スプレッドシートID</th><td><input type="text" name="sheet_id" size="60" value="<?=$student["sheet_id"]?>"></td>
	</tr>
	<tr>
	<th>ステータス</th><td>
		<select name="del_flag">
		<option value="0" <?php if ($student["del_flag"] == 0) { echo "selected"; } ?>>現生徒</option>
		<option value="2" <?php if ($student["del_flag"] == 2) { echo "selected"; } ?>>前生徒</option>
		<option value="1" <?php if ($student["del_flag"] == 1) { echo "selected"; } ?>>削除</option>
		</select>
	</td>
	</tr>
	</table>

	<br>

	<div class="menu_box">
		<font color="blue" size="-1">
		※&nbsp;科目について、「英会話」と「ピアノ」の場合は、「科目なし」を選択してください。<br>
		※&nbsp;１時間あたりの料金について、「ファミリー」の場合は、全員が出席した時の合計料金を入力してください。<br>
		※&nbsp;ファミリー&nbsp;一人欠席時引く金額は、「ファミリー」で一人が欠席した時に引く金額を入力してください。<br>
		&nbsp;&nbsp;&nbsp;&nbsp;ファミリーでない場合は、空白または「0」としてください。<br>
		</font>
		<font color="black" size="-1">
		&nbsp;&nbsp;&nbsp;&nbsp;ファミリーの例：2人で4000円、1人が欠席で1000円引きの場合、<br>
		&nbsp;&nbsp;&nbsp;&nbsp;「1時間あたりの料金」に「4000」を、「ファミリー&nbsp;1人欠席時引く金額」に「1000」入力してください。
		</font>
	</div>
	<table border="1" id="fee_table">
	<tr>
		<th>行</th>
		<th><font color="red">*</font>&nbsp;教室</th>
		<th><font color="red">*</font>&nbsp;科目</th>
		<th><font color="red">*</font>&nbsp;コース</th>
		<th><font color="red">*</font>&nbsp;先生</th>
		<th><font color="red">*</font>&nbsp;1時間あたりの料金</th>
		<th>ファミリー<br>1人欠席時引く金額</th>
		<th>&nbsp;</th>
	</tr>
 	<?php
	$row_no = 0;
	$fee_count = count($student["fee_list"]);
	foreach ($student["fee_list"] as $fee) {
	?>
	<tr>
		<?php
			if ($row_no == 0) { echo "<td id='cell1'>"; } else { echo "<td>"; }
		?>
			<input type="text" name="disp_row_no[]" value="<?=$row_no+1?>" disabled size="4">
			<!-- 20150813 -->
			<!-- <input type="hidden" name="row_no[]" value="<?=$row_no?>"> -->
			<input type="hidden" name="fee_no[]" value="<?=$fee["fee_no"]?>">
		</td>
		<?php
			if ($row_no == 0) { echo "<td id='cell2'>"; } else { echo "<td>"; }
		?>
			<?php disp_lesson_menu($lesson_list, "lesson_id[]", $fee["lesson_id"]); ?>
		</td>
		<?php
			if ($row_no == 0) { echo "<td id='cell3'>"; } else { echo "<td>"; }
		?>
			<?php disp_subject_menu($subject_list, "subject_id[]", $fee["subject_id"]); ?>
		</td>
		<?php
			if ($row_no == 0) { echo "<td id='cell4'>"; } else { echo "<td>"; }
		?>
			<?php disp_course_menu($course_list, "course_id[]", $fee["course_id"]); ?>
		</td>
		<?php
			if ($row_no == 0) { echo "<td id='cell5'>"; } else { echo "<td>"; }
		?>
			<?php disp_teacher_menu($teacher_list, "teacher_id[]", $fee["teacher_id"]); ?>
		</td>
		<?php
			if ($row_no == 0) { echo "<td id='cell6'>"; } else { echo "<td>"; }
		?>
			<input type="text" name="fee[]" size="10" value="<?=$fee["fee"]?>">円
		</td>
		<?php
			if ($row_no == 0) { echo "<td id='cell7'>"; } else { echo "<td>"; }
		?>
			<input type="text" name="family_minus_price[]" size="10" value="<?=$fee["family_minus_price"]?>" style="ime-mode: inactive;">円
		</td>
		<?php
			if ($row_no == 0) { echo "<td id='cell8'>"; } else { echo "<td>"; }
		?>
			<?php
				 if ($fee["fee_no"]) { ?>
				<input type="button" value="削除" onclick="delete_fee(<?=$row_no?>,<?=$fee["fee_no"]?>)">
			<?php } else {?>
				<input type="button" value="取消" onclick="delete_row(<?=($row_no+1)?>)">
			<?php } ?>
		</td>
	</tr>
 	<?php
		$row_no++;
	}
	?>
</table>

<table>
	<tr>
		<td align="center">
			<input type="button" value="最後に行を追加する" onclick="add_new_row()">&nbsp;&nbsp;
<!--
			<input type="button" value="最後に行を追加する" onclick="add_new_row(<?=$fee_count?>)">&nbsp;&nbsp;
			<input type="button" value="行の追加を取り消す" onclick="delete_last_row(<?=$fee_count?>)"> 
-->
		</td>
	</tr>
</table>
<br>
<table><tr><th>月謝</th></tr></table>
	<table border="1" id="m_fee_table">
	<tr>
		<th>行</th>
		<th><font color="red">*</font>&nbsp;教室</th>
		<th><font color="red">*</font>&nbsp;科目</th>
		<th><font color="red">*</font>&nbsp;コース</th>
		<th><font color="red">*</font>&nbsp;月謝</th>
		<th>休講1回割引金額</th>
		<th>&nbsp;</th>
	</tr>
 	<?php
	$row_no = 0;
	foreach ($student["m_fee_list"] as $fee) {
	?>
	<tr>
		<?php
			if ($row_no == 0) { echo "<td id='m_cell1'>"; } else { echo "<td>"; }
		?>
			<input type="text" name="m_disp_row_no[]" value="<?=$row_no+1?>" disabled size="4">
			<input type="hidden" name="m_fee_no[]" value="<?=$fee["fee_no"]?>">
		</td>
		<?php
			if ($row_no == 0) { echo "<td id='m_cell2'>"; } else { echo "<td>"; }
		?>
			<?php disp_lesson_menu($lesson_list, "m_lesson_id[]", $fee["lesson_id"]); ?>
		</td>
		<?php
			if ($row_no == 0) { echo "<td id='m_cell3'>"; } else { echo "<td>"; }
		?>
			<?php disp_subject_menu($subject_list, "m_subject_id[]", $fee["subject_id"]); ?>
		</td>
		<?php
			if ($row_no == 0) { echo "<td id='m_cell4'>"; } else { echo "<td>"; }
		?>
			<?php disp_course_menu($course_list, "m_course_id[]", $fee["course_id"]); ?>
		</td>
		<?php
			if ($row_no == 0) { echo "<td id='m_cell5'>"; } else { echo "<td>"; }
		?>
			<input type="text" name="m_fee[]" size="10" value="<?=$fee["fee"]?>" style="ime-mode: inactive;">円
		</td>
		<?php
			if ($row_no == 0) { echo "<td id='m_cell6'>"; } else { echo "<td>"; }
		?>
			<input type="text" name="m_minus_price[]" size="10" value="<?=$fee["minus_price"]?>" style="ime-mode: inactive;">円
		</td>
		<?php
			if ($row_no == 0) { echo "<td id='m_cell7'>"; } else { echo "<td>"; }
		?>
			<?php
				 if ($fee["fee_no"]) { ?>
				<input type="button" value="削除" onclick="m_delete_fee(<?=$row_no?>,<?=$fee["fee_no"]?>)">
			<?php } else  {?>
				<input type="button" value="取消" onclick="m_delete_row(<?=($row_no+1)?>)">
			<?php } ?>
		</td>
	</tr>
 	<?php
		$row_no++;
	}
	?>
</table>
<table>
	<tr>
		<td align="center">
			<input type="button" value="最後に行を追加する" onclick="m_add_new_row()">&nbsp;&nbsp;
		</td>
	</tr>
</table>
</form>
</div>

</body>
</html>
