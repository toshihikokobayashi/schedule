<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
$result = check_user($db, "1");

$teacher_list = get_teacher_list($db);

$errArray = array();
$initArray = array(
	"date"=>"", "stime"=>$time_list[$default_stime], "etime"=>$time_list[$default_etime], "season_course_id"=>"", 
	"stime1"=>"", "ltime1"=>"", "lesson1_id"=>"", "subject1_id"=>"", "teacher1_id"=>"",
	"stime2"=>"", "ltime2"=>"", "lesson2_id"=>"", "subject2_id"=>"", "teacher2_id"=>"", "fee"=>""
	);

$student["no"] = trim($_POST["no"]);
$student = get_member($db, array("tbl_member.no = ?"), array($student["no"]));

if (isset($_POST['add'])) {
	$action = 'add';
} else if (isset($_POST['delete_season_class_date'])) {
	$action = 'delete_season_class';
	$delete_season_class_date = $_POST['delete_season_class_date'];
	$delete_season_class_stime = $_POST['delete_season_class_stime'];
} else {
	$action = "";
}

if ($action == 'add' || $action == 'delete_season_class') {

	$date_array 				= $_POST["date"];
	$stime_array 				= $_POST["stime"];
	$etime_array 				= $_POST["etime"];
	$course_id_array 		= $_POST["course_id"];
	$stime1_array 			= $_POST["stime1"];
	$ltime1_array 			= $_POST["ltime1"];
	$lesson1_id_array		= $_POST["lesson1_id"];
	$subject1_id_array 	= $_POST["subject1_id"];
	$teacher1_id_array 	= $_POST["teacher1_id"];
	$stime2_array 			= $_POST["stime2"];
	$ltime2_array 			= $_POST["ltime2"];
	$lesson2_id_array 	= $_POST["lesson2_id"];
	$subject2_id_array 	= $_POST["subject2_id"];
	$teacher2_id_array 	= $_POST["teacher2_id"];
	$fee_array = $_POST["exercise_fee"];

	$season_class_list = array();
	foreach ($date_array as $no => $date) {
		$row = array();
		$row["date"] 				= $date_array[$no];
		$row["stime"] 			= $stime_array[$no];
		$row["etime"] 			= $etime_array[$no];
		$row["season_course_id"] 	= $course_id_array[$no];
		$row["stime1"] 			= $stime1_array[$no];
		$row["ltime1"] 			= $ltime1_array[$no];
		$row["lesson1_id"] 	= $lesson1_id_array[$no];
		$row["subject1_id"] = $subject1_id_array[$no];
		$row["teacher1_id"] = $teacher1_id_array[$no];
		$row["stime2"] 			= $stime2_array[$no];
		$row["ltime2"] 			= $ltime2_array[$no];
		$row["lesson2_id"] 	= $lesson2_id_array[$no];
		$row["subject2_id"] = $subject2_id_array[$no];
		$row["teacher2_id"] = $teacher2_id_array[$no];
		$row["fee"] 				= $fee_array[$no];
		$season_class_list[] = $row;
	}
	$student["season_class_list"] = $season_class_list;

}

$standard_exercise_fee = 2000;

if ($action == 'add') {
// 更新処理

	$errFlag = 0;
	$db->beginTransaction();
	foreach ($student["season_class_list"] as $key => $item) {
		if ($student['no']=="" || $item['date']=="" || $item['stime']=="") { continue; }
		$item001=$item;
		try{
			if ($key==0) {
				$sql = "DELETE FROM tbl_season_class WHERE member_id=? AND date IN ".$date_list_string;
				$stmt = $db->prepare($sql);
				$stmt->execute(array($student['no']));
			}
			$sql = "INSERT INTO tbl_season_class VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now(), now())";
			$stmt = $db->prepare($sql);
			$stmt->execute(array($student['no'], $item['date'], $item['stime'], $item['etime'], $item['season_course_id'],
					$item['stime1'], $item['lesson1_id'], $item['subject1_id'], $item['teacher1_id'],
					$item['stime2'], $item['lesson2_id'], $item['subject2_id'], $item['teacher2_id'],
					$item['fee'], $item['ltime1'], $item['ltime2']));
		}catch (PDOException $e){
			$errFlag = 1;
			if (strpos($e->getMessage(), "Duplicate") !== FALSE) {
				array_push($errArray, ($key+1)."行目、日時が重複しています。");
			} else {
			// 行を表示するためここでメッセージをセットする
				array_push($errArray, ($key+1)."行目、登録中にエラーが発生しました。".$e->getMessage());
		  //print('Error:'.$e->getMessage());
		  }
		}
	}
	if ($errFlag == 0) {
		$db->commit();
		header('Location: student_fee_list.php?sort_type=1');
		exit;
	} else {
		$db->rollback();
	}

	// エラーが発生した場合、編集画面を再表示する

} else if ($action == 'delete_season_class') {

	$errFlag = 0;
	try{
		$db->beginTransaction();
		$sql = "DELETE FROM tbl_season_class WHERE member_id=? AND date=? AND stime=?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($student['no'], $delete_season_class_date, $delete_season_class_stime));
	}catch (PDOException $e){
		$errFlag = 1;
		array_push($errArray, "削除中にエラーが発生しました。".$e->getMessage());
	  //print('Error:'.$e->getMessage());
	}
	if ($errFlag == 0) {
		$db->commit();
		// 料金の行を削除した時、編集画面を再表示する
	} else {
		$db->rollback();
	}
} else {

	$errFlag = 0;
	try{
		$sql = "SELECT * FROM tbl_season_class WHERE member_id=? AND date IN ".$date_list_string." ORDER BY date ASC, stime ASC";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($student['no']));
		$season_class_list = $stmt->fetchAll(PDO::FETCH_BOTH);
		$student["season_class_list"] = $season_class_list;
	}catch (PDOException $e){
		$errFlag = 1;
		array_push($errArray, "エラーが発生しました。".$e->getMessage());
	  //print('Error:'.$e->getMessage());
	}
	array_push($student["season_class_list"], $initArray);
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF8">
<meta name="robots" content="noindex,nofollow">
<title>事務システム</title>
<script type="text/javascript" src="./script/calender.js"></script>
<script type = "text/javascript">
<!--
function delete_season_class(row_no,date,stime) {
	result = window.confirm(row_no+"行目の情報を削除します。\nよろしいですか");
	if (result) {
		var tbl = document.getElementById("class_table");
		tbl.deleteRow(row_no+1);
		document.forms["student_form"].elements["delete_season_class_date"].value = date;
		document.forms["student_form"].elements["delete_season_class_stime"].value = stime;
		document.forms["student_form"].submit();
	}
}
function add_new_row(){
	var item = document.getElementsByName("date[]");
	if(item.length){
		len = item.length;
	}else{
		len = 1;
	}
	// 入力されたのが最後の行の場合
	// 行と空のセル(td)を追加する
	row = document.getElementById("class_table").insertRow(len+2);
	var new_cell1 = row.insertCell(0);
	var new_cell2 = row.insertCell(1);
	var new_cell3 = row.insertCell(2);
	var new_cell4 = row.insertCell(3);
	var new_cell5 = row.insertCell(4);
	var new_cell6 = row.insertCell(5);
	var new_cell7 = row.insertCell(6);
	var new_cell8 = row.insertCell(7);
	var new_cell9 = row.insertCell(8);
	var new_cell10 = row.insertCell(9);
	var new_cell11 = row.insertCell(10);
	var new_cell12 = row.insertCell(11);
	var new_cell13 = row.insertCell(12);
	var new_cell14 = row.insertCell(13);
	// 追加した行に最後の行の入力欄をセットする
	new_cell1.innerHTML = len+1;
	new_cell2.innerHTML = document.getElementById("cell2").innerHTML;
	new_cell3.innerHTML = document.getElementById("cell3").innerHTML;
	new_cell4.innerHTML = document.getElementById("cell4").innerHTML;
	new_cell5.innerHTML = document.getElementById("cell5").innerHTML;
	new_cell6.innerHTML = document.getElementById("cell6").innerHTML;
	new_cell7.innerHTML = document.getElementById("cell7").innerHTML;
	new_cell8.innerHTML = document.getElementById("cell8").innerHTML;
	new_cell9.innerHTML = document.getElementById("cell9").innerHTML;
	new_cell10.innerHTML = document.getElementById("cell10").innerHTML;
	new_cell11.innerHTML = document.getElementById("cell11").innerHTML;
	new_cell12.innerHTML = document.getElementById("cell12").innerHTML;
	new_cell13.innerHTML = document.getElementById("cell13").innerHTML;
	// 追加した行には削除ボタンを表示しない
	new_cell14.innerHTML = "	<input type='button' value='取消' onclick='delete_row("+(len+1)+")'>";
	// 初期値をセットする（セットしないと最後の行に初期値があれば表示されてしまう）
	// item(len)のlenは0から始まるので注意
	document.getElementsByName("date[]").item(len).value = "";
	document.getElementsByName("stime[]").item(len).value = "<?=$time_list[$default_stime]?>";
	document.getElementsByName("etime[]").item(len).value = "<?=$time_list[$default_etime]?>";
	document.getElementsByName("course_id[]").item(len).value = "";
	document.getElementsByName("stime1[]").item(len).value = "";
	document.getElementsByName("lesson1_id[]").item(len).value = "";
	document.getElementsByName("subject1_id[]").item(len).value = "";
	document.getElementsByName("teacher1_id[]").item(len).value = "";
	document.getElementsByName("stime2[]").item(len).value = "";
	document.getElementsByName("lesson2_id[]").item(len).value = "";
	document.getElementsByName("subject2_id[]").item(len).value = "";
	document.getElementsByName("teacher2_id[]").item(len).value = "";
//	document.getElementsByName("exercise_fee[]").item(len).value = "";
}

function delete_row(row_no){
  if (row_no == 1) {
	// （ＤＢに）未登録でも、1行目であれば削除できない
		alert('1行目は取り消せません。');
	} else {
		var tbl = document.getElementById("class_table");
		tbl.deleteRow(row_no+1);
		for (var i=row_no+1, rowLen=tbl.rows.length; i<rowLen; i++) {
			tbl.rows[i].cells[0].innerHTML = i-1;
			tbl.rows[i].cells[13].innerHTML = "<input type='button' value='取消' onclick='delete_row("+(i-1)+")'>";
		}
	}
}

function courseChange(obj,num)
{
	if (obj.selectedIndex == 0) {
//		document.getElementsByName('exercise_fee[]')[num-1].value='';
		document.getElementsByName('stime1[]')[num-1].selectedIndex=0;
		document.getElementsByName('ltime1[]')[num-1].value='';
		document.getElementsByName('lesson1_id[]')[num-1].selectedIndex=0;
		document.getElementsByName('subject1_id[]')[num-1].selectedIndex=0;
		document.getElementsByName('teacher1_id[]')[num-1].selectedIndex=0;
		document.getElementsByName('stime2[]')[num-1].selectedIndex=0;
		document.getElementsByName('ltime2[]')[num-1].value='';
		document.getElementsByName('lesson2_id[]')[num-1].selectedIndex=0;
		document.getElementsByName('subject2_id[]')[num-1].selectedIndex=0;
		document.getElementsByName('teacher2_id[]')[num-1].selectedIndex=0;
	} else if (obj.selectedIndex == 1) {
//		document.getElementsByName('exercise_fee[]')[num-1].value='<?= $standard_exercise_fee ?>';
	} else {
//		document.getElementsByName('exercise_fee[]')[num-1].value='';
	}
}

function stime1Change(obj,num)
{
	if (obj.selectedIndex>0) {
		document.getElementsByName('ltime1[]')[num-1].value='1.0';
	} else {
		document.getElementsByName('ltime1[]')[num-1].value='';
		document.getElementsByName('lesson1_id[]')[num-1].selectedIndex=0;
		document.getElementsByName('subject1_id[]')[num-1].selectedIndex=0;
		document.getElementsByName('teacher1_id[]')[num-1].selectedIndex=0;
	}
}

function stime2Change(obj,num)
{
	if (obj.selectedIndex>0) {
		document.getElementsByName('ltime2[]')[num-1].value='1.0';
	} else {
		document.getElementsByName('ltime2[]')[num-1].value='';
		document.getElementsByName('lesson2_id[]')[num-1].selectedIndex=0;
		document.getElementsByName('subject2_id[]')[num-1].selectedIndex=0;
		document.getElementsByName('teacher2_id[]')[num-1].selectedIndex=0;
	}
}

//-->
</script>
<link rel="stylesheet" type="text/css" href="./script/style.css">
</head>
<body>

<div id="header">
	事務システム 
</div>

<div id="content" align="center">

<h2><?= $season_class_title ?></h2>
<h3>期間講習の登録 - 更新・削除</h3>

<a href="student_fee_list.php">生徒一覧へ</a>&nbsp;&nbsp;
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


<form method="post" name="student_form" action="season_class.php">
	<input type="hidden" name="no" value="<?=$student["no"]?>">
	<input type="hidden" name="del_flag" value="<?=$student["del_flag"]?>">

	<input type="hidden" name="delete_season_class_date" value="">
	<input type="hidden" name="delete_season_class_stime" value="">

	<div class="menu_box">
		<font color="black" size="-1">
		※&nbsp;編集する場合は、講習情報を入力して、登録ボタンを押してください。<br>
		</font>
	</div>


	<table>
	<tr>
  <td align="center">
		<input type="submit" name="add" value="登録">
		<input type="reset" value="リセット">
	</td>
	</tr>
	</table>

	<br>

	<table id="form">
	<tr>
		<th>　生徒名：　</th>
		<td><?=$student["name"]?></td>
	</tr>
	</table>


	<table border="1" id="class_table">
	<tr>
		<th rowspan="2">行</th>
		<th rowspan="2">日付</th>
		<th rowspan="2">演習時間</th>
		<th rowspan="2">コース</th>
		<th rowspan="2">演習解説受講料</th>
		<th colspan="4">個別授業１</th>
		<th colspan="4">個別授業２</th>
		<th rowspan="2">&nbsp;</th>
	</tr>
	<tr>
		<th>時間</th>
		<th>教室</th>
		<th>科目</th>
		<th>先生</th>
		<th>時間</th>
		<th>教室</th>
		<th>科目</th>
		<th>先生</th>
	</tr>
 	<?php
	$row_no = 1;
	$season_class_count = count($student["season_class_list"]);
	foreach ($student["season_class_list"] as $season_class) {
		if ($season_class["season_course_id"]==1 && !$season_class["fee"]) {
			$season_class["fee"] = $standard_exercise_fee;
		}
		if ($season_class["stime1"] && !$season_class["ltime1"]) {
			$season_class["ltime1"] = "1.0";
		}
		if ($season_class["stime2"] && !$season_class["ltime2"]) {
			$season_class["ltime2"] = "1.0";
		}
	?>
	<tr>
		<?php
			if ($row_no == 1) { echo "<td id='cell1'>"; } else { echo "<td>"; }
		?>
			<?=$row_no?>
		</td>
		<?php
			if ($row_no == 1) { echo "<td id='cell2'>"; } else { echo "<td>"; }
		?>
			<?php disp_pulldown_menu($date_list, "date[]", $season_class["date"]); ?>
		</td>
		<?php
			if ($row_no == 1) { echo "<td id='cell3'>"; } else { echo "<td>"; }
		?>
			<?php disp_pulldown_menu($time_list, "stime[]", $season_class["stime"]); ?>～
			<?php disp_pulldown_menu($time_list, "etime[]", $season_class["etime"]); ?>
		</td>
		<?php
			if ($row_no == 1) { echo "<td id='cell4'>"; } else { echo "<td>"; }
		?>
			<?php disp_course_menu($season_course_list, "course_id[]", $season_class["season_course_id"], "courseChange(this,$row_no)"); ?>
		</td>
		<?php
			if ($row_no == 1) { echo "<td id='cell5'>"; } else { echo "<td>"; }
		?>
<!--
		<input type="text" name="exercise_fee[]" size="4" value="<?= $season_class["fee"] ?>" >
-->
		　　　　
		</td>
		<?php
			if ($row_no == 1) { echo "<td id='cell6'>"; } else { echo "<td>"; }
		?>
			<?php disp_pulldown_menu($time_list, "stime1[]", $season_class["stime1"], "stime1Change(this,$row_no)"); ?>
			<input type="text" name="ltime1[]" size="1" value="<?= $season_class["ltime1"] ?>" >H
		</td>
		<?php
			if ($row_no == 1) { echo "<td id='cell7'>"; } else { echo "<td>"; }
		?>
			<?php disp_lesson_menu($lesson_list, "lesson1_id[]", $season_class["lesson1_id"]); ?>
		</td>
		<?php
			if ($row_no == 1) { echo "<td id='cell8'>"; } else { echo "<td>"; }
		?>
			<?php disp_subject_menu($subject_list, "subject1_id[]", $season_class["subject1_id"]); ?>
		</td>
		<?php
			if ($row_no == 1) { echo "<td id='cell9'>"; } else { echo "<td>"; }
		?>
			<?php disp_teacher_menu($teacher_list, "teacher1_id[]", $season_class["teacher1_id"]); ?>
		</td>
		<?php
			if ($row_no == 1) { echo "<td id='cell10'>"; } else { echo "<td>"; }
		?>
			<?php disp_pulldown_menu($time_list, "stime2[]", $season_class["stime2"], "stime2Change(this,$row_no)"); ?>
			<input type="text" name="ltime2[]" size="1" value="<?= $season_class["ltime2"] ?>" >H
		</td>
		<?php
			if ($row_no == 1) { echo "<td id='cell11'>"; } else { echo "<td>"; }
		?>
			<?php disp_lesson_menu($lesson_list, "lesson2_id[]", $season_class["lesson2_id"]); ?>
		<?php
			if ($row_no == 1) { echo "<td id='cell12'>"; } else { echo "<td>"; }
		?>
			<?php disp_subject_menu($subject_list, "subject2_id[]", $season_class["subject2_id"]); ?>
		</td>
		<?php
			if ($row_no == 1) { echo "<td id='cell13'>"; } else { echo "<td>"; }
		?>
			<?php disp_teacher_menu($teacher_list, "teacher2_id[]", $season_class["teacher2_id"]); ?>
		</td>
		<?php
			if ($row_no == 1) { echo "<td id='cell14'>"; } else { echo "<td>"; }
		?>
			<?php
				 if ($season_class["date"]) { ?>
				<input type="button" value="削除" onclick="delete_season_class(<?=$row_no?>,'<?=$season_class["date"]?>','<?=$season_class["stime"]?>')">
			<?php } else  {?>
				<input type="button" value="取消" onclick="delete_row(<?=$row_no?>)">
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
		</td>
	</tr>
</table>

</form>

</div>

</body>
</html>
