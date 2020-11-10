<?php
	include_once 'simple_html_dom.php';
	function fetch_standing() {
		return shell_exec(trim(file_get_contents("curl.txt"))." -s");
		//return file_get_contents("contest_standing.htm");
	}
	function parse_standing($page) {
		$html = str_get_html($page);
		$tables = $html->find('tbody');
		$board = str_get_html($tables[0]->innertext);
		$board = $board->find('tr');
		$result = array();
		foreach ($board as $eachteam) {
			$teamstatus = str_get_html($eachteam->innertext);
			$teamstatus = $teamstatus->find('td');
			$tmp = array();
			foreach ($teamstatus as $each) array_push($tmp,$each);
			$info = array(
				'team_nick'=>$tmp[1]->plaintext,
				'team_id'=>$tmp[2]->plaintext,
				'problem_status'=>array()
			);
			for ($i=4;$i<count($tmp)-3;$i++) {
				if ($tmp[$i]->class=='acfb_stat'||$tmp[$i]->class=='ac_stat') {
					$pos = strpos($tmp[$i]->plaintext,'(');
					$t = explode(':',substr($tmp[$i]->plaintext,0,$pos));
					$info['problem_status'][chr(65+$i-4)] = intval($t[0]) * 60 * 60 + intval($t[1]) * 60 + intval($t[2]);
				}
				else $info['problem_status'][chr(65+$i-4)] = false;
			}
			array_push($result,$info);
		}
		return $result;
	}
	$vis = array();
	$problem_vis = array();
	$t_start = 0;
	while (true) {
		$standing_page = fetch_standing();
		if (strpos($standing_page,"cstandingcontainer") === false) {
			echo "fetch error!\n";
		}
		else {
			$result = parse_standing(fetch_standing());
			foreach ($result as $eachteam) {
				foreach ($eachteam['problem_status'] as $problem => $ac) if ($ac) {
					if (!array_key_exists($eachteam['team_id'].$problem,$vis) && $ac/60 >= $t_start) {
						if (!array_key_exists($problem,$problem_vis)) echo sprintf("first blood\n");
						$problem_vis[$problem] = true;
						echo sprintf("%03d %s\t%s\t%s\n",$ac/60,$eachteam['team_id'],$problem,$eachteam['team_nick']);
						$buf = fgets(STDIN);
					}
					$vis[$eachteam['team_id'].$problem] = true;
				}
			}
		}
		sleep(10);
	}
?>