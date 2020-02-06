<?php

$member_list = get_simple_member_list($db);
$course_list = get_course_list($db);

//$cal_calArray = array();
//$calender_auth = new GoogleCalenderAuth();
//$service = $calender_auth->getCalenderService();

if ($nocheck) {
	$result=true;
	$nocheckoption = "&nocheck=1";
}

if ($result == false) {
	$err_flag = true;
	$errArray[] = "セッションエラーが発生しました";
}

try {

if ($result) {
	// イベントテーブルのデータをすべて削除
	$result = delete_event($db, $year, $month);
	// 履歴テーブルにある対象年月のデータを削除
	//$result = delete_history($db, $year, $month);
	$sql = "DELETE FROM tbl_event_staff WHERE event_year = ? AND event_month = ?";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($year, $month));
}

if ($result) {
	// 20160810堀内修正　引数に$yearと$monthを追加
	$calender_event_list = get_calender_event_list($db, $year, $month);

	// カレンダーの予定ごとに処理をする
  foreach ($calender_event_list as $event) {

		// 2016/03/31
		$tmp_event_summary = str_replace(array("　", "（", "）", "：", "︰", "＊"), array(" ", "(", ")", ":", ":", "*"), trim($event['event_summary']));
		if (preg_match("/^\*/", $tmp_event_summary, $matches, PREG_OFFSET_CAPTURE) == 1) {
		// 20160306 先頭に＊がある場合は処理をとばす
			$asterisk_array[] = $tmp_event_summary;
			continue;
		}
		else if ($kari_ignore === "1" && preg_match("/(\(仮\)|\(not\s*defin|\(not\s*confirm|temporary)/i", $tmp_event_summary, $matches, PREG_OFFSET_CAPTURE) == 1) {
		// 20160306
		// 仮を無視するが選択されている場合
		// 「(仮)」があれば無視する
		// 20160522「not defined」があれば無視する
			continue;
		}

		if (mb_strpos($event['calender_summary'], "事務") !== FALSE) {
			
			$start_timestamp = $event['event_start_timestamp'];
			$end_timestamp = $event['event_end_timestamp'];
			$diff_hours = ($end_timestamp - $start_timestamp) / (60*60);
			$absent_flag = 0;
			$blocks = explode(':', $tmp_event_summary);
			$staff_no = ''; $staff_name = '';
			foreach ($blocks as $block) {
				if (preg_match("/(\S+ \S+) さん/u", $block, $matches) == 1) {
					$staff_name = $matches[1];
					$sql = "SELECT no FROM tbl_staff WHERE name = ?";
					$stmt = $db->prepare($sql);
					$stmt->execute(array($staff_name));
					$staff_no = $stmt->fetch(PDO::FETCH_NUM)[0];
				} else if (preg_match("/休み/u", $block, $matches) == 1) {
					$absent_flag = 1;
				}
			}
			
			$sql = 
				"INSERT INTO tbl_event_staff ".
				"(event_id, staff_no, staff_cal_name, event_year, event_month, event_day, event_start_timestamp, event_end_timestamp, event_diff_hours, place_id, absent_flag,".
				" cal_id, cal_summary, cal_evt_summary, cal_evt_location, cal_evt_description, cal_evt_updated_timestamp, insert_datetime, update_datetime) VALUES".
				"(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,now(),now())";
			$stmt = $db->prepare($sql);
			$stmt->execute(array(
				$event["event_id"], $staff_no, $staff_name, $year, $month, date("j", $start_timestamp), $start_timestamp, $end_timestamp, $diff_hours, 0, $absent_flag,
				$event["calender_id"], $event["calender_summary"], $event["event_summary"], $event["event_location"], $event["event_description"], $event['event_updated_timestamp']
				));
			
			continue;
		}

		// カレンダーの予定ごとから生徒ごとの配列に変換する
		$event_param_array = get_event_param($db, $event, $errArray, $target_teacher_id);
		// 20150826 エラーがあれば処理を中断する
		if ($event_param_array === false) {
			if ($target_teacher_id) { continue; }
			$err_flag = true;
     	break;
		}

		foreach ($event_param_array as $event_param) {
			$event_no = insert_event($db, $event_param);
      if ($event_no === false) {
				echo "insert_event:エラー発生";
				$err_flag = true;
			  //$db->rollBack();
       	break;
			}
			//$result = insert_history($db, $event_no, $event_param);
      //if ($result === false) {
			//	echo "insert_history:エラー発生";
			//	$err_flag = true;
			  //$db->rollBack();
      // 	break;
			//}
		}
		if ($err_flag == true) break;

	}

}// End:if ($result)

}
catch(PDOException $e) {
	$err_flag = true;
	array_push($errArray, "エラーが発生しました。" . $e->getMessage());
}

// ****** メイン処理ここまで ******


// １つのイベント（カレンダーに入力された予定）ごとに処理をする
// イベントデータが生徒の時は、生徒情報の配列を返す
// イベントデータが先生の時は、nullを返す
function get_event_param($db, $event, &$errArray, $target_teacher_id) {
	global $kari_ignore;

	mb_regex_encoding("UTF-8");
	$param_array = array();
	$errMessage = "";

	//global $member_list;
	global $place_list;
	global $course_list;
	global $year;
	global $month;

//array(12) { ["event_no"]=> string(4) "1247" ["event_id"]=> string(20) "3opqltd18d74nv87biq7" 
//["event_start_timestamp"]=> NULL ["event_end_timestamp"]=> NULL ["calender_id"]=> string(52) "o9qcbs2l2bv8v15vbjjri9u3oc@group.calendar.google.com" 
//["calender_summary"]=> string(17) "習い事_駅前" 
//["event_summary"]=> string(80) "グループ（坂田 青之輔 様)(清水 様：無料体験）：そろばん" 
//["event_location"]=> string(0) "" ["event_description"]=> string(0) "" 
//["event_updated_timestamp"]=> string(24) "2015-08-27T16:00:14.920Z" ["insert_datetime"]=> string(19) "2015-09-09 12:17:34" ["update_datetime"]=> NULL } 

	// ■初期処理
	// 全角のスペース、かっこ、セミコロン、アスタリスクを半角に
	$event_summary = str_replace(array("　", "（", "）", "：", "︰", "＊"), array(" ", "(", ")", ":", ":", "*"), trim($event['event_summary']));
	// 20150817 追加
	//$event_summary = str_replace(array("　", "（", "）", "：","１","２"), array(" ", "(", ")", ":", "1","2"), trim($event['summary']));
	$event_summary = mb_convert_kana($event_summary, "n", "utf-8");
	$event_description = str_replace("　", " ", trim($event['event_description']));
	$event_location = str_replace(array("　","１","２","３"), array(" ","1","2","3"), trim($event['event_location']));


	$monthly_fee_flag = 0;
	if (mb_strpos($event_summary, ":月謝") !== FALSE) { $monthly_fee_flag = 1; }
	
	// 20160423 先生を追加
	// ■先生IDを取得
	$teacher_list = get_teacher_list($db);
	$teacher_id = 0;
	$tmp_event_summary = str_replace(array("　", "（", "）", "：", "︰", "＊"), array(" ", "(", ")", ":", ":", "*"), trim($event['event_summary']));
	$tmp_event_summary = str_replace(array(" "), array(""), $tmp_event_summary);// 半角スペースも除去
	
	// 20200122 名前の変更
	//  旧：Nix Cruz　→　新：Nina Cruz
	//  旧：田中 要介　→　新：田中 耕一郎
	$tmp_event_summary = str_replace(array("NixCruz"), array("NinaCruz"), $tmp_event_summary);
	$tmp_event_summary = str_replace(array("田中要介"), array("田中耕一郎"), $tmp_event_summary);
	
	foreach ($teacher_list as $teacher) {
		$name = str_replace(array("　", "（", "）", "：", "︰", "＊"), array(" ", "(", ")", ":", ":", "*"), trim($teacher["name"]));
		$name = str_replace(array(" "), array(""), $name);// 半角スペースも除去
		if (mb_strpos($tmp_event_summary, $name) !== FALSE) {
	   	$teacher_id = $teacher["no"];
			break;
		}
		//else {
    //	echo $name."-".$tmp_event_summary."<br>";
		//}
	}
	if ($target_teacher_id && $teacher_id != $target_teacher_id) { return false; }


	// ■日時と時間を取得
	$start_timestamp = null;
	$end_timestamp = null;
	$diff_hours = 0;
// 20150909 変更
//	if (is_null($event['start']['dateTime']) === false) {
//		$start_timestamp = DateTime::createFromFormat(DateTime::ISO8601, $event['start']['dateTime'])->getTimestamp();
//	}
//	if (is_null($event['end']['dateTime']) === false) {
//		$end_timestamp = DateTime::createFromFormat(DateTime::ISO8601, $event['end']['dateTime'])->getTimestamp();
//	}
	$start_timestamp = $event['event_start_timestamp'];
	$end_timestamp = $event['event_end_timestamp'];
//	if (is_null($event['start']['dateTime']) === false && is_null($event['end']['dateTime']) === false) {
	if (is_null($start_timestamp) === false && is_null($end_timestamp) === false) {
		$diff_hours = ($end_timestamp - $start_timestamp) / (60*60);
	}
	// 2015/06/03 
	// お休み１の場合
	// お休み２の場合は料金が発生するため、$diff_hoursが必要
	// ファミリーの場合について
	// 全員が休みの場合は、absent_flagは1で、diff_hoursは0でいい。
	// 全員は休みでないが誰かが休みの場合は、absent_flagは0で、出席した人の料金が発生するため、$diff_hoursが必要
//	if ($student["absent_flag"] == "1") {
//		$diff_hours = 0;
//	}
//echo "差秒：".($end_timestamp - $start_timestamp);
//echo " 時間：".$diff_hours;

	// ■生徒情報を取得
	//$member_list = get_simple_member_list($db);
	$member_array = array();
/*
	$name_array = preg_split("/[\s]/", $db_name);
	if (count($name_array) > 1) {
		$sei = $name_array[0];
		$mei = $name_array[1];
	}
*/


	// 20160119 グループコースの追加
	$course_id = "";
	$type_id = "";
	//$group_course_flag = false;
	//$course_type_id = "";
	//$course_list = get_course_list($db);
//var_dump($course_list);
	foreach ($course_list as $course) {
	 	if ($course["course_id"] == "1") {
			continue;
		}
		// 2016/03/31 (仮)対応
		//if (preg_match( "/^".$course["course_name"]."/", $event_summary, $matches, PREG_OFFSET_CAPTURE)===1) {
		if (preg_match( "/^(\(仮\))?".$course["course_name"]."/", $event_summary, $matches, PREG_OFFSET_CAPTURE)===1) {
			$course_id = $course["course_id"];
			$type_id = $course["type_id"];
			//if ($course["type_id"] == "1") {
			//	$course_type_id = "1";
			//} else if ($course["type_id"] == "2") {
			//	$course_type_id = "2";
			//} else if ($course["type_id"] == "3") {
			//	$course_type_id = "3";
			//}
//echo $course["course_name"].$course_id.$type_id;
			break;
		} else if (empty($course["course_name_english"]) === false) {
//echo $course["course_name_english"]."-".ucwords($course["course_name_english"])."<br>";
		// 2016/03/31 (仮)対応
			//if (preg_match( "/^".$course["course_name_english"]."|".ucwords($course["course_name_english"])."/", $event_summary, $matches, PREG_OFFSET_CAPTURE)===1) {
			if (preg_match( "/^(\(仮\))?".$course["course_name_english"]."|".ucwords($course["course_name_english"])."/", $event_summary, $matches, PREG_OFFSET_CAPTURE)===1) {
				$course_id = $course["course_id"];
				$type_id = $course["type_id"];
				//if ($course["type_id"] == "1") {
				//	$course_type_id = "1";
				//} else if ($course["type_id"] == "2") {
				//	$course_type_id = "2";
				//} else if ($course["type_id"] == "3") {
				//	$course_type_id = "3";
				//}
				break;
			}
		}
	}
	if ($course_id == "") {
  	$course_id  = "1";
		$type_id = "1";
	}

	//$type_id = "";
	//if ($course_id < 4) {
	//	$type_id = (string)$course_id;
	//} else {
	//	$type_id = "2";
	//}


	// 2016/03/31 (仮)対応
	$tmp_event_summary = str_replace(array("(仮)"), array(""), trim($event_summary));

	if ($type_id == "3") {
		// ファミリーのとき「()」は、一つ
		//$tmp_match_num = preg_match_all( "/\((.*?)\)/", $event_summary, $tmp_matches, PREG_PATTERN_ORDER);
		$match_num = preg_match( "/\((.*?)\)/", $tmp_event_summary, $matches, PREG_OFFSET_CAPTURE);
		// 20150826返り値チェック追加
		if ($match_num === false || $match_num !== 1) {
			$errMessage = "ファミリーの場合「()」で生徒氏名をくくってください。<br>";
			$errMessage .= $event['calender_summary']."カレンダー&nbsp;".date("Y/m/d H:i", $start_timestamp)."～".date("H:i", $end_timestamp)."<br>";
			$errMessage .= $event['summary']; 
			$errArray[] = $errMessage;
			return false;
		}
//var_dump($matches);
		// １つの家族の複数の生徒で一人の生徒とする
		$student = get_family($db, $matches[1][0]);
// 20150728 生徒Noが取得できない場合の確認
// get_familyの場合、noがなければ新規登録するため$student["no"] == ""の条件は成り立たない
//if ($student["no"] == "") { var_dump($student); }
		$member_array[] = $student;
	} else if ($type_id == "2") {
	// 2015/05/22 グループとGroupの2種類がある
		$tmp_match_num = preg_match_all( "/\((.*?)\)/", $tmp_event_summary, $tmp_matches, PREG_PATTERN_ORDER);
		// 20150826返り値チェック追加
		if ($tmp_match_num === false || $tmp_match_num < 1) {
			//$errMessage = "グループの場合「()」で生徒氏名をくくってください。<br>";
			$errMessage = "「()」で生徒氏名をくくってください。<br>";
			$errMessage .= $event['calender_summary']."カレンダー&nbsp;".date("Y/m/d H:i", $start_timestamp)."～".date("H:i", $end_timestamp)."<br>";
			$errMessage .= $event['summary']; 
			$errArray[] = $errMessage;
			return false;
		}
//var_dump($tmp_matches);
		// 「(」「)」でくくられた一人ずつ処理をする
		for ($i=0; $i<$tmp_match_num; $i++) {
			$trial_flag = "0";
			if (preg_match("/[:：︰]\s*(無料体験|体験|\btrial\b)/i",$tmp_matches[1][$i] ) == 1) {
				$trial_flag = "1";
			}
			$student = get_student($db, $tmp_matches[1][$i], $trial_flag);
// 20150728 生徒Noが取得できない場合の確認
//if ($student["no"] == "") { var_dump($student); }
			$member_array[] = $student;
		}



	} else {
	// マンツーマンの場合

		$trial_flag = "0";
		if (preg_match("/[:：︰]\s*(無料体験|体験|\btrial\b)/i",$event_summary ) == 1) {
			$trial_flag = "1";
		}

//		if (preg_match("/^\*/", $event_summary, $matches, PREG_OFFSET_CAPTURE) == 1) {
//		// 20160306 先頭に＊がある場合は処理をとばす
//			return null;
//		}
//		else if ($kari_ignore === "1" && preg_match("/^\(仮\)/", $event_summary, $matches, PREG_OFFSET_CAPTURE) == 1) {
//		// 20160306 仮を無視するが選択されている場合
//		// 先頭に「(仮)」があれば無視する
//			return null;
//		}

		// 2016/03/31
		// 生徒と先生の予定を統合するため、生徒の予定のタイトルの最後に「:〇〇さん」を入れる
		// この変更により、すべての生徒の予定に「.*さん」が入る
		// 先生の予定は、get_calendar_data.phpの156行目ですでに取り除いている
		//else if (preg_match("/.*さん/", $event_summary, $matches, PREG_OFFSET_CAPTURE) == 1) {
		//// 先生の場合
		//// 2015/05/21 生徒の月謝計算のみを行うので、先生は除く
 		//	// 同じカレンダーに先生の予定も入っている。
		//	// ここではじかないと、生徒として登録されてしまう。
		//	// 生徒でないので、このあとの処理は不要。
		//	return null;
		//}
		//else if (preg_match("/.*様|Hiroka/", $event_summary, $matches, PREG_OFFSET_CAPTURE) == 1) {
		//else {
		// 生徒の場合
//echo $event_summary."\n";
			//$student = get_student($db, $event_summary, $member_list);
			$student = get_student($db, $tmp_event_summary, $trial_flag);
// 20150728 生徒Noが取得できない場合の確認
//if ($student["no"] == "") { var_dump($student); }
			$member_array[] = $student;
//		}

// 2015/06/22 チェック
//echo $event_summary."\n";
// ３．マンツーマンでNULLの時がある
//if (is_null($student) === true) {
//	var_dump($event);
//	echo "\n";
//}

	}
//var_dump($member_array);


// ４．ここでNULLの時がある(99件)
//var_dump($student);

	// updatedは、文字列としてそのまま出力する
	//$updated = DateTime::createFromFormat(DateTime::ISO8601, $event['updated']);
	//$updated_timestamp = "";
	//if ($updated != false) {
	//	$updated_timestamp = $updated->getTimestamp();
	//}
	$updated_timestamp = $event['event_updated_timestamp'];


	// ■教室IDを取得
	// lesson_nameは、「塾」、「英会話」、「ピアノ」、「習い事」を用意している。（0215/06/17 ピアノというのはない）
	// mb_strposのパラメータ順に注意。1つ目が調べたい文字列で、2つ目が1つ目の文字列から探す文字列
	$lesson_list = get_lesson_list($db);
	$lesson_id = 0;
	// 2015/05/21 英会話教室のカレンダー名が「English_home」と「English_station」のため調整する。
	// 2015/09/23 英会話教室のカレンダー名が「English_home」と「English_Keio」と「English_south」に変更になったため修正した。
	//if (($event['calender_summary'] == "English_home") || ($event['calender_summary'] == "English_station")) {
	// 20160508 カレンダー名変更に伴う修正
//	if (($event['calender_summary'] == "English_home") || ($event['calender_summary'] == "English_Keio")
//		 || (mb_strpos($event['calender_summary'], "English_south") !== FALSE) || (mb_strpos($event['calender_summary'], "English_north") !== FALSE)
//		 || (mb_strpos($event['calender_summary'], "English_dattochi") !== FALSE)) {
	if (mb_strpos($event['calender_summary'], "English") !== FALSE) {
		$lesson_id = "2";
	} else {
		foreach ($lesson_list as $id => $name) {
			if (mb_strpos($event['calender_summary'], $name) !== FALSE) {
		  //if (preg_match("\"/".$name."/u\"", $event['calender_name'], $matches, PREG_OFFSET_CAPTURE)) {
		   	$lesson_id = $id;
				break;
			}
		}
	}


	// ■科目IDを取得（タイトルから「数学」などを取得）
	$subject_id = "0";
	// 英会話教室でない場合
	if ($lesson_id != "2") {
		$subject_list = get_subject_list($db);
		// 20170512 T.Katsu
		foreach ($subject_list as &$word) {
			if (mb_strpos($word, "・") !== FALSE) {
				$tmp_item_list = explode("・", $word);
				sort($tmp_item_list);
				$word = implode("・", $tmp_item_list);
			}
		}
		unset($word);
		
		//$subject_id = 0;				// テーブルのデフォルトはnull。もし0が入っていたらcal_descriptionで入力値を確認する。
		$word_list = explode(":", $event_summary);
	  foreach ($word_list as $word) {
			//$sep_num = preg_match_all( "/・/", $event_summary, $sep_matches, PREG_PATTERN_ORDER);
			//if ($sep_num > 0) {
			if (mb_strpos($word, "・") !== FALSE) {
			// 科目が複数指定されている場合
				$tmp_item_list = array();
				$item_list = explode("・", $word);
				foreach ($item_list as $item) {
					// 科目の後ろについているかもしれないメモを取り除くため、半角スペースと(の以外の文字列を取得する
					//$match_num = preg_match( "/([^ ]+)・([^ ]+)/", $word, $tmp_matches, PREG_OFFSET_CAPTURE);
					$match_num = preg_match( "/[^ \(]+/", $item, $item_matches, PREG_OFFSET_CAPTURE);
/* 20170512 T.Katsu
					if($item_matches[0][0] == "国語") {
						$tmp_item_list[0] = $item_matches[0][0];		// tmp_item_list配列のキーは打合せで決めた記述順
					}
					if($item_matches[0][0] == "算数") {
						$tmp_item_list[1] = $item_matches[0][0];		// tmp_item_list配列のキーは打合せで決めた記述順
					}
					if($item_matches[0][0] == "数学") {
						$tmp_item_list[2] = $item_matches[0][0];		// tmp_item_list配列のキーは打合せで決めた記述順
					}
					if($item_matches[0][0] == "理科") {
						$tmp_item_list[3] = $item_matches[0][0];		// tmp_item_list配列のキーは打合せで決めた記述順
					}
					if($item_matches[0][0] == "社会") {
						$tmp_item_list[4] = $item_matches[0][0];		// tmp_item_list配列のキーは打合せで決めた記述順
					}
					if($item_matches[0][0] == "英語") {
						$tmp_item_list[5] = $item_matches[0][0];		// tmp_item_list配列のキーは打合せで決めた記述順
					}
*/
					$tmp_item_list[] = $item_matches[0][0];
				}
//				ksort($tmp_item_list);
				sort($tmp_item_list);
				$word = implode("・", $tmp_item_list);
	    }

			// 2015/11/19
			// 科目の後ろについているかもしれないメモを取り除くため、半角スペースと(の以外の文字列を取得する
			// preg_match関数はマッチングしたらそれ以降は検索しない
			// 配列[0][0]には、半角スペースと(でないにマッチングする文字列（科目名）が入っている
			$match_num = preg_match( "/[^ \(]+/", $word, $word_matches, PREG_OFFSET_CAPTURE);
			//$subject_key_array[0] = array_keys($subject_list, $name, true);
			//var_dump($word_matches);
			$result = array_search($word_matches[0][0], $subject_list);
      if ($result !== false) {
      	$subject_id = $result;
			//} else {
      //	var_dump($word);
			}
			if ($subject_id != "0") {
	    // 科目が取得できたら、ループから出る
				break;
			}
		}
	}

	if ($subject_id == "5") {
	// 科目がそろばんだったら、教室を習い事にする
		$lesson_id = "4";
	}

	// 20150725 教室が英会話だったら科目なしにする。（月謝計算時に、English_stationは科目がいらない）
	// 宮崎さんのカレンダーの予定：English_stationで科目に英語や英会話が入っている
	// 20160128 教室が英会話で科目が取得できない場合は、科目に英会話を登録する
	if ($lesson_id == "2" && $subject_id == "0") {
		$subject_id = "23";
	}

	// 20150925 教室がピアノで科目が取得できない場合は、科目にピアノを登録する
	if ($lesson_id == "3" && $subject_id == "0") {
		$subject_id = "18";
	}

// 2015/06/19 科目が未登録の場合はとりあえずエラーにする。あとで「未定」を登録するかもしれない
// 2015/11/19 塾で科目が未登録の場合は「未定」を登録する
	// 塾で「$subject_id = 0」ならば、未定とする（未定はsubject_idが8）
	if ($lesson_id == "1" && $subject_id == "0") {
		$subject_id = "8";
	}

	// 20160119現在 未使用
	// 20160423現在 未使用
	// ■場所IDを取得（場所から「本校１」などを取得）
	//$place_list = array("1"=>"本校１", "2"=>"本校２", "3"=>"本校１と２", "4"=>"駅前１", "5" => "駅前２", "6"=>"駅前１と２");
	//$place_id = 0;

	// 20160523
	// 20160423現在 未使用
	// ■場所IDを取得（カレンダー名から取得）
	$place_id = 0;
	foreach ($place_list as $place) {
		foreach ($place["calendar_name_list"] as $calendar_name) {

			if ($event["calender_summary"] == $calendar_name) {
			 	$place_id = $place["no"];
				break;
			}
			// 20160601 「習い事_南口校」の前に不明な記号が入っていたため正規表現でもう一度検索を行う
			//else if (mb_strpos($calendar_name, $event["calender_summary"]) !== FALSE) {
			else if (preg_match("/^(.+)?".$calendar_name."$/", $event["calender_summary"]) === 1) {
//echo $calendar_name."<br>";
        $place_id = $place["no"];
				break;
			}
		}
		if ($place_id != 0) {
    	break;
		}
	}

	// ■生徒ごとにパラメータを配列に格納
	foreach ($member_array as $member) {
		// 2015/05/22 statusがcancelledのものを除く → SingleEvents入れるとなくなった。
		//if ($event["status"] == "cancelled") {
//var_dump($event);
		//	break;
		//}

		// お休み1の場合は、無料体験でも面談でも普通の授業でも、ファミリーでも、時間を0にする
		// お休み1の時、時間をDBに「0時間」を登録したいので、ここで登録する
		// $diff_hoursは、$member_array配列のすべての人がグループで共用しているため、$diff_hoursを退避する
		$tmp_diff_hours = $diff_hours;
		if ($member["absent_flag"] == "1") {
			$tmp_diff_hours = 0;
		}
		// 20160331
		// お休み3の場合は、無料体験でも面談でも普通の授業でも、ファミリーでも、時間を0にする
		if ($member["absent_flag"] == "3") {
			$tmp_diff_hours = 0;
		}
		
		// 20160831
		// 振替の場合は、無料体験でも面談でも普通の授業でも、ファミリーでも、時間を0にする
		if ($member["alternative_flag"] == "1") {
			$tmp_diff_hours = 0;
		}

		// 20160331 面談の場合科目と同じように扱うように変更
		// get_studentとget_familyのところの面談処理を削除する必要がある
		if (strpos($event["event_summary"], "面談") !== FALSE) {
			$member["interview_flag"] = "3";
		}

		// 20150909 $event["event_no"]を追加
		$param_array[] = array($event["event_id"], $member["no"],
    	           					$member["id"], $member["cal_name"],$member["kind"],
													date("Y", $start_timestamp), date("n", $start_timestamp), date("j", $start_timestamp),
													$start_timestamp, date("H", $start_timestamp), date("i", $start_timestamp),
													$end_timestamp, date("H", $end_timestamp), date("i", $end_timestamp),
													$tmp_diff_hours,
													$lesson_id, $subject_id, $course_id, $teacher_id, $place_id, $member["absent_flag"], $member["trial_flag"], $member["interview_flag"],$member["alternative_flag"],
													$member["absent1_num"], $member["absent2_num"], $member["trial_num"], null,
													$event["calender_id"], $event["calender_summary"], 
													$event["event_summary"], $member["attendance_data"], $event["event_location"], $event["event_description"], $updated_timestamp, $year, $month,
													$event["recurringEvent"], $member["grade"], $monthly_fee_flag);

	}
//var_dump($param_array);
	return $param_array;
}

//function get_student(&$db, $student_data, $member_list) {
function get_student(&$db, $student_data, $trial_flag) {
	global $member_list, $grade_list;
	$student_array = array();
	$absent_flag = 0;
	$interview_flag = 0;
	$alternative_flag = 0;
	$member_no = "";
	$cal_name = "";
	$tmp_cal_name = "";
	$tmp_db_name = "";
	$words = explode(":", $student_data);//「(」「)」を外したもの
	$grade='';
	foreach ($words as $key => $word) {
		$word = trim($word);
		
		foreach($grade_list as $key1=>$str) { if (strpos($word,$str)!==false) { $grade=$key1; } }

//echo $word."\n";
		if ($word=="休み2" || $word=="Absent2" || $word=="absent2") {
			$absent_flag = "2";
		}
   	else if ($word=="休み1" || $word=="Absent1" || $word=="absent1") {
		// 生徒都合の休み。授業料が発生しない
			$absent_flag = "1";
   	}
		// 2016/03/31 講師都合の休みを追加
   	else if ($word=="休み3" || $word=="Absent3" || $word=="absent3") {
		// 先生都合の休み。授業料が発生しない
			$absent_flag = "3";
   	}
// 20150807 休みの番号なしをエラーにする
//		else if (strpos($word, "休み") !== FALSE || strpos($word, "Absent") !== FALSE || strpos($word, "absent") !== FALSE) {
		// 授業料が発生しない休み
//			$absent_flag = 1;
//   	}
//   	else if (mb_strpos($word, "無料体験") !== FALSE || mb_strpos($word, "体験") !== FALSE || strpos($word, "trial") !== FALSE || strpos($word, "Trial") !== FALSE) {
//			$trial_flag = "1";
//		}
// 20150807 三者面談の番号なしをエラーにする
   	else if (strpos($word, "三者面談1") !== FALSE) {
		// 20150726 入塾後、無料の三者面談
			$interview_flag = "1";
		}
   	else if (strpos($word, "三者面談2") !== FALSE) {
		// 20150726 入塾後、有料の三者面談
			$interview_flag = "2";
		}
   	else if (strpos($word, "面談") !== FALSE) {
		// 20150807 入塾前、無料の三者面談
			$interview_flag = "3";
		}
   	else if (($key == 0) && (strpos($word, "振替") !== FALSE || stripos($word, "alternative") !== FALSE || stripos($word, "make-up") !== FALSE || stripos($word, "make up") !== FALSE || stripos($word, "makeup") !== FALSE)) {
		// 20151026 振替授業
			$alternative_flag = "1";
		}
		// 2016/03/31 休み3の対応
		//else if (($key == 0 && $absent_flag == 0) || ($key == 1 && $absent_flag == 1) || ($key == 1 && $absent_flag == 2)) {
		else if (($key == 0 && $absent_flag == 0) || ($key == 1 && $absent_flag == 1) || ($key == 1 && $absent_flag == 2) || 
					($key == 1 && $absent_flag == 3) || ($key == 1 && $alternative_flag == 1)) {
			// 名前の部分。名前の前には「休み」しかない
			//if (strpos($word, "様") !== FALSE) {
			if (preg_match("/(.*)様|(.*)/", $word, $matches, PREG_OFFSET_CAPTURE) == 1) {
				// 1つ目の様のあとは、何が入力してあったとしても名前として扱わない。
				if ($matches[1][0] != "") {
				// 様がある場合
					$cal_name = $matches[1][0];
				} else {
				// 様がない場合
					$cal_name = $matches[2][0];
				}
				$tmp_cal_name = str_replace(array(" "), array(""), $cal_name);// 半角スペースも除去
			}

			if ( $trial_flag != "0" ) { $tmp_cal_name = "体験生徒"; }

			foreach ($member_list as $no => $member) {

		    $tmp_db_name = str_replace(array("　", " "), array(" ", ""), $member['name']);// 半角スペースも除去

				// 名字が同じだとうまく条件分岐できない
				//if (strpos($cal_name, $db_name) !== FALSE) {
				if (preg_match("/^".preg_quote($tmp_db_name,"/")."$/", $tmp_cal_name, $name_matches, PREG_OFFSET_CAPTURE) == 1) {
// 2015/05/29 データチェック（名前を抽出時）※データ取り込み2回目以降
//echo $tmp_cal_name."\n";
					$member_no = $member["no"];
					break;
				}
			} // End:foreach ($member_list as $id => $member)
		}
	}	// End:foreach ($words as $key => $word)
	

	// absent1_num、absent2_num、trial_numは、ファミリーの時のみ使用するため、「0」を入れておく
	$student_array = array("id"=>"", "no"=>$member_no, "kind"=>"student", "absent_flag"=>$absent_flag, "trial_flag"=>$trial_flag, 
													"interview_flag"=>$interview_flag, "alternative_flag"=>$alternative_flag,
													"cal_name"=>$cal_name, "absent1_num"=>0, "absent2_num"=>0, "trial_num"=>0, "attendance_data"=>$student_data,
													"grade"=>$grade);

	return $student_array;
}

// 2015/06/15 ファミリー対応
//function get_family(&$db, $family_data, $member_list) {
function get_family(&$db, $family_data) {
	global $member_list, $grade_list;
	$family_array = array();
	$absent1_num = 0;
	$absent2_num = 0;
	$trial_num = 0;
	$absent_flag = "0";	// 全員が休みの場合
	$trial_flag = "0";	// 全員が無料体験の場合
	$interview_flag = "0";	// 全員が面談か三者面談の場合
	$cal_name = "";			// スペースが入っている明細書の宛先用
	$tmp_cal_name = "";	// スペースが入っていない比較用
	$tmp_db_name = "";	// スペースが入っていない比較用

	// 前後の半角スペースを削除しておく
	$family_data = trim($family_data);

	// 全員が無料体験の場合　()の中に「:無料体験」が入る
	if (preg_match("/(無料体験|体験|trial|Trial)/", $family_data, $matches, PREG_OFFSET_CAPTURE) == 1) {
		$trial_flag = "1";
	}
	// 全員は無料体験でないが誰かは無料体験の場合があるかどうか不明。確認が必要。
	//if ($trial_flag != "1") {
	//	$trial_num = preg_match_all( "/無料体験|体験|trial|Trial/", $family_data, $matches, PREG_PATTERN_ORDER);
	//}

	// 20150726 全員が三者面談の場合　()の中に「:三者面談」が入る
	if (preg_match("/三者面談1|面談1/", $family_data, $matches, PREG_OFFSET_CAPTURE) == 1) {
		$interview_flag = "1";
	} else if (preg_match("/三者面談2|面談2|三者面談/", $family_data, $matches, PREG_OFFSET_CAPTURE) == 1) {
		$interview_flag = "2";
	} else if (preg_match("/面談/", $family_data, $matches, PREG_OFFSET_CAPTURE) == 1) {
	// 2016/03/31 無料の面談
		$interview_flag = "3";
	}


	// 20151026 全員が振替の場合　()の中に「:振替」が入る
	if (preg_match("/振替|alternative|Alternative/", $family_data, $matches, PREG_OFFSET_CAPTURE) == 1) {
		$alternative_flag = "1";
	}    //20171129 T.Katsu コメントになっていたのを有効化。何故コメントにしたのか？


	//if (preg_match("/(.*)様|(.*)/", $family_data, $matches, PREG_OFFSET_CAPTURE) == 1) {
		// 1つ目の様のあとは、何が入力してあったとしても名前として扱わない。
		//if ($matches[1][0] != "") {
		// 様がある場合
		//	$cal_name = $matches[1][0];
		//} else {
		// 様がない場合
		//	$cal_name = $matches[2][0];
		//}

		//$search_array = array("休み2","Absent2","absent2","休み1","Absent1","absent1","休み","Absent","absent","無料体験","体験","trial","Trial",":","様");
		$search_array = array("休み3","Absent3","absent3","休み2","Absent2","absent2","休み1","Absent1",
				"absent1","休み","Absent","absent",":","振替","alternative","Alternative","make-up","Today","No_class","No class",
				"当日");
		$replace_array = array("", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "");
		$tmp1_cal_name = str_replace($search_array, $replace_array, $family_data);
		$search_array = array("無料体験","体験","trial","Trial","三者面談1","面談1","三者面談2","面談2","三者面談","面談","休講");
		$replace_array = array("", "", "", "", "", "", "", "", "", "", "");
		$tmp2_cal_name = str_replace($search_array, $replace_array, $tmp1_cal_name);
		$tmp_cal_name = str_replace(array(" "), array(""), $tmp2_cal_name);// 比較のため半角スペースも除去
	//}

		if ( $trial_flag != "0" ) { $cal_name = "体験生徒"; $tmp_cal_name = $cal_name; }

		$member_no = "";
		foreach ($member_list as $no => $member) {

	    $tmp_db_name = str_replace(array("　", " "), array(" ", ""), $member['name']);// 比較のため半角スペースも除去
			// 名字が同じだとうまく条件分岐できない
			//if (strpos($cal_name, $db_name) !== FALSE) {
			if (preg_match("/^".preg_quote($tmp_db_name,"/")."様/u", $tmp_cal_name, $name_matches, PREG_OFFSET_CAPTURE) == 1) {
// 2015/05/29 データチェック（名前を抽出時）※データ取り込み2回目以降
//echo $tmp_cal_name."\n";
				$member_no = $member["no"];
				break;
			}
		} // End:foreach ($member_list as $id => $member)
		// 2015/05/19 生徒情報を初期登録するため、一時的に登録する
		// 「様」は入力されているのに氏名は登録されていない場合、生徒を新規登録する
		//if ($member_no == "" && strpos($tmp_matches[0][$i], "様") !== FALSE) {
		// カレンダーEnglish_stationとEnglish_homeには「様」が付かない
		if ($member_no == "") {
		// 20160508
		// 20160424 様があるかまたはアルファベットがある場合に登録する
		if (strpos($family_data, "様") !== FALSE || preg_match("/[A-Za-z]+/", $family_data, $matches, PREG_OFFSET_CAPTURE) == 1) {
				// add_studentは、登録に成功したらmember_noを、登録に失敗したらfalseを返す
//				$member_no = add_student($db, $tmp_cal_name);
//				if ($member_no === false) {
//					$member_no = "";
//				} else {
//					// 2016/03/31 登録に成功した場合
//					// 追加登録した生徒を重複して登録しないために、member_listを再取得する必要がある
//					$member_list = get_simple_member_list($db);
//				}
		}
		}
		//}
//	}	// End:foreach ($words as $key => $word)

	// ファミリーの場合は、休みが特別。
	// 全員が休みの場合
	// 講師都合の休みの場合は、全員が休みになる
	if (preg_match("/^(休み3|Absent3|absent3)/", $family_data, $matches, PREG_OFFSET_CAPTURE) == 1) {
		$absent_flag = "3";
	}
	// 20150728
	//if (preg_match("/^(休み2:|Absent2:|absent2:)/", $family_data, $matches, PREG_OFFSET_CAPTURE) == 1) {
	if (preg_match("/^(休み2|Absent2|absent2)/", $family_data, $matches, PREG_OFFSET_CAPTURE) == 1) {
		$absent_flag = "2";
	}
	// if (preg_match("/^(休み:|Absent:|absent:|休み1:|Absent1:|absent1:)/", $family_data, $matches, PREG_OFFSET_CAPTURE) == 1) {
	if (preg_match("/^(休み1|Absent1|absent1)/", $family_data, $matches, PREG_OFFSET_CAPTURE) == 1) {
		$absent_flag = "1";
	}
	// 2016/03/31 数字がないときはエラーにする
	//休み1、休み2のとき「休み」と「休み1」または「休み2」があってしまうため、あとからチェックする
	//if ($absent_flag == "0") {
	//	if (preg_match("/^(休み|Absent|absent)/", $family_data, $matches, PREG_OFFSET_CAPTURE) == 1) {
	//		$absent_flag = "1";
	//	}
	//}

  // 全員はお休みでないが誰かはお休みの場合
	// 注意！無料体験でもお休みはある
	if ($absent_flag != "1" && $absent_flag != "2" && $absent_flag != "3") {
		// 授業料が発生する休みの人数を取得する
		// 20150728
		//$absent2_num = preg_match_all( "/休み2:|Absent2:|absent2:/", $family_data, $matches, PREG_PATTERN_ORDER);
		$absent2_num = preg_match_all( "/休み2|Absent2|absent2/", $family_data, $matches, PREG_PATTERN_ORDER);
		// 授業料が発生しない休みの人数を取得する
		// 6月以降は「休み1」「Absent1」「absent1」
   	//if (strpos($word, "休み1") !== FALSE || strpos($word, "Absent1") !== FALSE || strpos($word, "absent1") !== FALSE) {
		//else if (strpos($word, "休み") !== FALSE || strpos($word, "Absent") !== FALSE || strpos($word, "absent") !== FALSE) {
		// elseにしないと、休み2の「休み」部分がヒットしてしまう
		//$absent1_num = preg_match_all( "/休み1|Absent1|absent1|休み:|Absent:|absent:/", $family_data, $matches, PREG_PATTERN_ORDER);
		$tmp1_absent1_num = preg_match_all( "/休み1|Absent1|absent1/", $family_data, $matches, PREG_PATTERN_ORDER);
		// 休みのとき
		// $tmp2_absent1_num = preg_match_all( "/休み!1|Absent!1|absent!1|休み!2|Absent!2|absent!2/", $family_data, $matches, PREG_PATTERN_ORDER);
		//$tmp2_absent1_num = preg_match_all( "/休み[^1]*|Absent[^1|^2]*|absent[^1|^2]*/", $family_data, $matches, PREG_PATTERN_ORDER);
		$tmp2_absent1_num = preg_match_all( "/休み[^1-2]|Absent[^1-2]|absent[^1-2]/", $family_data, $matches, PREG_PATTERN_ORDER);
		$absent1_num = $tmp1_absent1_num + $tmp2_absent1_num;
		
		if ($member_no) {
			$member_count = count(explode(' ',$member_list[$member_no]['name']))-1;
			if ($member_count==$absent1_num) { $absent_flag=1; }
			if ($member_count==$absent2_num) { $absent_flag=2; }
		}
	}

//	$student_array = array("id"=>"", "no"=>$member_no, "kind"=>"student", "absent_flag"=>$absent_flag, "trial_flag"=>$trial_flag, 
//													"cal_name"=>$cal_name, "absent1_num"=>$absent1_num, "absent2_num"=>$absent2_num, "trial_num"=>$trial_num,);
//	$student_array = array("id"=>"", "no"=>$member_no, "kind"=>"student","absent_flag"=>$absent_flag, "trial_flag"=>$trial_flag, "interview_flag"=>$interview_flag,
//													"cal_name"=>$cal_name, "absent1_num"=>$absent1_num, "absent2_num"=>$absent2_num, "trial_num"=>$trial_num);

	$grdade = '';
	foreach($grade_list as $key1=>$str) { if (strpos($family_data,$str)!==false) { $grade=$key1; } }

	$student_array = array("id"=>"", "no"=>$member_no, "kind"=>"student", "absent_flag"=>$absent_flag, "trial_flag"=>$trial_flag,
													"interview_flag"=>$interview_flag,  "alternative_flag"=>$alternative_flag,
													"cal_name"=>$cal_name, "absent1_num"=>$absent1_num, "absent2_num"=>$absent2_num, "trial_num"=>$trial_num, "attendance_data"=>$family_data,
													"grade"=>$grade);

	return $student_array;
}



function insert_event(&$db, $event_param_array) {
	//  schedule_date_format削除
		try{
// 20150909 event_noを追加
// 20160423 teacher_idを追加
				$sql = "INSERT INTO tbl_event (".
					" event_id, member_no, ".
					" member_id, member_cal_name, member_kind, ".
					" event_year, event_month, event_day, ".
					" event_start_timestamp, event_start_hour, event_start_minute, ".
					" event_end_timestamp, event_end_hour, event_end_minute, ".
					" event_diff_hours, ".
					" lesson_id, subject_id, course_id, teacher_id, place_id, absent_flag, trial_flag, interview_flag, alternative_flag,".
					" absent1_num, absent2_num, trial_num, repeat_flag, ".
					" cal_id, cal_summary, cal_evt_summary, cal_attendance_data, cal_evt_location, cal_evt_description, cal_evt_updated_timestamp, seikyu_year, seikyu_month,".
					" recurringEvent, grade, monthly_fee_flag, ".
					" insert_datetime ".
					" ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,  ?, ?, ?, ?, ?, ?, ?, ?,  ?, ?, ?, ?, ?, now())";
				$stmt = $db->prepare($sql);
				$stmt->execute($event_param_array);
//var_dump($event_param_array);
				$event_no = $db->lastInsertId();
		}catch (PDOException $e){
			print_r('insert_event:failed: ' . $e->getMessage());
			//if ($stmt->errorCode()) {
				//$stmt->errorInfo();
				return false;
			//}
		}
		return $event_no;
} // End:event_insert($db, $event)



// 20150909 月単位に削除するように修正
function delete_event(&$db, $year, $month) {
			//  schedule_date_format削除
try{
			// 20160810堀内修正　請求年月ごとに削除
			//$sql = "DELETE FROM tbl_event where event_year = ? and event_month = ?";
			$sql = "DELETE FROM tbl_event where seikyu_year = ? and seikyu_month = ?";
			$stmt = $db->prepare($sql);
			//$stmt->execute();
			$stmt->execute(array($year, $month));
	}catch (PDOException $e){
		$err_msg = 'Delete failed: ' . $e->getMessage();
//echo $err_msg;
			//if ($stmt->errorCode()) {
				//$stmt->errorInfo();
				return false;
			//}
	}
			return true;
} // End:event_insert($db, $event)

function add_student(&$db, $cal_name) {
		// 20160525変更 入力チェックをするためtax_flagとmembership_feeの初期値をnullに変更
		$student = array();
		// 20150811 student_fee_add.php L20からにあわせる
		$student["name"] = $cal_name;
		$student["furigana"] = "";
		//$student["sei"] = $cal_name;
		//$student["mei"] = "";
		$student["grade"] = "";
		$student["membership_fee"] = null;
		$student["sheet_id"] = null;
		$student["cid"] = null;
		$student["del_flag"] = 0;	// 基本は、現在入会している生徒
		$student["tax_flag"] = null;
		$student_no = insert_student($db, $student);
		return $student_no;
}


// 20150909
// カレンダーから取得したデータを取得
// $param_array : array("event_year = ?")
// $value_array : array("2015")
// $order_array : array("event_start_datetime")

// 20160810堀内修正　引数に$yearと$monthを追加
//function get_calender_event_list($db) {
function get_calender_event_list($db, $year, $month) {

	//tbl_event.event_date_format as date_format,
	// 20150727	「tbl_event.member_id as member_id,」を「tbl_event.member_no as member_no,」に修正
	//$cmd = "SELECT * FROM tbl_calender_event	order by tbl_calender_event.event_no";

	// 20160810堀内修正　請求年月ごとにカレンダーデータを取得
	//$cmd = "SELECT * FROM tbl_calender_event";
	$cmd = "SELECT * FROM tbl_calender_event where seikyu_year = ? and seikyu_month = ?";

	$stmt = $db->prepare($cmd);
//echo $cmd;
//var_dump($value_array);
//	$stmt->execute($value_array);

	// 20160810堀内修正　請求年月ごとにカレンダーデータを取得
	//$stmt->execute();
	$stmt->execute(array($year, $month));

	$calender_event_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
	if ($stmt->errorCode()) {
		$stmt->errorInfo();
	}
	return $calender_event_list;
}


?>