<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
$result = check_user($db, "1");

$errArray = array();
$message = "";

if (isset($_POST['add'])) {
	$action = 'add';
} else {
	$action = "";
}

function lesson_db($no, $lesson, $fees) {
	global $stmt;
	$i0 = ($no-1)*9;
	$stmt->execute(array($no, $lesson,  2, $fees[$i0], $fees[$i0]));
	$stmt->execute(array($no, $lesson,  3, $fees[$i0], $fees[$i0]));
	$stmt->execute(array($no, $lesson,  4, $fees[$i0], $fees[$i0]));
	$stmt->execute(array($no, $lesson,  5, $fees[$i0], $fees[$i0]));
	$stmt->execute(array($no, $lesson,  6, $fees[$i0], $fees[$i0]));
	$stmt->execute(array($no, $lesson,  7, $fees[$i0], $fees[$i0]));
	$stmt->execute(array($no, $lesson,  8, $fees[$i0+1], $fees[$i0+1]));
	$stmt->execute(array($no, $lesson,  9, $fees[$i0+1], $fees[$i0+1]));
	$stmt->execute(array($no, $lesson, 10, $fees[$i0+2], $fees[$i0+2]));
	$stmt->execute(array($no, $lesson, 11, $fees[$i0+3], $fees[$i0+3]));
	$stmt->execute(array($no, $lesson, 12, $fees[$i0+3], $fees[$i0+3]));
	$stmt->execute(array($no, $lesson, 13, $fees[$i0+4], $fees[$i0+4]));
}
function exercise_db($no, $lesson, $fees) {
	global $stmt;
	$i0 = ($no-1)*9;
	$stmt->execute(array($no, $lesson,  0, $fees[$i0+5], $fees[$i0+5]));
	$stmt->execute(array($no, $lesson,  1, $fees[$i0+6], $fees[$i0+6]));
	$stmt->execute(array($no, $lesson,  2, $fees[$i0+7], $fees[$i0+7]));
	$stmt->execute(array($no, $lesson,  3, $fees[$i0+8], $fees[$i0+8]));
}

if ($action == "add") {
	
	$fees60 = $_POST['fees60'];
	$fees90 = $_POST['fees90'];
	$fees120 = $_POST['fees120'];
	
	try{
		$db->beginTransaction();
		$sql = "INSERT INTO tbl_season_class_lesson_fee (no, course, grade, fee, insert_timestamp, update_timestamp) ".
						"VALUES (?,?,?,?,now(),now()) ".
						"ON DUPLICATE KEY UPDATE fee=?, update_timestamp=now()";
		$stmt = $db->prepare($sql);
		lesson_db(1, LESSON60, $fees60);
		lesson_db(1, LESSON90, $fees90);
		lesson_db(1, LESSON120, $fees120);
		lesson_db(2, LESSON60, $fees60);
		lesson_db(2, LESSON90, $fees90);
		lesson_db(2, LESSON120, $fees120);
		$sql = "INSERT INTO tbl_season_class_exercise_fee (no, course, type, fee, insert_timestamp, update_timestamp) ".
						"VALUES (?,?,?,?,now(),now()) ".
						"ON DUPLICATE KEY UPDATE fee=?, update_timestamp=now()";
		$stmt = $db->prepare($sql);
		exercise_db(1, LESSON60, $fees60);
		exercise_db(1, LESSON90, $fees90);
		exercise_db(1, LESSON120, $fees120);
		exercise_db(2, LESSON60, $fees60);
		exercise_db(2, LESSON90, $fees90);
		exercise_db(2, LESSON120, $fees120);
		$db->commit();
	} catch (PDOException $e){
		$db->rollback();
	  $errArray[] = $e->getMessage();
	}
}

get_season_fee_table($db);

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF8">
<meta name="robots" content="noindex,nofollow">
<title>事務システム</title>
<script type = "text/javascript">
function numcheck()
{
	var fees;
	fees=document.getElementsByName("fees60[]");
	for (var i=0;i<fees.length;i++) if (fees[i].value.match(/[^0-9]/)) {alert("半角数字を入力してください。\n\""+fees[i].value+"\"");return false;}
	fees=document.getElementsByName("fees90[]");
	for (var i=0;i<fees.length;i++) if (fees[i].value.match(/[^0-9]/)) {alert("半角数字を入力してください。\n\""+fees[i].value+"\"");return false;}
	fees=document.getElementsByName("fees120[]");
	for (var i=0;i<fees.length;i++) if (fees[i].value.match(/[^0-9]/)) {alert("半角数字を入力してください。\n\""+fees[i].value+"\"");return false;}
	return true;
}
</script>
<link rel="stylesheet" type="text/css" href="./script/style.css">
</head>
<?php
if ($action=='add' && count($errArray)==0) {
	echo "<body onLoad=\"alert('登録しました。')\">\n";
} else {
	echo "<body>\n";
}
?>
<div id="header">
	事務システム 
</div>

<div id="content" align="center">

<h3>期間講習・土日講習受講料登録</h3>
<a href="menu.php">メニューへ戻る</a>

<?php
	if (count($errArray) > 0) {
		foreach( $errArray as $error) {
?>
			<font color="red" size="3"><?= $error ?></font><br>
<?php
		}
	}
?>
<?php
	if ($message) {
?>
			<font color="blue" size="3"><?= $message ?></font><br>
<?php
	}
?>
<br><br>
<div class="menu_box">
■１時間当たりの受講料単価（税抜き）を登録<br>
■小学生受験生（4、5年生）はプラス500円<br>
■小学生受験生（6年生）はプラス1000円<br>
</div>
<br>
<form method="post" action="ss_class_fee.php" onsubmit="return numcheck()">
<h4>2018年8月以前入会者</h4>
<table border="1">
<tr>
<td align="center" rowspan="2">コース</td>
<td align="center" colspan="5">マンツーマン受講料</td>
<td align="center" colspan="3">期間講習演習</td>
<td align="center" rowspan="2">土日講習<br>演習</td>
<tr>
<td align="center">小学生</td>
<td align="center">中学生<br>１-２年</td>
<td align="center">中学生<br>３年</td>
<td align="center">高校生<br>１-２年</td>
<td align="center">高校生<br>３年</td>
<td align="center">受講日数<br>夏1-14<br>春冬1-9</td>
<td align="center">受講日数<br>夏15-19<br>春冬10-14</td>
<td align="center">受講日数<br>夏20-<br>春冬15-</td>
</tr>
<tr>
<td>60分コース</td>
<td><input type="tel" name="fees60[]" size="8" maxlength="5" value=<?= $lesson_fee_table[0][2][LESSON60]   ?>></td>
<td><input type="tel" name="fees60[]" size="8" maxlength="5" value=<?= $lesson_fee_table[0][8][LESSON60]   ?>></td>
<td><input type="tel" name="fees60[]" size="8" maxlength="5" value=<?= $lesson_fee_table[0][10][LESSON60]  ?>></td>
<td><input type="tel" name="fees60[]" size="8" maxlength="5" value=<?= $lesson_fee_table[0][11][LESSON60]  ?>></td>
<td><input type="tel" name="fees60[]" size="8" maxlength="5" value=<?= $lesson_fee_table[0][13][LESSON60]  ?>></td>
<td><input type="tel" name="fees60[]" size="8" maxlength="5" value=<?= $exercise_fee_table[0][LESSON60][0] ?>></td>
<td><input type="tel" name="fees60[]" size="8" maxlength="5" value=<?= $exercise_fee_table[0][LESSON60][1] ?>></td>
<td><input type="tel" name="fees60[]" size="8" maxlength="5" value=<?= $exercise_fee_table[0][LESSON60][2] ?>></td>
<td><input type="tel" name="fees60[]" size="8" maxlength="5" value=<?= $exercise_fee_table[0][LESSON60][3] ?>></td>
</tr>
<tr>
<td>90分コース</td>
<td><input type="tel" name="fees90[]" size="8" maxlength="5" value=<?= $lesson_fee_table[0][2][LESSON90]   ?>></td>
<td><input type="tel" name="fees90[]" size="8" maxlength="5" value=<?= $lesson_fee_table[0][8][LESSON90]   ?>></td>
<td><input type="tel" name="fees90[]" size="8" maxlength="5" value=<?= $lesson_fee_table[0][10][LESSON90]  ?>></td>
<td><input type="tel" name="fees90[]" size="8" maxlength="5" value=<?= $lesson_fee_table[0][11][LESSON90]  ?>></td>
<td><input type="tel" name="fees90[]" size="8" maxlength="5" value=<?= $lesson_fee_table[0][13][LESSON90]  ?>></td>
<td><input type="tel" name="fees90[]" size="8" maxlength="5" value=<?= $exercise_fee_table[0][LESSON90][0] ?>></td>
<td><input type="tel" name="fees90[]" size="8" maxlength="5" value=<?= $exercise_fee_table[0][LESSON90][1] ?>></td>
<td><input type="tel" name="fees90[]" size="8" maxlength="5" value=<?= $exercise_fee_table[0][LESSON90][2] ?>></td>
<td><input type="tel" name="fees90[]" size="8" maxlength="5" value=<?= $exercise_fee_table[0][LESSON90][3] ?>></td>
</tr>
<tr>
<td>120分コース</td>
<td><input type="tel" name="fees120[]" size="8" maxlength="5" value=<?= $lesson_fee_table[0][2][LESSON120]   ?>></td>
<td><input type="tel" name="fees120[]" size="8" maxlength="5" value=<?= $lesson_fee_table[0][8][LESSON120]   ?>></td>
<td><input type="tel" name="fees120[]" size="8" maxlength="5" value=<?= $lesson_fee_table[0][10][LESSON120]  ?>></td>
<td><input type="tel" name="fees120[]" size="8" maxlength="5" value=<?= $lesson_fee_table[0][11][LESSON120]  ?>></td>
<td><input type="tel" name="fees120[]" size="8" maxlength="5" value=<?= $lesson_fee_table[0][13][LESSON120]  ?>></td>
<td><input type="tel" name="fees120[]" size="8" maxlength="5" value=<?= $exercise_fee_table[0][LESSON120][0] ?>></td>
<td><input type="tel" name="fees120[]" size="8" maxlength="5" value=<?= $exercise_fee_table[0][LESSON120][1] ?>></td>
<td><input type="tel" name="fees120[]" size="8" maxlength="5" value=<?= $exercise_fee_table[0][LESSON120][2] ?>></td>
<td><input type="tel" name="fees120[]" size="8" maxlength="5" value=<?= $exercise_fee_table[0][LESSON120][3] ?>></td>
</tr>
</table>
<br>
<h4>2018年9月以降入会者</h4>
<table border="1">
<tr>
<td align="center" rowspan="2">コース</td>
<td align="center" colspan="5">マンツーマン受講料</td>
<td align="center" colspan="3">期間講習演習</td>
<td align="center" rowspan="2">土日講習<br>演習</td>
<tr>
<td align="center">小学生</td>
<td align="center">中学生<br>１-２年</td>
<td align="center">中学生<br>３年</td>
<td align="center">高校生<br>１-２年</td>
<td align="center">高校生<br>３年</td>
<td align="center">受講日数<br>夏1-14<br>春冬1-9</td>
<td align="center">受講日数<br>夏15-19<br>春冬10-14</td>
<td align="center">受講日数<br>夏20-<br>春冬15-</td>
</tr>
<tr>
<td>60分コース</td>
<td><input type="tel" name="fees60[]" size="8" maxlength="5" value=<?= $lesson_fee_table[1][2][LESSON60]   ?>></td>
<td><input type="tel" name="fees60[]" size="8" maxlength="5" value=<?= $lesson_fee_table[1][8][LESSON60]   ?>></td>
<td><input type="tel" name="fees60[]" size="8" maxlength="5" value=<?= $lesson_fee_table[1][10][LESSON60]  ?>></td>
<td><input type="tel" name="fees60[]" size="8" maxlength="5" value=<?= $lesson_fee_table[1][11][LESSON60]  ?>></td>
<td><input type="tel" name="fees60[]" size="8" maxlength="5" value=<?= $lesson_fee_table[1][13][LESSON60]  ?>></td>
<td><input type="tel" name="fees60[]" size="8" maxlength="5" value=<?= $exercise_fee_table[1][LESSON60][0] ?>></td>
<td><input type="tel" name="fees60[]" size="8" maxlength="5" value=<?= $exercise_fee_table[1][LESSON60][1] ?>></td>
<td><input type="tel" name="fees60[]" size="8" maxlength="5" value=<?= $exercise_fee_table[1][LESSON60][2] ?>></td>
<td><input type="tel" name="fees60[]" size="8" maxlength="5" value=<?= $exercise_fee_table[1][LESSON60][3] ?>></td>
</tr>
<tr>
<td>90分コース</td>
<td><input type="tel" name="fees90[]" size="8" maxlength="5" value=<?= $lesson_fee_table[1][2][LESSON90]   ?>></td>
<td><input type="tel" name="fees90[]" size="8" maxlength="5" value=<?= $lesson_fee_table[1][8][LESSON90]   ?>></td>
<td><input type="tel" name="fees90[]" size="8" maxlength="5" value=<?= $lesson_fee_table[1][10][LESSON90]  ?>></td>
<td><input type="tel" name="fees90[]" size="8" maxlength="5" value=<?= $lesson_fee_table[1][11][LESSON90]  ?>></td>
<td><input type="tel" name="fees90[]" size="8" maxlength="5" value=<?= $lesson_fee_table[1][13][LESSON90]  ?>></td>
<td><input type="tel" name="fees90[]" size="8" maxlength="5" value=<?= $exercise_fee_table[1][LESSON90][0] ?>></td>
<td><input type="tel" name="fees90[]" size="8" maxlength="5" value=<?= $exercise_fee_table[1][LESSON90][1] ?>></td>
<td><input type="tel" name="fees90[]" size="8" maxlength="5" value=<?= $exercise_fee_table[1][LESSON90][2] ?>></td>
<td><input type="tel" name="fees90[]" size="8" maxlength="5" value=<?= $exercise_fee_table[1][LESSON90][3] ?>></td>
</tr>
<tr>
<td>120分コース</td>
<td><input type="tel" name="fees120[]" size="8" maxlength="5" value=<?= $lesson_fee_table[1][2][LESSON120]   ?>></td>
<td><input type="tel" name="fees120[]" size="8" maxlength="5" value=<?= $lesson_fee_table[1][8][LESSON120]   ?>></td>
<td><input type="tel" name="fees120[]" size="8" maxlength="5" value=<?= $lesson_fee_table[1][10][LESSON120]  ?>></td>
<td><input type="tel" name="fees120[]" size="8" maxlength="5" value=<?= $lesson_fee_table[1][11][LESSON120]  ?>></td>
<td><input type="tel" name="fees120[]" size="8" maxlength="5" value=<?= $lesson_fee_table[1][13][LESSON120]  ?>></td>
<td><input type="tel" name="fees120[]" size="8" maxlength="5" value=<?= $exercise_fee_table[1][LESSON120][0] ?>></td>
<td><input type="tel" name="fees120[]" size="8" maxlength="5" value=<?= $exercise_fee_table[1][LESSON120][1] ?>></td>
<td><input type="tel" name="fees120[]" size="8" maxlength="5" value=<?= $exercise_fee_table[1][LESSON120][2] ?>></td>
<td><input type="tel" name="fees120[]" size="8" maxlength="5" value=<?= $exercise_fee_table[1][LESSON120][3] ?>></td>
</tr>
</table>
<br><br>
<input type="submit" name="add" value="登録">　<input type="reset" value="リセット"><br>
</form>
</div>
</body>
</html>
