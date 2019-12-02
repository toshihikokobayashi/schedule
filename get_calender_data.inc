<?php

// 20160810堀内修正
// イベントテーブルのデータをすべて削除から対象年月のみ削除へ変更
if ($result) {
	//$result = delete_calender_event($db);
	$result = delete_calender_event($db, $year, $month);
	// 履歴テーブルにある対象年月のデータを削除
	//$result = delete_history($db, $year, $month);
}

//$cal_calArray = array();
$calender_auth = new GoogleCalenderAuth();
$service = $calender_auth->getCalenderService();

mb_regex_encoding("UTF-8");

if ($result) {

//log-event
file_put_contents('./log-event', date("----------- Y/m/d H:i:s \n"), FILE_APPEND);

// Google カレンダーからデータを取得
$calList = $service->calendarList->listCalendarList();
$err_flag = false;
//$err_event_array = array();
$errArray = array();
//$db->beginTransaction();
while(true) {
	if ($err_flag == true) { break; }
  foreach ($calList->getItems() as $calender) {
	if ($err_flag == true) { break; }

		// 20151018 事務と教材開発のカレンダーは除く
		//if (($calender['summary'] == "事務_駅前") || ($calender['summary'] == "教材開発_駅前") || ($calender['summary'] == "事務_本校")) {
		//if ((mb_strpos($calender['summary'], "事務_北口") !== FALSE) || (mb_strpos($calender['summary'], "事務_南口") !== FALSE) || ($calender['summary'] == "事務_本校")
		//		 || ($calender['summary'] == "教材開発_駅前")) {
//		if ((mb_strpos($calender['summary'], "事務") !== FALSE) || (mb_strpos($calender['summary'], "教材開発") !== FALSE)) {
		if ((mb_strpos($calender['summary'], "教材開発") !== FALSE)) {
			continue;
		}
		
		// 20160508 2016年04月までは下の４つのカレンダーを除く
		if (mktime(0, 0, 0, 5, 1, 2016) > mktime(0, 0, 0, $month, 1, $year)) {
			if ((mb_strpos($calender['summary'], "塾_北口校 3F") !== FALSE) || (mb_strpos($calender['summary'], "塾_北口校 4F") !== FALSE)
					|| (mb_strpos($calender['summary'], "English_north 3F") !== FALSE) || (mb_strpos($calender['summary'], "English_north 4F") !== FALSE)) {
				continue;
			}
		}

		// 20151230 冬期講習対応
		$objMinDateTime = new DateTime();
		$objMaxDateTime = new DateTime();

		// 20160103 夏期講習・冬期講習・春期講習を元に戻す
		// 20160103_2 夏期講習・冬期講習・春期講習を対応する
//		if ($month == "8" || $month == "1" || $month == "4") {
//			$lastYear = date("Y", mktime(0, 0, 0, $month-1, 1, $year));
//			$lastMonth = date("n", mktime(0, 0, 0, $month-1, 1, $year));
//			$objMinDateTime->setTimeStamp(mktime(0, 0, 0, $lastMonth, 18, $lastYear));
//			$objMaxDateTime->setTimeStamp(mktime(23, 59, 59, $month+1, 0, $year));
//		} else {
			$objMinDateTime->setTimeStamp(mktime(0, 0, 0, $month, 1, $year));
			$objMaxDateTime->setTimeStamp(mktime(23, 59, 59, $month+1, 0, $year));
//		}

		$minDateTime = $objMinDateTime->format(DateTime::ISO8601);
		$maxDateTime = $objMaxDateTime->format(DateTime::ISO8601);

		// maxResultsのデフォルト値は250件
		//$optParams = array('maxResults' => 250, 'timeMin' => $minDateTime, 'timeMax' => $maxDateTime);
		$optParams = array('maxResults' => 500, 'timeMin' => $minDateTime, 'timeMax' => $maxDateTime, 'orderBy'=>'startTime', 'singleEvents'=>'True');
		$events = $service->events->listEvents($calender['id'], $optParams);
//		$events = $service->events->listEvents($calender['id']);
		while(true) {
			if ($err_flag == true) { break; }

			// イベントごと（カレンダーに入力された予定ごと）に処理をする
		  foreach ($events->getItems() as $event) {

			// 20160522
			// 仮とnot definedができたため変更
			// 先生と生徒の予定が統合されたためスキップする予定がなくなりました

			// 20160331
			// 生徒と先生の予定を統合するため、生徒の予定のタイトルの最後に「:〇〇さん」を入れる
			// これまでの先生の予定を含めないようにするため、処理をとばす
			// 20160916 追加
			// 2016年3月までは生徒と先生の予定が別々だったため、（2016年3月の春期講習を含む）2016年4月以前を計算する場合は、
			// 先生の予定を含めないようにするため、処理をとばす
			if ($year < 2016 || ($year == 2016 && $month <= 4)) {
				$tmp_event_summary = str_replace(array("　", "（", "）", "：", "︰", "＊"), array(" ", "(", ")", ":", ":", "*"), trim($event['summary']));
				//$tmp_event_summary = str_replace(array("："), array(":"), trim($event['summary']));
				if (preg_match("/^(\(仮\))?(休み[^:]*:|Absent[^:]*:|absent[^:]*:)?[^:]*さん/", $tmp_event_summary, $matches, PREG_OFFSET_CAPTURE) === 1) {
					//var_dump($tmp_event_summary);
	      	continue;
				}
			}

			
			// 20151230 冬期講習対応
			$start_timestamp = null;
			$end_timestamp = null;
			if (is_null($event['start']['dateTime']) === false) {
				$start_timestamp = DateTime::createFromFormat(DateTime::ISO8601, $event['start']['dateTime'])->getTimestamp();
			}
			if (is_null($event['end']['dateTime']) === false) {
				$end_timestamp = DateTime::createFromFormat(DateTime::ISO8601, $event['end']['dateTime'])->getTimestamp();
			}
			
/* 2017年以前の期間講習処理			
			// 20180122 期間講習は除く
			if (mb_strpos($event["summary"], "夏期講習") !== FALSE) {
				continue;
			}
			if (mb_strpos($event["summary"], "冬期講習") !== FALSE) {
				continue;
			}
			if (mb_strpos($event["summary"], "春期講習") !== FALSE) {
				continue;
			}


		// 20160103_2 夏期講習・冬期講習・春期講習を対応する
			// 8月に取り込んだ7月分のデータに「夏期講習」が含まれていなければ、データベースに登録しない
			if ($month == "8" && date("n", $start_timestamp) == "7" && mb_strpos($event["summary"], "夏期講習") === FALSE) {
        	continue;
			// 1月に取り込んだ12月分のデータに「冬期講習」が含まれていなければ、データベースに登録しない
			} else if ($month == "1" && date("n", $start_timestamp) == "12" && mb_strpos($event["summary"], "冬期講習") === FALSE) {
        	continue;
			// 4月に取り込んだ3月分のデータに「春期講習」が含まれていなければ、データベースに登録しない
			} else if ($month == "4" && date("n", $start_timestamp) == "3" && mb_strpos($event["summary"], "春期講習") === FALSE) {
        	continue;
			// 8月でない月に「夏期講習」が含まれていれば、データベースに登録しない
			} else if ($month != "8" && mb_strpos($event["summary"], "夏期講習") !== FALSE) {
        	continue;
			// 1月でない月に「冬期講習」が含まれていれば、データベースに登録しない
			} else if ($month != "1" && mb_strpos($event["summary"], "冬期講習") !== FALSE) {
        	continue;
			// 4月でない月に「春期講習」が含まれていれば、データベースに登録しない
			} else if ($month != "4" && mb_strpos($event["summary"], "春期講習") !== FALSE) {
        	continue;
			}
*/
//log-event
file_put_contents('./log-event', $calender['summary'].date(" Y/m/d H:i  ", $start_timestamp).$event['summary']."\n", FILE_APPEND);

			// 20160810堀内修正　引数に$yearと$monthを追加
			$result = add_calender_event($db, $calender, $event, $start_timestamp, $end_timestamp, $year, $month);
			if ($result === false) {
				$err_flag = true;
      	break;
			}

				if ($err_flag == true) { break; } // break:foreach ($events->getItems() as $event)
		  }
			if ($err_flag == true) { break; } // break:while(true)
		  $pageToken = $events->getNextPageToken();
		  if ($pageToken) {
//echo "pagetoken:".$pageToken."<br>";
		    //$optParams = array('pageToken' => $pageToken);
				// maxResultsのデフォルト値は250件
				//$optParams = array('maxResults' => 250, 'timeMin' => $minDateTime, 'timeMax' => $maxDateTime, 'pageToken' => $pageToken);
				$optParams = array('maxResults' => 500, 'timeMin' => $minDateTime, 'timeMax' => $maxDateTime, 'pageToken' => $pageToken, 'orderBy'=>'startTime', 'singleEvents'=>'True');
		    $events = $service->events->listEvents($calender['id'], $optParams);
		  } else {
		    break; // break:while(true)
		  }
		}
		if ($err_flag == true) { break; } // break:foreach ($calList->getItems() as $calender)
  }
	if ($err_flag == true) { break; } // break:while(true)
  $pageToken = $calList->getNextPageToken();
  if ($pageToken) {
    $optParams = array('pageToken' => $pageToken);
    $calList = $service->calendarList->listCalendarList($optParams);
  } else {
    break;
  }
}// End:while(true)

}// End:if ($result)

// ****** メイン処理ここまで ******


class GoogleCalenderAuth {

	private static $client;
	private static $service;

	public static function getCalenderService() {
		if (! isset(self::$client)) {
			self::createClient();
		}
		
		if (! isset(self::$service)) {
			self::$service = new Google_Service_Calendar(self::$client);
		}
		return self::$service;
	}

	private static function createClient() {
		self::$client = new Google_Client();
		//self::$client->setApplicationName('Application Name');
		self::$client->setClientId(CLIENT_ID);

		$credential = new Google_Auth_AssertionCredentials(
												SERVICE_ACCOUNT_NAME,
												array('https://www.googleapis.com/auth/calendar.readonly'),
												file_get_contents(KEY_FILE)
											);
		self::$client->setAssertionCredentials($credential);
	}
}



// 20160810堀内修正 引数に$yearと$monthを追加
function delete_calender_event(&$db, $year, $month) {
try{
		// 20160810堀内修正　月ごとにカレンダーデータの最終バージョンを残すため請求年月ごとに削除
		//$sql = "DELETE FROM tbl_calender_event";
		$sql = "DELETE FROM tbl_calender_event where seikyu_year = ? and seikyu_month = ?";
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


// 20160810堀内修正　引数に$yearと$monthを追加
function add_calender_event(&$db, $calender, $event, $start_timestamp, $end_timestamp, $year, $month) {
	$event_no = insert_calender_event($db, $calender, $event, $start_timestamp, $end_timestamp, $year, $month);
	if ($event_no === false) {
		return false;
	}
  return true;
}

// 20160810堀内修正　引数に$yearと$monthを追加
function insert_calender_event(&$db, $calender, $event, $event_start_timestamp, $event_end_timestamp, $year, $month) {

		try{
				// 20160810堀内修正　seikyu_year列とseikyu_month列を追加
				$sql = "INSERT INTO tbl_calender_event (".
					" event_id, event_start_timestamp, event_end_timestamp, ".
					" calender_id, calender_summary, event_summary, event_location, event_description, event_updated_timestamp, seikyu_year, seikyu_month, recurringEvent,".
					" insert_datetime ".
					" ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now())";
				$stmt = $db->prepare($sql);
				$stmt->bindParam(1, $event_id);
				$stmt->bindParam(2, $tmp_event_start_timestamp);
				$stmt->bindParam(3, $tmp_event_end_timestamp);
				$stmt->bindParam(4, $calender_id);
				$stmt->bindParam(5, $calender_summary);
				$stmt->bindParam(6, $event_summary);
				$stmt->bindParam(7, $event_location);
				$stmt->bindParam(8, $event_description);
				$stmt->bindParam(9, $event_updated_timestamp);
				$stmt->bindParam(10, $seikyu_year);
				$stmt->bindParam(11, $seikyu_month);
				$stmt->bindParam(12, $recurringEvent);
				$event_id = $event["id"];
				$tmp_event_start_timestamp = $event_start_timestamp;
				$tmp_event_end_timestamp = $event_end_timestamp;
				$calender_id = trim($calender['id']);
				$calender_summary = trim($calender['summary']);
				$event_summary = trim($event['summary']);
				$event_location = trim($event['location']);;
				$event_description = trim($event['description']);;
				$event_updated_timestamp = trim($event['updated']);
				$seikyu_year = $year;
				$seikyu_month = $month;
				$recurringEvent = is_null($event['recurringEventId'])?'0':'1';
				$stmt->execute();
				$event_no = $db->lastInsertId();
		}catch (PDOException $e){
			print_r('insert_calender_event:failed: ' . $e->getMessage());
			//if ($stmt->errorCode()) {
				//$stmt->errorInfo();
				return false;
			//}
		}
		return $event_no;
} // End:event_insert($db, $event)

?>