<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
$result = check_user($db, "1");

$course_list = get_course_list($db);

if ($_POST["search_name"]) {
	$search_name = trim($_POST["search_name"]);
}
if ($_POST["gensensei"]=='0') {
	$gensensei = '0';
} else {
	$gensensei = '1';
}
if ($_POST["zensensei"]=='1') {
	$zensensei = '1';
} else {
	$zensensei = '0';
}

$param_array = array();
$value_array = array();
if ($search_name) {
	array_push($param_array," tbl_teacher.name like concat('%',?,'%') ");
	array_push($value_array, $search_name);
}

if ($lms_mode) {
	if (preg_match('/^1.....$/', $_GET['teacher_id']))
		$teacher_id = preg_replace('/^10*/', '', $_GET['teacher_id']);
	else
		$teacher_id = '';
	array_push($param_array," tbl_teacher.no = '$teacher_id' ");
}

// 20150816 ふりがなの50音順にソートする
$order_array = array("tbl_teacher.furigana asc");

// 先生一覧を取得
if ($gensensei=='1' && $zensensei=='0') {
	$all_flag = "0";	// 現先生を抽出
	$teacher_list = get_teacher_list($db, $param_array, $value_array, $order_array, $all_flag);
} else if ($gensensei=='1' && $zensensei=='1') {
	$all_flag = "1";	// 前先生と現先生を抽出
	$teacher_list = get_teacher_list($db, $param_array, $value_array, $order_array, $all_flag);
} else if ($gensensei=='0' && $zensensei=='1') {
	$all_flag = "3";	// 前先生を抽出
	$teacher_list = get_teacher_list($db, $param_array, $value_array, $order_array, $all_flag);
}
$now_count = count($teacher_list);

$tatekae_list = get_tatekae_list($db);

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF8">
<meta name="robots" content="noindex,nofollow">
<title>事務システム</title>
<script type = "text/javascript">

function edit_teacher(no) {
	document.forms["teacher_list"].elements["no"].value = no;
	document.forms["teacher_list"].action = "teacher_edit.php";
	document.forms["teacher_list"].submit();
}

function entry_teacher(no,class_type) {
	document.forms["teacher_list"].elements["no"].value = no;
	document.forms["teacher_list"].elements["class_type"].value = class_type;
	document.forms["teacher_list"].action = "season_class_teacher_entry.php";
	document.forms["teacher_list"].submit();
}

function payadj_teacher(no) {
	document.forms["teacher_list"].elements["no"].value = no;
	document.forms["teacher_list"].action = "payadj_edit.php";
	document.forms["teacher_list"].submit();
}

function tatekae_teacher(no) {
	document.forms["teacher_list"].elements["no"].value = no;
	document.forms["teacher_list"].action = "tatekae_edit.php";
	document.forms["teacher_list"].submit();
}

function search_clear() {
	document.forms["teacher_list"].elements["search_name"].value = "";
	document.forms["teacher_list"].submit();
}

function dispPass(flag) {
	if(flag) {
		document.forms["teacher_list"].elements["pass"].value = "1";
	} else {
		document.forms["teacher_list"].elements["pass"].value = "";
	}
	document.forms["teacher_list"].submit();
}

function dispGensensei(flag) {
	if(flag) {
		document.forms["teacher_list"].elements["gensensei"].value = "1";
	} else {
		document.forms["teacher_list"].elements["gensensei"].value = "0";
	}
	document.forms["teacher_list"].submit();
}

function dispZensensei(flag) {
	if(flag) {
		document.forms["teacher_list"].elements["zensensei"].value = "1";
	} else {
		document.forms["teacher_list"].elements["zensensei"].value = "0";
	}
	document.forms["teacher_list"].submit();
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

<?php if ($lms_mode) { ?>
<h3>先生の登録</h3>
<?php } else { ?>
<h3>先生の登録 - 先生一覧</h3>

<a href="teacher_add.php">新規登録へ</a>&nbsp;&nbsp;
<a href="menu.php">メニューへ戻る</a><br><br>
<?php } ?>

<form method="post" name="teacher_list" action="teacher_list.php">
	<input type="hidden" name="type" value="<?= TEACHER ?>">
	<input type="hidden" name="no" value="">	<?php /* 編集ボタンクリック時にteacher_noをセットする */ ?>
	<input type="hidden" name="pass" value="">
	<input type="hidden" name="gensensei" value="<?= $gensensei ?>">
	<input type="hidden" name="zensensei" value="<?= $zensensei ?>">
	<input type="hidden" name="class_type" value="">

<?php if (!$lms_mode) { ?>
<table border="1">
	<tr>
		<th>氏名</th>
		<td><input type="text" name="search_name" value="<?= $search_name; ?>">※氏名の部分一致で検索します</td>
	</tr>
	<tr>
		<td colspan="4" align="center">
			<input type="submit" value="検索">&nbsp;&nbsp;
			<input type="button" value="検索解除" onclick="search_clear()">
		</td>
	</tr>
</table>

<br>
<br>
表示先生：&nbsp;&nbsp;<input type="checkbox" onclick="dispGensensei(this.checked)" <?php if ($gensensei=="1") echo "checked"; ?>>現先生&nbsp;&nbsp;
<input type="checkbox" onclick="dispZensensei(this.checked)" <?php if ($zensensei=="1") echo "checked"; ?>>前先生&nbsp;&nbsp;（前先生の欄はグレーで表示されます。）
<br>
<?= $now_count?>件&nbsp;<br>
<?php } ?>

<table border="1">
		<tr>
<th>氏名</th>
<th>　教室　</th>
<th>メールアドレス</th>
<th>編集</th>
<th>期間講習・土日講習</th>
<th>給与調整</th>
<th>立替経費</th>
		</tr>
	<?php
			foreach ($teacher_list as $teacher_id=>$item) {
			
			if ($item["del_flag"]=='0') {
				echo "<tr>";
			} else {
				echo "<tr bgcolor=\"#eeeeee\">";
			}
			
			$tatekae_button_color = '';
			foreach ($tatekae_list as $item0) {
				if ($item0['employee_type']==TEACHER && $item0['employee_no']==$teacher_id)
					if ($item0['status'] == '承認待ち') $tatekae_button_color = 'style="background-color:red;"';
			}
	?>
			<td><?= $item["name"] ?></td>
			<td align="center">
			<?php
							echo $lesson_list[$item["lesson_id"]];
							if ($item["lesson_id2"]) { echo ",".$lesson_list[$item["lesson_id2"]]; }
			?>
			</td>
			<td align="left"><?= $item["mail_address"] ?></td>
			<td align="center">
			<input type="button" value="編集" onclick="edit_teacher('<?= $item['no']?>')" target="_blank"> <?php /* ボタンにはnameを付けないこと。jsに渡すnoは'で囲むことStringとして渡すため*/ ?>
			</td>
			<td align="center">
			<input type="button" value="期間講習・土日講習登録" onclick="entry_teacher('<?= $item['no']?>','sat_sun_class')" target="_blank"> <?php /* ボタンにはnameを付けないこと。jsに渡すnoは'で囲むことStringとして渡すため*/ ?>
			</td>
			<td>
			<input type="button" value="給与調整登録" onclick="payadj_teacher('<?= $item['no']?>')" target="_blank"> <?php /* ボタンにはnameを付けないこと。jsに渡すnoは'で囲むことStringとして渡すため*/ ?>
			</td>
			<td>
			<input type="button" value="立替経費登録" onclick="tatekae_teacher('<?= $item['no']?>')" target="_blank" <?= $tatekae_button_color ?>>
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
