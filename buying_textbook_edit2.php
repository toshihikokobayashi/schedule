<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
require_once("./array_column.php");
$result = check_user($db, "1");

//var_dump($_GET);echo"<BR>";
//var_dump($_POST);echo"<BR>";

$errArray = array();
$message = "";

$kind_array = get_kind_array("1");

$student_no   = trim($_POST["no"]);
if (!$student_no) $student_no = trim($_GET["no"]);

if (isset($_POST['add'])) {
	$action = 'add';
} else if (isset($_POST['delete'])) {
	$action = 'delete';
} else if ($_POST['selected_buying_no']) {
	$action = 'selected_buying_no';
} else {
	$action = "";
}

$result1 = check_number($errArray, "生徒No", $student_no, true);
if ($result1 === false) {
	header('location: menu.php');
}

// メンバー情報を取得
$member = null;
if ($student_no != "") {
	$member = get_member($db, array("tbl_member.no = ?"), array($student_no));
}
if ($member == null) {
	header('location: menu.php');
}

if ($action == 'selected_buying_no') {
// 選択時
	$buying_array = array("buying_no"=>"", "input_year"=>"", "input_month"=>"", "input_day"=>"", "name"=>"", "price"=>"", "lesson_id"=>"", "kind"=>"");
	$buying_list = get_buying_textbook_list($db, array("tbl_buying_textbook.buying_no = ?"), array($_POST["selected_buying_no"]));
	if (count($buying_list) == 1) {
		$buying_array = $buying_list[0];
	}
	$grade = $buying_array['grade'];
} else {
	$year = trim($_POST["year"]);
	$month = trim($_POST["month"]);
	$buying_array["buying_no"] = trim($_POST["buying_no"]);
	$buying_array["year"] = $year;
	$buying_array["month"] = $month;
	$buying_array["input_year"] = trim($_POST["input_year"]);
	$buying_array["input_month"] = trim($_POST["input_month"]);
	$buying_array["input_day"] = trim($_POST["input_day"]);
	$buying_array["text_subject"] = trim($_POST["text_subject"]);
	$buying_array["name"] = trim($_POST["name"]);
	$buying_array["price"] = trim($_POST["price"]);
	$buying_array["lesson_id"] = trim($_POST["lesson_id"]);
	$buying_array["kind"] = trim($_POST["kind"]);
	$grade = $member['grade'];
	$buying_array["grade"] = $grade;
	if ($_POST["grade"])	{
		$grade = array_search($_POST["grade"], $grade_list);
		$buying_array["grade"] = array_search($_POST["grade"], $grade_list);
	}
	if (!$grade) $errArray[] = '学年が不明です。生年月日を登録してください。';
}

try {
	$stmt = $db->query("SELECT * FROM tbl_text ORDER BY name");
	$rslt = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rslt as $item) { $item['name']=trim($item['name']); $text_list[$item['text_id']] = $item; }
	
	$stmt = $db->query("SELECT * FROM tbl_text_subject");
	$rslt = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rslt as $item) $text_subject_list[$item['text_id']][] = $item['subject_id'];
		
	$stmt = $db->query("SELECT * FROM tbl_text_grade");
	$rslt = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rslt as $item) $text_grade_list[$item['text_id']][] = $item['grade'];

	$stmt = $db->query("SELECT * FROM tbl_text_supplier");
	$rslt = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rslt as $item) $text_supplier_list[$item['text_id']][] = $item['supplier_id'];
	
	$stmt = $db->query("SELECT subject_id, name FROM tbl_text_subject_name");
	$rslt = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rslt as $item) $text_subject_name_list[$item['subject_id']] = $item['name'];
	$text_subject_name_list[-1] = 'その他'; 
	if ($buying_array["text_subject_id"]) $buying_array["text_subject"] = $text_subject_name_list[$buying_array["text_subject_id"]];
	else if ($buying_array["text_subject"]) $buying_array["text_subject_id"] = array_search($buying_array["text_subject"], $text_subject_name_list);

	if (!$buying_array["text_subject_id"]) $text_list = array();
	
	$stmt = $db->query("SELECT publisher_id, name FROM tbl_text_publisher_name");
	$rslt = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rslt as $item) $publisher_name_list[$item['publisher_id']] = $item['name'];
	
	$stmt = $db->query("SELECT supplier_id, name FROM tbl_text_supplier_name");
	$rslt = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rslt as $item) $supplier_name_list[$item['supplier_id']] = $item['name'];
	
	$text_list = array_filter($text_list,
		function($s){
			global $grade,$text_subject_list,$text_grade_list,$buying_array,$text_id;
			$subjs = $text_subject_list[$s['text_id']]; $grades = $text_grade_list[$s['text_id']];
			if ((!$buying_array["text_subject_id"] || 
						($subjs && array_search($buying_array["text_subject_id"],$subjs)!==false) ||
						(!$subjs && $buying_array["text_subject_id"]==-1)) && 
					(!$grade || !$grades || array_search($grade, $grades)!==false)) {
				if ($buying_array["name"] == $s['name']) $text_id=$s['text_id'];
				return true;
			} else {
				return false;
			}
		}
	);
	$text_name_list = array_column($text_list,'name');
	
	if (is_null($buying_array["grade"]) && !$buying_array["buying_no"]) $buying_array["grade"] = $grade;
	
} catch (PDOException $e) {
	$errFlag = 1;
	$errArray[] = $e->getMessage();
}

if ($action == 'add') {
// 更新処理

	// 入力チェック処理
	$result = check_buying_textbook_list($errArray, $buying_array);
	if (count($errArray) == 0) {
	// 入力エラーがない場合
		$errFlag = 0;
		try{
			$db->beginTransaction();
			$result = edit_buying_textbook($db, $student_no, $buying_array, $errArray);
			if (!$result) $errFlag = 1;
		}catch (PDOException $e){
			$errFlag = 1;
		  //print('Error:'.$e->getMessage());
		}
		if ($errFlag == 0) {
			$db->commit();
 			//if ($buying_array["buying_no"] == ""){ 
				// 登録時、フォームを初期化
				$buying_array = array("buying_no"=>"", "input_year"=>"", "input_month"=>"", "input_day"=>"", "name"=>"", "price"=>"", "lesson_id"=>"", "kind"=>"");
				$buying_array["grade"] = $grade;
			//}
			$text_list = array(); $text_name_list = array();
			$message = "登録できました。";
			//header("Location: buying_textbook_edit.php?no=".$student_no."&year=".$year."&month=".$month."&f");
			//exit;
		} else {
			array_push($errArray, "登録中にエラーが発生しました。");
			$db->rollback();
			$selected_item_no = $_POST['selected_item_no'];
		}
	}

	// エラーが発生した場合、編集画面を再表示する
	// 再表示時に、料金の新規追加行を追加表示する
		//array_push($buying_textbook_list, array("buying_no"=>"", "year"=>"", "month"=>"", "day"=>"", "name"=>"", "price"=>""));

} else if ($action == 'delete') {

	$result = check_number($errArray, "購入No", $_POST["buying_no"], true);
	if (count($errArray) == 0) {
	// 入力エラーがない場合
		$errFlag = 0;
		try{
			$db->beginTransaction();
			$result = delete_buying_textbook($db, $_POST["buying_no"]);
			if (!$result) $errFlag = 1;
		}catch (PDOException $e){
			$errFlag = 1;
		  //print('Error:'.$e->getMessage());
		}
		if ($errFlag == 0) {
			$db->commit();
 			//if ($buying_array["buying_no"] == ""){ 
				// 登録時、フォームを初期化
				$buying_array = array("buying_no"=>"", "input_year"=>"", "input_month"=>"", "input_day"=>"", "name"=>"", "price"=>"", "lesson_id"=>"", "kind"=>"");
				$buying_array["grade"] = $grade;
			//}
			$text_list = array(); $text_name_list = array();
			$message = "削除できました。";
		} else {
			array_push($errArray, "削除中にエラーが発生しました。");
			$db->rollback();
			$selected_item_no = $_POST['selected_item_no'];
		}
	}

} else if ($action == 'selected_buying_no') {
	
	$selected_item_no = $_POST['selected_item_no'];
	
} else {
	
	if ($text_list[$text_id]['tewatashi_price3']) {
		$tewatashi_price = $text_list[$text_id]['tewatashi_price3'];
	} else {
		$amazon_id = array_search('amazon', $supplier_name_list);
		if (array_search($amazon_id,$text_supplier_list[$text_id])!==false) {
			$tewatashi_price = $text_list[$text_id]['tewatashi_price2'];
		} else {
			$tewatashi_price = $text_list[$text_id]['tewatashi_price1'];
		}
	}
	if ($text_id) {
		$buying_array["price"] = $tewatashi_price;
	}
	
	$selected_item_no = $_POST['selected_item_no'];

}

// 一覧表示
//$param_array = array("tbl_buying_textbook.member_no = ?",
//												"tbl_buying_textbook.year = ?",
//												"tbl_buying_textbook.month = ?");
//$value_array = array($student_no, $year, $month);
//$order_array = array("tbl_buying_textbook.day");
$param_array = array("tbl_buying_textbook.member_no = ?");
$value_array = array($student_no);
//$order_array = array("tbl_buying_textbook.input_year", "tbl_buying_textbook.input_month", "tbl_buying_textbook.input_day");
$order_array = array("tbl_buying_textbook.input_year", "tbl_buying_textbook.input_month", "tbl_buying_textbook.input_day", "tbl_buying_textbook.buying_no");
$buying_textbook_list = get_buying_textbook_list($db, $param_array, $value_array, $order_array);


function edit_buying_textbook(&$db, $student_no, $buying_array, &$errArray) {
	$errFlag = 0;
		try {
				if ($buying_array["buying_no"] && $buying_array["buying_no"] > 0) {
				// 更新時
					$result = update_buying_textbook($db, $student_no, $buying_array);
					if (!$result) {
						$errFlag = 1;
					}
				} else {
		    // 新規登録時
					$result = insert_buying_textbook($db, $student_no, $buying_array);
					if (!$result) {
						$errFlag = 1;
					}
				}
		}catch (PDOException $e){
			$errFlag = 1;
		  //print('Error:'.$e->getMessage());
		}
	if ($errFlag == 0) {
		return true;
	} else {
		return false;
	}

}

// 管理画面の生徒管理からテキストブック名の変更があるかもしれないので、引数に年月は入れない
function insert_buying_textbook(&$db, $student_no, $buying_array) {
	$errFlag = 0;
	try{
		$sql = "INSERT INTO tbl_buying_textbook (member_no, year, month, input_year, input_month, input_day, ".
					"name, price, kind, lesson_id, insert_timestamp, update_timestamp, text_subject_id, grade ".
					" ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now(), now(), ?, ?)";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(1, $member_no);
		$stmt->bindParam(2, $year);
		$stmt->bindParam(3, $month);
		$stmt->bindParam(4, $input_year);
		$stmt->bindParam(5, $input_month);
		$stmt->bindParam(6, $input_day);
		$stmt->bindParam(7, $name);
		$stmt->bindParam(8, $price);
		$stmt->bindParam(9, $kind);
		$stmt->bindParam(10, $lesson_id);
		$stmt->bindParam(11, $text_subject_id);
		$stmt->bindParam(12, $grade);
		$member_no = $student_no;
		$year = $buying_array["year"];
		$month = $buying_array["month"];
		$input_year = $buying_array["input_year"];
		$input_month = $buying_array["input_month"];
		$input_day = $buying_array["input_day"];
		$name = $buying_array["name"];
		$price = $buying_array["price"];
		$kind = $buying_array["kind"];
		$lesson_id = $buying_array["lesson_id"];
		$text_subject_id = $buying_array["text_subject_id"];
		$grade = $buying_array["grade"];
		$stmt->execute();
	}catch (PDOException $e){
		$errFlag = 1;
	  print('Error:'.$e->getMessage());
		throw $e;
	}
	if ($errFlag == 0) {
		return true;
	} else {
		return false;
	}
}

// 管理画面の生徒管理からテキストブック名の変更があるかもしれないので、引数に年月は入れない
function update_buying_textbook(&$db, $student_no, $buying_array) {
	$errFlag = 0;
	try{
		$sql = "UPDATE tbl_buying_textbook SET member_no=?, year=?, month=?, input_year=?, input_month=?, input_day=?, name=?, ".
					"price=?, kind=?, lesson_id=?, update_timestamp=now(), text_subject_id=?, grade=? ".
					"WHERE tbl_buying_textbook.buying_no = ?";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(1, $member_no);
		$stmt->bindParam(2, $year);
		$stmt->bindParam(3, $month);
		$stmt->bindParam(4, $input_year);
		$stmt->bindParam(5, $input_month);
		$stmt->bindParam(6, $input_day);
		$stmt->bindParam(7, $name);
		$stmt->bindParam(8, $price);
		$stmt->bindParam(9, $kind);
		$stmt->bindParam(10, $lesson_id);
		$stmt->bindParam(11, $text_subject_id);
		$stmt->bindParam(12, $grade);
		$stmt->bindParam(13, $buying_no);
		$member_no = $student_no;
		$year = $buying_array["year"];
		$month = $buying_array["month"];
		$input_year = $buying_array["input_year"];
		$input_month = $buying_array["input_month"];
		$input_day = $buying_array["input_day"];
		$name = $buying_array["name"];
		$price = $buying_array["price"];
		$kind = $buying_array["kind"];
		$lesson_id = $buying_array["lesson_id"];
		$text_subject_id = $buying_array["text_subject_id"];
		$grade = $buying_array["grade"];
		$buying_no = $buying_array["buying_no"];
		$stmt->execute();
	}catch (PDOException $e){
		$errFlag = 1;
		throw $e;
	  //print('Error:'.$e->getMessage());
	}
	if ($errFlag == 0) {
		return true;
	} else {
		return false;
	}
}

function delete_buying_textbook(&$db, $buying_no) {
	$errFlag = 0;
	try{
		$sql = "DELETE FROM tbl_buying_textbook ".
					 "WHERE tbl_buying_textbook.buying_no = ?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($buying_no));
	}catch (PDOException $e){
		$errFlag = 1;
		//throw $e;
	  //print('Error:'.$e->getMessage());
	}
	if ($errFlag == 0) {
		return true;
	} else {
		return false;
	}
}


?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF8">
<title>事務システム</title>
<script type = "text/javascript">
<!--
function delete_buying() {
	result = window.confirm("テキスト購入情報を削除します。\nよろしいですか");
	if (result) {
		document.forms["buying_textbook_form"].submit();
	} else {
		return false;
	}
}

function selected_buying(item_no,buying_no) {
		document.forms["buying_textbook_form"].selected_item_no.value=item_no;
		document.forms["buying_textbook_form"].selected_buying_no.value=buying_no;
		document.forms["buying_textbook_form"].submit();
}

function reset_form() {
		document.forms["buying_textbook_form"].buying_no.value="";
		document.forms["buying_textbook_form"].year.value="";
		document.forms["buying_textbook_form"].month.value="";
		document.forms["buying_textbook_form"].input_year.value="";
		document.forms["buying_textbook_form"].input_month.value="";
		document.forms["buying_textbook_form"].input_day.value="";
		document.forms["buying_textbook_form"].name.value="";
		document.forms["buying_textbook_form"].price.value="";
		document.forms["buying_textbook_form"].lesson_id.value="";
		document.forms["buying_textbook_form"].kind.value="";
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


<h3>生徒の登録 - テキスト代</h3>

<?php if (!$lms_mode) { ?>
<a href="student_fee_list.php">生徒の登録 - 生徒一覧へ</a>&nbsp;&nbsp;
<a href="menu.php">メニューへ戻る</a><br><br>

<?php
}
	if (count($errArray) > 0) {
		foreach( $errArray as $error) {
?>
			<font color="red" size="3"><?= $error ?></font><br>
<?php
		}
?>
<!--	<br>-->
<?php
	}
?>
<?php
	if ($message) {
?>
			<font color="blue" size="3"><?= $message ?></font><br>
<?php
	}

?>

<form method="post" name="buying_textbook_form" action="buying_textbook_edit2.php">
	<input type="hidden" name="no" value="<?=$student_no?>">
<!--
	<input type="hidden" name="y" value="<?=$year?>">
	<input type="hidden" name="m" value="<?=$month?>">
-->
	<h3><?=$member["name"]?>様（<?= $grade_list[$member['grade']] ?>）
<!-- <?=$year?>年<?=$month?>月 -->
	</h3>
<div class="menu_box">
	<font color="black" size="-1">
	■教科、学年、テキストブック名の順に選択してください。
	</font>
</div>
	<table border="1" id="buying_textbook_table">
	<tr>
		<th>番号</th>
		<th colspan="2"><font color="red">*</font>請求年月</th>
		<th colspan="3"><font color="red">*</font>記入日</th>
		<th><font color="red">*</font>教科</th>
		<th><font color="red">*</font>学年</th>
		<th><font color="red">*</font>テキストブック名</th>
		<th>金額</th>
		<th><font color="red">*</font>教室</th>
		<th><font color="red">*</font>種類</th>
	</tr>
	<tr>
		<td><?=$selected_item_no?></td>
		<td align="center">
			<input type="text" name="year" size="2" maxlength="4" value="<?=$buying_array["year"]?>">年
		</td>
		<td align="center">
			<input type="text" name="month" size="1" maxlength="2" value="<?=$buying_array["month"]?>">月
		</td>
		<td align="center">
			<input type="text" name="input_year" size="2" maxlength="4" value="<?=$buying_array["input_year"]?>">年
		</td>
		<td align="center">
			<input type="text" name="input_month" size="1" maxlength="2" value="<?=$buying_array["input_month"]?>">月
		</td>
		<td align="center">
			<input type="text" name="input_day" size="1" maxlength="2" value="<?=$buying_array["input_day"]?>">日
		</td>
		<td align="center">
			<?php disp_pulldown_menu($text_subject_name_list,"text_subject","{$buying_array['text_subject']}",'document.forms["buying_textbook_form"].submit()'); ?>
		</td>
		<td align="center">
			<?php disp_pulldown_menu1($grade_list,"grade","{$grade_list[$buying_array['grade']]}",'document.forms["buying_textbook_form"].submit()'); ?>
		</td>
		<td align="center">
			<input type="hidden" name="buying_no" value="<?=$buying_array["buying_no"]?>">
			<?php
				if ($text_id || !is_null($buying_array['text_subject_id']) || !$buying_array["name"]) {
					disp_pulldown_menu($text_name_list,"name", "{$buying_array["name"]}",'document.forms["buying_textbook_form"].submit()');
				} else {
			?>
			<input type="text" name="name" size="60" value="<?= $buying_array['name'] ?>">
			<?php
				}
			?>
		</td>
		<td align="right">
			<input type="text" name="price" size="4" value="<?=$buying_array["price"]?>">
		</td>
		<td align="center">
			<?php disp_lesson_menu($lesson_list, "lesson_id", $buying_array["lesson_id"]) ?>
		</td>
		<td align="center">
			<?php disp_kind_menu($kind_list, "kind", "1", $buying_array["kind"]) ?>
		</td>
	</tr>
</table>
<table>
	<tr>
  <td align="center">
		<input type="submit" name="add" value="<?= $buying_array["buying_no"]?'登録変更':'登録追加' ?>">
		<input type="button" value="リセット" onclick="location.href='./buying_textbook_edit2.php?no=<?= $student_no ?>'">
<?php if ($buying_array["buying_no"]) { ?>
		<input type="submit" name="delete" value="削除" onclick="delete_buying()">
<?php } ?>
	</td>
	</tr>
</table>

<input type="hidden" name="selected_item_no" value="<?=$selected_item_no?>">
<input type="hidden" name="selected_buying_no" value="">
</form>

<?php
if (count($buying_textbook_list) > 0) {
?>
<hr>
<br>
<table border="1">
	<tr>
		<th>番号</th><th>請求年月</th><th>記入日</th><th>教科</th><th>学年</th><th>テキストブック名</th><th>金額</th><th>教室</th><th>種類</th><th>&nbsp;</th>
	</tr>
 	<?php
	$item_no = 0;
	foreach ($buying_textbook_list as $item) {
		$item_no++;
	?>
	<tr>
		<td><?=$item_no?></td>
		<td align="center" width="110"><input type="hidden" name="buying_no[]" value="<?=$item["buying_no"]?>"><?=$item["year"]?>年<?=$item["month"]?>月</td>
		<td align="center" width="150"><?=$item["input_year"]?>年<?=$item["input_month"]?>月<?=$item["input_day"]?>日</td>
		<td align="left"><?=$text_subject_name_list[$item["text_subject_id"]]?></td>
		<td align="left"><?=$item["grade"]?$grade_list[$item["grade"]]:""?></td>
		<td align="left"><?=$item["name"]?></td>
		<td align="right"><?=number_format($item["price"])?> 円</td>
		<td align="center"><?=$lesson_list[$item["lesson_id"]]?></td>
		<td align="center"><?=$kind_array[$item["kind"]]?></td>
		<td align="center">
			<?php if ($item["buying_no"]) { ?>
				<input type="button" value="選択" onclick="selected_buying(<?=$item_no?>,<?=$item["buying_no"]?>)">
			<?php } ?>
		</td>
	</tr>
 	<?php
	}
	?>
</table>
<?php
}
?>

<?php if ($lms_mode) { ?>
<br><input type="button" onclick="document.location='student_fee_list.php?student_id=<?=$student_no?>'" value="戻る">
		<input type="button" onclick="window.close()" value="閉じる">
<?php } ?>

</div>

</body>
</html>
