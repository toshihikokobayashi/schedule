<?php
ini_set( 'display_errors', 0 );
require_once("./const/const.inc");
require_once("./func.inc");
require_once("./const/login_func.inc");
require_once("./calculate_fees.php");
//$result = check_user($db, "1");

$errArray = array();
$errFlag = 0;

$year = $_POST['y'];
$month = $_POST['m'];
$teacher_id = $_POST['tid'];
if (is_null($year) === true || empty($year) === true)   { $year = $_GET['y']; }
if (is_null($month) === true || empty($month) === true) { $month = $_GET['m']; }
if (is_null($teacher_id) === true || empty($teacher_id) === true) { $teacher_id = $_GET['tid']; }


$date_list_string = "("; $flag=0;
foreach ($date_list as $item) {
	if (str_replace('/0','/',substr($item,0,7)) != "$year/$month") { continue; }
	if ($flag==0) { $date_list_string .= "'$item'"; } else { $date_list_string .= ",'$item'"; }
	$flag = 1;
}
$date_list_string = $date_list_string.")";

$course_list = get_course_list($db);

try {
	
// 先生一覧を取得
$teacher_list = get_teacher_list($db, array(), array(), array());

//if ($teacher_list[$teacher_id]['lesson_id'] != 2) {
	define(STR_SHUSSEKIBO,             '出席簿');
	define(STR_YEN,                    '円');
	define(STR_SHUSSEKI,               '出席');
	define(STR_FURIKAE,                '振替');
	define(STR_TOUJITSU,               '当日');
	define(STR_KYUUKOU,                '休講');
	define(STR_YASUMI,                 'お休み');
	define(STR_CHANGE_CONFIRM,         'カレンダー設定を変更してよろしいですか？ ');
	define(STR_FURIKAE_CONFIRM1,       '毎週繰り返し予定の授業ではありません。「振替」ではなく「出席」でよろしいですか？');
	define(STR_FURIKAE_CONFIRM2,       '毎週繰り返し予定の授業です。「出席」ではなく「振替」でよろしいですか？');
	define(STR_OVERLOAD_ERROR,         '過負荷エラー発生、再登録してください。');
	define(STR_YEAR,                   '年');
	define(STR_MONTH,                  '月');
	define(STR_PREVIOUS_MONTH,         '前月');
	define(STR_NEXT_MONTH,             '翌月');
	define(STR_YASUMI1,                'お休み１');
	define(STR_YASUMI2,                'お休み２');
	define(STR_CALENDAR_ERROR,         'カレンダー登録エラー');
	define(STR_CALENDAR_NAME,          'カレンダー名');
	define(STR_DATE,                   '日付');
	define(STR_START_TIME,             '開始時間');
	define(STR_END_TIME,               '終了時間');
	define(STR_TITLE,                  'タイトル');
	define(STR_ERROR,                  'エラー');
	define(STR_PAYMENT_DISPLAY_SWITCH, '給与表示ON/OFF');
	define(STR_LOGOUT,                 'ログアウト');
	define(STR_TIME,                   '時刻');
	define(STR_HOURS,                  '時間');
	define(STR_KYOUSHITSU,             '教室');
	define(STR_KAMOKU,                 '科目');
	define(STR_COURSE,                 'コース');
	define(STR_NAME,                   '生徒名');
	define(STR_ATTANDANCE,             '生徒出欠');
	define(STR_CALNDAR_STATUS,         'カレンダー登録');
	define(STR_WAGE,                   '時給');
	define(STR_PAYMENT,                '給与');
	define(STR_COMMENT1,               '＊赤字はお休みの生徒です。');
	define(STR_COMMENT2,               '＊青字は体験生徒です。');
	define(STR_COMMENT3,               '＊背景淡緑色は毎週繰り返し予定でないスポットの授業です。');
/*} else {
	$attendStatusList = $attendStatusList_eng;
	$weekday_array = $weekday_array_eng;
	define(STR_SHUSSEKIBO,             'Attendance record');
	define(STR_YEN,                    'yen');
	define(STR_SHUSSEKI,               'Attend');
	define(STR_FURIKAE,                'make-up');
	define(STR_TOUJITSU,               'Today');
	define(STR_KYUUKOU,                'No class');
	define(STR_YASUMI,                 'Absent');
	define(STR_CHANGE_CONFIRM,         'Aye you sure you change calendar status? ');
	define(STR_FURIKAE_CONFIRM1,       'It is not a regularly class. Are you sure you select Absent instead of make-up?');
	define(STR_FURIKAE_CONFIRM2,       'It is a regularly class. Are you sure you select make-up instead of Absent?');
	define(STR_OVERLOAD_ERROR,         'Overload error. Please retry.');
	define(STR_YEAR,                   '/');
	define(STR_MONTH,                  '');
	define(STR_PREVIOUS_MONTH,         'Previous month');
	define(STR_NEXT_MONTH,             'Next month');
	define(STR_YASUMI1,                'Absent1');
	define(STR_YASUMI2,                'Absent2');
	define(STR_CALENDAR_ERROR,         'Calendar error');
	define(STR_CALENDAR_NAME,          'Calendar name');
	define(STR_DATE,                   'date');
	define(STR_START_TIME,             'Start time');
	define(STR_END_TIME,               'End time');
	define(STR_TITLE,                  'Title');
	define(STR_ERROR,                  'Error');
	define(STR_PAYMENT_DISPLAY_SWITCH, 'Payment display ON/OFF');
	define(STR_LOGOUT,                 'Logout');
	define(STR_TIME,                   'Time');
	define(STR_HOURS,                  'Hours');
	define(STR_KYOUSHITSU,             'Class');
	define(STR_KAMOKU,                 'Subject');
	define(STR_COURSE,                 'Course');
	define(STR_NAME,                   'Name');
	define(STR_ATTANDANCE,             'Attndance');
	define(STR_CALNDAR_STATUS,         'Calendar data');
	define(STR_WAGE,                   'Wage');
	define(STR_PAYMENT,                'Payment');
	define(STR_COMMENT1,               '* Red name is an absent student.');
	define(STR_COMMENT2,               '* Blue name is an trial student.');
	define(STR_COMMENT3,               '* Light green is a spot (irregularly) class.');
}
*/

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="robots" content="index,follow">
<link rel="stylesheet" type="text/css" href="../../sakura/schedule/script/style.css">
<style type="text/css">
<!--
#loading-view {
 /* 領域の位置やサイズに関する設定 */
 width: 100%;
 height: 100%;
 z-index: 9999;
 position: fixed;
 top: 0;
 left: 0;
 /* 背景関連の設定 */
 background-color: #FFFFFF;
 opacity: 0.5;
 background-image: url(./loading.gif);
 background-position: center center;
 background-repeat: no-repeat;
 background-attachment: fixed;
}
 -->
</style>
<script type = "text/javascript">
<!--
var loadingflag=0;
function loadingView(flag) {
	if (loadingflag) return;
	if (flag) {
		document.getElementById('loading-view').style.display = 'block';
	} else {
		document.getElementById('loading-view').style.display = 'none';
	}
}

var wage_list = [],diff_hours_list = [];
var URIstr=['','','','','','','','','',''];
var pay_display_flag = 1;
function pay_display() {
	var pay = document.getElementsByName('pay');
	for (var i=0;i<pay.length;i++) {
		pay[i].style.display = (pay_display_flag)? "":"none";
	}
	pay_display_flag = 1-pay_display_flag;
	return false;
}
function update_totalpay() {
	var sum=0;
	for (var i=1;i<wage_list.length;i++) {
		var str = document.getElementById('pay'+i).innerHTML.replace("<?= STR_YEN ?>","");
		if (str) { sum += parseFloat(str); }
	}
	document.getElementById('pay_total').innerHTML = Math.floor(sum)+"<?= STR_YEN ?>";
}
function set_pay(id1){
	var stSelect = document.getElementsByName('stSelect'+id1);
	var flag1=0,flag2=0,flag3=1;
	var pay_hours = parseFloat(diff_hours_list[id1]);
	var wage = parseInt(wage_list[id1]);
	if (stSelect.length) {
		for (var i=0;i<stSelect.length;i++) {
			var str = stSelect[i].options[stSelect[i].selectedIndex].value;
			if (str.indexOf("<?= STR_SHUSSEKI ?>") !== -1) { flag1=1; }
			if (str.indexOf("<?= STR_FURIKAE  ?>") !== -1) { flag1=1; }
			if (str.indexOf("<?= STR_TOUJITSU ?>") !== -1) { flag2=1; }
			if (str=='') { flag3=0; }
		}
		if (flag1 == 0) { 
			if (flag2) {
				wage *= 0.6; if (wage<1000) wage=1000; 
			} else {
				wage = 0;
			}
		}
	}
	if (stSelect.length>0 && !flag3) {
		document.getElementById('pay'+id1).innerHTML = '';
	} else {
		document.getElementById('pay'+id1).innerHTML = (wage * pay_hours) + "<?= STR_YEN ?>";
	}
}
function set_attendance(obj, cal_id, event_id, name, old_index, year, month, date, time, teacher_id, member_no, recurringEvent, id1, id2, trial) {
	var i;
	var new_st = obj.options[obj.selectedIndex].value;
	if (!obj.current_index) { obj.current_index = old_index+1; }
	var old_st = obj.options[obj.current_index-1].value;
	var cal_st = document.getElementById('cal'+id2);
	if  (cal_st) {
		if (!(cal_st.innerHTML == '' && new_st == "<?= STR_SHUSSEKI ?>") && (cal_st.innerHTML != new_st)) {
			if (!confirm("<?= STR_CHANGE_CONFIRM ?>"+cal_st.innerHTML+"->"+new_st)) {
				obj.selectedIndex = obj.current_index-1; return;
			}
		}
		if  (!recurringEvent && new_st=="<?= STR_SHUSSEKI ?>" && !trial) {
			if (!confirm("<?= STR_FURIKAE_CONFIRM1 ?>")) {
				obj.selectedIndex = obj.current_index-1; return;
			}
		}
		if  (recurringEvent && new_st=="<?= STR_FURIKAE  ?>") {
			if (!confirm("<?= STR_FURIKAE_CONFIRM2 ?>")) {
				obj.selectedIndex = obj.current_index-1; return;
			}
		}
	}
	var flag=1;
	for (i=0;i<10;i++) { if (URIstr[i]!='') { flag=0; } }
	for (i=0;i<10;i++) {
		if (URIstr[i]=='') {
			URIstr[i] = encodeURI(
				'./set_attendance.php?cal_id='+cal_id+'&event_id='+event_id+'&name='+name+'&old_st='+old_st+'&new_st='+new_st+
				'&year='+year+'&month='+month+'&date='+date+'&time='+time+'&teacher_id='+teacher_id+'&member_no='+member_no+
				'&seq_no='+i);
//    var div_element = document.createElement("span");
//    div_element.innerText ='+'+i+'+';
// 		document.getElementById('debug').appendChild(div_element);
			break;
		}
	}
	if (i>=10) { alert("<?= STR_OVERLOAD_ERROR ?>"); loadingView(true); loadingflag=1; location.reload(); }
	if (i>5) {
		loadingView(true);
	}
	if (flag) {
			document.getElementsByName('frame1')[0].contentWindow.location.replace( URIstr[i] );
	}
	obj.current_index = obj.selectedIndex+1;
	document.getElementById('name'+id2).style.color = (new_st.indexOf("<?= STR_YASUMI ?>")!=-1)? "red": "black";
	if (trial) { document.getElementById('name'+id2).style.color = "blue"; }
	if (cal_st) { cal_st.innerHTML = (new_st!="<?= STR_SHUSSEKI ?>")? new_st: ''; }

	set_pay(id1);
	update_totalpay();
}

function set_attendance_done(seq_no) {
	var i;
	URIstr[seq_no] = ''; 
//    var div_element = document.createElement("span");
//    div_element.innerText ='-'+seq_no+'-';
// 		document.getElementById('debug').appendChild(div_element);
	for (i=0;i<10;i++) {
		if (URIstr[i]!='') {
			document.getElementsByName('frame1')[0].contentWindow.location.replace( URIstr[i] );
			return;
		}
	}
	loadingView(false);
}
//-->
</script>
</head>
<body>
<div id="content" align="center">

<h3>給与計算詳細</h3>
<h3><?= $teacher_list[$teacher_id]["name"] ?></h3>
<h3><?= $year.STR_YEAR.$month.STR_MONTH ?></h3>
<br>
<div id="loading-view" style="display:none"></div>
<iframe name="frame1" width=1000 height=400 style="display:none;"></iframe>
<div id="debug"></div>
<?php

$sql = "SELECT e.cal_evt_summary, e.cal_id, e.course_id, e.event_end_timestamp, e.event_start_timestamp, ".
		"e.grade, e.lesson_id, e.member_cal_name, e.member_no, e.recurringEvent, e.subject_id, e.trial_flag, ".
		"e.absent_flag, m.name, m.furigana, m.grade, e.grade as tgrade ".
		"FROM tbl_event e,tbl_member m ".
		"where e.member_no=m.no and e.event_year=? and e.event_month=? and e.teacher_id=? ".
		"order by e.event_start_timestamp, e.cal_evt_summary";
$stmt = $db->prepare($sql);
$stmt->execute(array($year, $month, $teacher_id));
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
	
	$name = $event["name"];
	$attendStatusCal[$event['date']][$event['time']][$name] = '';
	$str0 = str_replace(array('　','（','）','：','︰'), array(' ','(',')',':',':'), $event['cal_evt_summary']);
	$ret = preg_match_all('/\((.*?)\)/u', $str0, $blocks);
	if (!$ret) { $blocks[1]=array($str0); }
	if (strpos($str0,'ファミリー')===false) {
		foreach ($blocks[1] as $key=>$block) {
			$ret = preg_match( '/([^():]+?)様([A-Za-z ]+)?/u', $block, $name_cal );
			if (!$ret) { continue; }
			if (str_replace(' ','',$name) != str_replace(' ','',$name_cal[1])) { continue; }
			$event['eng_name'] = $name_cal[2];
			if (preg_match('/休み[1１]\s*:/u',$block)) { $attendStatusCal[$event['date']][$event['time']][$name] = '休み１'; }
			if (preg_match('/休み[2２]\s*:/u',$block)) { $attendStatusCal[$event['date']][$event['time']][$name] = '休み２'; }
			if (preg_match('/振替\s*:/u',$block))      { $attendStatusCal[$event['date']][$event['time']][$name] = '振替'; }
			if (preg_match('/:\s*当日/u',$block))      { $attendStatusCal[$event['date']][$event['time']][$name] .= '当日'; }
			if (preg_match('/:\s*休講/u',$block))      { $attendStatusCal[$event['date']][$event['time']][$name] .= '休講'; }
			if (preg_match('/absent1\s*:/iu',$block))  { $attendStatusCal[$event['date']][$event['time']][$name] = 'Absent1'; }
			if (preg_match('/absent2\s*:/iu',$block))  { $attendStatusCal[$event['date']][$event['time']][$name] = 'Absent2'; }
			if (preg_match('/alternative\s*:/iu',$block)) { $attendStatusCal[$event['date']][$event['time']][$name] = 'make-up'; }
			if (preg_match('/make.?up\s*:/iu',$block)) { $attendStatusCal[$event['date']][$event['time']][$name] = 'make-up'; }
			if (preg_match('/:\s*today/iu',$block))    { $attendStatusCal[$event['date']][$event['time']][$name] .= 'Today'; }
		}
	} else {
		$str0 = $blocks[1][0];
		$ret = preg_match_all('/(\S+)/u', $str0, $blocks);
		if (!$ret) { $blocks[1]=array($str0); }
		$flag = 0;
		$event['eng_name'] = '';
		foreach ($blocks[1] as $key=>$block) {
			if ($block == '様') { $flag = 1; continue; }
			if ($flag == 0) {
				$name0 = $block;
				$tmp = preg_replace( "/(\s*休み[12１２]\s*:\s*|\s*振替\s*:\s*|:\s*当日|:\s*休講)/u", "", $name0 );
				if ($tmp) {$name0=$tmp;}
				$tmp = preg_replace( "/(\s*absent[12]\s*:\s*|\s*alternative\s*:\s*|\s*make.?up\s*:\s*|:\s*today)/iu", "", $name0 );
				if ($tmp) {$name0=$tmp;}
				if ($key==0) {
					$family_name = $name0;
				} else {
					$name0 = $family_name.' '.$name0;
					$attendStatusCal[$event['date']][$event['time']][$name0] = '';
					if (preg_match('/休み[1１]\s*:/u',$block)) { $attendStatusCal[$event['date']][$event['time']][$name0] = '休み１'; }
					if (preg_match('/休み[2２]\s*:/u',$block)) { $attendStatusCal[$event['date']][$event['time']][$name0] = '休み２'; }
					if (preg_match('/振替\s*:/u',$block))      { $attendStatusCal[$event['date']][$event['time']][$name0] = '振替'; }
					if (preg_match('/:\s*当日/u',$block))      { $attendStatusCal[$event['date']][$event['time']][$name0] .= '当日'; }
					if (preg_match('/:\s*休講/u',$block))      { $attendStatusCal[$event['date']][$event['time']][$name0] .= '休講'; }
					if (preg_match('/absent1\s*:/iu',$block))  { $attendStatusCal[$event['date']][$event['time']][$name0] = 'Absent1'; }
					if (preg_match('/absent2\s*:/iu',$block))  { $attendStatusCal[$event['date']][$event['time']][$name0] = 'Absent2'; }
					if (preg_match('/alternative\s*:/iu',$block)) { $attendStatusCal[$event['date']][$event['time']][$name0] = 'make-up'; }
					if (preg_match('/make.?up\s*:/iu',$block)) { $attendStatusCal[$event['date']][$event['time']][$name0] = 'make-up'; }
					if (preg_match('/:\s*today/iu',$block))    { $attendStatusCal[$event['date']][$event['time']][$name0] .= 'Today'; }
				}
			} else {
				$event['eng_name'] .= $block.' ';
			}
		}
	}
			
	$event["comment"] = $comment;
	$event['diff_hours'] = ($event["event_end_timestamp"] - $event["event_start_timestamp"]) /  (60*60);;
}
unset($event);

// 期間講習追加
$season_exercise = array();
if ($date_list_string != '()') {
	$sql = "SELECT * FROM tbl_season_schedule s LEFT OUTER JOIN tbl_member m ON s.member_no=m.no WHERE s.date IN {$date_list_string} AND s.teacher_no={$teacher_id}";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$rslt = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($rslt as $schedule) {
		$date = str_replace('/','月',substr(str_replace('/0','/',$schedule['date']),5)).'日';
		$event = array();
		$event['course_id']    = $season_course_id;
		$event['course_name']  = $course_list[$event["course_id"]]["course_name"];
		$event['lesson_id']    = $schedule['lesson_id'];
		$event['lesson_name']  = $lesson_list[$schedule['lesson_id']];
		$event['subject_id']   = $schedule['subject_id'];
		$event['subject_name'] = $subject_list[$schedule['subject_id']];
		$event['member_no']    = $schedule['member_no'];
		$event['name']         = $schedule['name'];
		$event['furigana']     = $schedule['furigana'];
		$event['grade']        = $schedule['grade'];
		$event['date']         = $date;
		$event['time']         = "{$schedule['stime']} ～ {$schedule['etime']}";
		$event['event_start_timestamp'] = DateTime::createFromFormat('Y/m/d H:i', "{$schedule['date']} {$schedule['stime']}")->getTimestamp();
		$event['event_end_timestamp']   = DateTime::createFromFormat('Y/m/d H:i', "{$schedule['date']} {$schedule['etime']}")->getTimestamp();
		$event['recurringEvent'] = 0;
		$event['trial_flag']     = 0;
		$event['diff_hours'] = ($event["event_end_timestamp"] - $event["event_start_timestamp"]) / (60*60);
		if ($event['member_no']) {
			$event_list[] = $event;
		} else {
			if (!$season_exercise[$date]) {
				$season_exercise[$date] = array();
				$event_list[] = $event;
			}
			$season_exercise[$date][] = array('stime'=>$schedule['stime'], 'etime'=>$schedule['etime']);
		}
	}
	
	foreach ($event_list as &$event) {
		if (!$event['member_no']) {
			$time_str = ''; $lastetime = '';
			foreach ($season_exercise[$event['date']] as $item) {
				if ($item['stime'] != $lastetime) {
					$time_str .= $lastetime;
					if ($lastetime) { $time_str .= '<br>'; }
					$time_str .= $item['stime'].' ～ ';
				}
				$lastetime = $item['etime'];
			}
			$time_str .= $lastetime;
			$event['time'] = $time_str;
			$event['diff_hours'] = count($season_exercise[$event['date']]) * 0.5;
			$event['lesson_name']  = $lesson_list[1];
			$event['subject_name'] = '演習';
		}
	}
	unset($event);
}

$lesson_array = array();
foreach ($event_list as $key => $value) {
    $sort1[$key] = $value['date'];
    $sort2[$key] = $value['time'];
		$sort3[$key] = $value['cal_evt_summary'];
		$sort4[$key] = $value['furigana'];
		$lesson_array[$value['date']][] = $value['time'].$value['cal_evt_summary'];
}

array_multisort(
	$sort1, SORT_ASC, SORT_NATURAL, $sort2, SORT_ASC, SORT_NATURAL,
	$sort3, SORT_ASC, SORT_NATURAL, $sort4, SORT_ASC, SORT_NATURAL, $event_list );
	
$lesson_count = array();
foreach ($lesson_array as $key=>$item) {
	$lesson_count[$key] = count ( array_unique($item) );
}

$stmt = $db->query(
		"SELECT * FROM tbl_teacher_presence_report ".
		"WHERE teacher_id=\"$teacher_id\" AND year=\"$year\" AND month=\"$month\"");
$ret = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($ret as $item) {
	$attendStatus[$item['date']][$item['time']][$item['name']] = $item['presence'];
}

} catch (Exception $e) {
	// 処理を中断するほどの致命的なエラー
	array_push($errArray, $e->getMessage());
}

function cmp_date_furigana($a, $b) {
	if ($a["event_start_timestamp"] == $b["event_start_timestamp"]) {
		if ($a["furigana"] == $b["furigana"]) {
			return 0;
		}
		return ($a["furigana"] > $b["furigana"]) ? +1 : -1;
		}
	return ($a["event_start_timestamp"] > $b["event_start_timestamp"]) ? +1 : -1;
}

?>
<?php
	if (count($errArray) > 0) {
		foreach( $errArray as $error) {
?>
			<font color="red" size="3"><?= $error ?></font><br><br>
<?php
		}
		exit();
	}
?>
<?php
		foreach( $log as $msg) {
?>
			<font color="red" size="3"><?= $msg ?></font><br><br>
<?php
		}
?>
<div class="menu_box">
<font color="red"><?= STR_COMMENT1 ?></font><br>
<font color="blue"><?= STR_COMMENT2 ?></font><br>
<span style="background-color:#c0ffc0"><?= STR_COMMENT3 ?></span>
</div>
<br>
<table border="1">
<tr>
<th></th><th><?= STR_DATE ?></th><th><?= STR_TIME ?></th><th><?= STR_HOURS ?></th><th><?= STR_KYOUSHITSU ?></th><th><?= STR_KAMOKU ?></th><th><?= STR_COURSE ?></th>
<th><?= STR_NAME ?></th><th><?= STR_ATTANDANCE ?></th><th name="pay"><?= STR_WAGE ?></th><th name="pay"><?= STR_PAYMENT ?></th>
</tr>
<?php
$no=0; $i=0; $member_count = 0; $rowspan=1;
$event = reset($event_list);
while ($event) {
	$diff_hours = $event['diff_hours'];
	$absent_flag_min = 2;
	$todayFlag = 0;
	$DOW = (int)date_format(date_create($year.'-'.str_replace('月', '-', str_replace('日','',$event["date"]))),'w');
	switch ($DOW) {
	case 0: $DOW = "<font color=red>(".$weekday_array[$DOW].")</font>"; break;
	case 6: $DOW = "<font color=blue>(".$weekday_array[$DOW].")</font>"; break;
	default: $DOW = "(".$weekday_array[$DOW].")";
	}
	$bgcolor = ($event['recurringEvent'])? '"#ffffff"': '"#c0ffc0"';
?>
	<tr bgcolor=<?= $bgcolor ?>>
		<td align="left"><?php echo ++$no; ?></td>
<?php
		$rowspan--;
		if (!$rowspan) {
			$rowspan = $lesson_count[$event["date"]];
?>
		<td align="left" style="padding: 0px 10px 0px 10px;" bgcolor="#ffffff" rowspan="<?= $rowspan ?>"><?= str_replace(array('月','日'),array('/',''),$event["date"]).$DOW ?></td>
<?php } ?>
<?php
	$next_event = $event;
	do {
		$event = $next_event;
		if ($event["member_no"]) {
			$name = $event["name"];
			if ($name=='体験生徒') { $name = $event['member_cal_name']; }

			if ($event["course_id"] == 3) {
				$tmp0 = explode(' ',$name);
				$family_name = $tmp0[0]; array_shift($tmp0); $names = array();
				foreach ($tmp0 as $str0) { $names[] = $family_name.' '.$str0; }
//				$tmp0 = explode(' ',$event['eng_name']? $event['eng_name']: eng_name1($kana2romaji->convert($event["furigana"])));
//				$family_name_eng = $tmp0[0]; array_shift($tmp0); $names_eng = array();
//				foreach ($tmp0 as $str0) { $names_eng[] = $family_name_eng.' '.$str0; }
			} else {
				$names = array($name);
//				$names_eng = array( $event['eng_name']? $event['eng_name']: eng_name1($kana2romaji->convert($event["furigana"])) );
			}
			foreach ($names as $key=>$name) {
				$st = $attendStatus[$event["date"]][$event["time"]][$name];
				$st_index = ($st)? array_search($st, $attendStatusList)+1: 0;
				$color = (strpos($st,STR_YASUMI)===false)? "black" : "red" ;
				if ($event["trial_flag"]) { $color = "blue"; }
				$name0 = $name;
				if (($teacher_list[$teacher_id]['lesson_id'] == 2) && $names_eng[$key]) {
					$name0 = $names_eng[$key];
				}
				$nameCol .= "<font id=\"name$i\" color=\"$color\">$name0</font><br>";
				disp_pulldown_menu($attendStatusList, "stSelect{$no}", $st,
					"set_attendance(this,\"{$event['cal_id']}\",\"{$event['event_id']}\",\"$name\",{$st_index},".
					"\"$year\",\"$month\",\"{$event["date"]}\",\"{$event['time']}\",\"$teacher_id\",".
					"\"{$event['member_no']}\",{$event['recurringEvent']}, \"{$no}\", \"{$i}\",{$event['trial_flag']})", $str);
				if (!$name) { $str=''; }
				$stSelect .= "$str<br>";
				$calStatus .= "<span id=\"cal$i\">".$attendStatusCal[$event["date"]][$event["time"]][$name].'</span><br>';
				$i++;
				if (!($event['course_id'] == 2 && $event["trial_flag"])) { $member_count++; }
				if (preg_match('/(当日|today)/iu', $attendStatusCal[$event["date"]][$event["time"]][$name])) { $todayFlag = 1; }
			}
		}
		
		if ($event['absent_flag']<$absent_flag_min) { $absent_flag_min = $event['absent_flag']; }
		$lastdate=$event["date"]; $lasttime=$event["time"]; $last_cal_evt_summary = $event["cal_evt_summary"];
		$next_event = next($event_list);
	} while (($next_event) && ($next_event["date"] == $lastdate) && ($next_event["time"] == $lasttime) && ($next_event["cal_evt_summary"] == $last_cal_evt_summary));
	if ($absent_flag_min>0 && !$todayFlag) { $diff_hours = 0; }
	
	if ($event['course_id'] == 2 && $member_count == 0) { $member_count = 1; }

	if ($todayFlag) {
		$sql = 
			"SELECT * FROM tbl_event ".
			"WHERE event_year=? AND event_month=? AND teacher_id=? AND absent_flag=0 ".
			"AND cal_evt_summary!=? AND (event_start_timestamp BETWEEN ? AND ?) ";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($year,$month,$teacher_id,$event["cal_evt_summary"],$event['event_start_timestamp'],$event['event_end_timestamp']-1));
		$ret = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if ($ret) {
			$array = array();
			for ($i=$event['event_start_timestamp']; $i<$event['event_end_timestamp']; $i+=60) { $array[$i]=1; }
			foreach ($ret as $item) {
				for ($i=$item['event_start_timestamp']; $i<$item['event_end_timestamp']; $i+=60) { $array[$i]=0; }
			}
			$count = 0;
			foreach ($array as $item) { if ($item) { $count++; } }
			$diff_hours = $count/60.0;
		}
	}

?>
		<td align="left" style="padding: 0px 10px 0px 10px;"><?= $event["time"] ?></td>
		<td align="left" style="padding: 0px 10px 0px 10px;"><?= sprintf( "%4.2f", $diff_hours ) ?>h</td>
		<td align="left" style="padding: 0px 10px 0px 10px;"><?= $event["lesson_name"] ?></td>
		<td align="left" style="padding: 0px 10px 0px 10px;"><?= $event["subject_name"] ?></td>
		<td align="left" style="padding: 0px 10px 0px 10px;"><?= $event["course_name"] ?></td>
		<td align="left" style="padding: 0px 10px 0px 10px;"><?= $nameCol ?></td>
		<td align="left" style="padding: 0px 10px 0px 10px;"><?= ($event['course_id']==$season_course_id)?'-':$calStatus ?></td>
		<td name="pay" align="left" style="padding: 0px 10px 0px 10px;">
<?php
	$nameCol = ''; $stSelect = ''; $calStatus = ''; 
	if ($event["member_no"]) {
		
		$wage_no = -1; $lesson_id = $event['lesson_id'];
		switch ($lesson_id) {
		case 1:
			$wage_type_list = $jyuku_wage_type_list;
			if ($event['trial_flag']) {
				$grade = $event['tgrade'];
			} else {
				$grade = $event['grade'];
				if ($grade) {
					if ($year==2018 && $month < 4 && $grade > 1) { $grade--; }
				}
			}
			if ($grade) {
				if ($grade < 8) {
					if ($member_count >= 5) {
						$wage_no = 13;
					} else if ($member_count == 4) {
						$wage_no = 12;
					} else if ($member_count == 3) {
						$wage_no = 11;
					} else if ($member_count == 2) {
						$wage_no = 1;
					} else if ($member_count == 1) {
						$wage_no = 0;
					}
				} else if ($grade <= 10) {
					if ($member_count >= 5) {
						$wage_no = 7;
					} else if ($member_count == 4) {
						$wage_no = 6;
					} else if ($member_count == 3) {
						$wage_no = 5;
					} else if ($member_count == 2) {
						$wage_no = 4;
					} else if ($member_count == 1) {
						if ($grade == 10) {
							$wage_no = 3;
						} else {
							$wage_no = 2;
						}
					}
				} else if ($grade <= 13) {
					if ($member_count == 2) {
						$wage_no = 9;
					} else if ($member_count == 1) {
						if ($grade == 13) {
							$wage_no = 10;
						} else {
							$wage_no = 8;
						}
					}
				}
			}
			break;
		case 2:
			$wage_type_list = $eng_wage_type_list;
			if ($member_count >= 5) { 
				$wage_no = 2;
			} else if ($member_count >= 2) {
				$wage_no = 1;
			} else if ($member_count == 1 ) {
				$wage_no = 0;
			}
			break;
		case 3:
			$wage_type_list = $piano_wage_type_list;
			$wage_no = 0;
			break;
		case 4:
			$wage_type_list = $naraigoto_wage_type_list;
			$wage_no = 0;
		}
		//echo "$lesson_id,$grade,$member_count,$wage_no<br>";
		if ($wage_no>-1) {
			$stmt = $db->query("SELECT * FROM tbl_wage WHERE teacher_id=\"{$teacher_id}\" AND wage_no=\"{$wage_no}\" AND lesson_id=\"{$lesson_id}\"");
			$wage_array = $stmt->fetch(PDO::FETCH_ASSOC);			
			if ($wage_array) {
				$hourly_wage = $wage_array["hourly_wage"];
				if ($hourly_wage) {
					if ($absent_flag_min>0 && $todayFlag) {
						$hourly_wage *= 0.6; if ($hourly_wage<1000) { $hourly_wage=1000; }
					}
					echo "{$hourly_wage}円<br> {$wage_type_list[$wage_no]}";
				} else {
					echo "時給未設定";
				}
			} else {
				echo "時給未設定";
			}
		} else {
			if ($lesson_id == 1 && $grade == '') {
				echo "学年不明";
			} else {
				echo "時給未設定";
			}
		}
	} else {
		$hourly_wage = 1200;
		echo "{$hourly_wage}円";
	}
	$wage_list[$no] = $hourly_wage; $diff_hours_list[$no] = $diff_hours;
?>
		</td>
		<td name="pay" align="right" style="padding: 0px 10px 0px 10px;">
<?php
	$val = $hourly_wage * $diff_hours;
	echo "<span id=\"pay$no\">{$val}円</span><br>";
	
	$member_count = 0; $event = $next_event;
?>
		</td>
	</tr>
<?php
}
?>
	<tr name="pay"><td colspan="10"></td><td align="right"><span id="pay_total"></span></td></tr>
</table><br><br>
</form>
</div>

<script type = "text/javascript">
<!--
<?php
foreach ($wage_list as $key=>$wage) { echo "wage_list[$key]=$wage; diff_hours_list[$key]={$diff_hours_list[$key]};"; }
$i--;
//echo "pay_display();";
//echo "for (var i=1;i<={$no};i++) { set_pay(i); }";
echo "update_totalpay();";
?>
		document.getElementById('loading-view').style.display = 'none';

//-->
</script>

</body></html>

