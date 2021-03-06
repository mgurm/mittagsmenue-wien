<?php

require_once(__DIR__ . '/VoteHandler.php');
require_once(__DIR__ . '/MysqlConnection.php');

class VoteHandler_MySql extends VoteHandler {

	private static $instance;
	protected $db = null;

	function __construct($timestamp=null) {
		$this->db = MysqlConnection::getInstance()->getConnection();
	}

	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function db_ok() {
		return ($this->db !== null);
	}

	public function save($day, $ip, $category, $vote) {
		if (!$this->db_ok()) {
			return;
		}

		// prepare statement
		if (!($stmt = $this->db->prepare("INSERT INTO foodVote VALUES (?, ?, ?, ?)"))) {
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		}
		// bind params
		if (!$stmt->bind_param("ssss", $day, $ip, $category, $vote)) {
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// execute
		if (!$stmt->execute()) {
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		$stmt->free_result();
	}

	public function update($day, $ip, $category, $vote) {
		if (!$this->db_ok()) {
			return;
		}

		// prepare statement
		if (!($stmt = $this->db->prepare("UPDATE foodVote SET vote=? WHERE day=? AND ip=? AND category=?"))) {
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		}
		// bind params
		if (!$stmt->bind_param("ssss", $vote, $day, $ip, $category)) {
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// execute
		if (!$stmt->execute()) {
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		$stmt->free_result();
	}

	public function get($day, $ip=null, $category=null) {
		if (!$this->db_ok()) {
			return;
		}
		$return = [];

		// prepare statement
		if ($day && $ip && $category && !($stmt = $this->db->prepare("SELECT user.name, user.ip, category, vote FROM foodVote vote JOIN foodUser user ON vote.ip=user.ip WHERE day=? AND user.ip=? AND vote.category=? ORDER BY name"))) {
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		} else if ($ip && !$category && !($stmt = $this->db->prepare("SELECT user.name, user.ip, category, vote FROM foodVote vote JOIN foodUser user ON vote.ip=user.ip WHERE day=? AND user.ip=? ORDER BY name"))) {
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		} else if (!$ip && !$category && !($stmt = $this->db->prepare("SELECT user.name, user.ip, category, vote FROM foodVote vote JOIN foodUser user ON vote.ip=user.ip WHERE day=? ORDER BY name"))) {
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		}

		// bind params
		if ($day && $ip && $category && !$stmt->bind_param("sss", $day, $ip, $category)) {
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		} else if ($day && $ip && !$category && !$stmt->bind_param("ss", $day, $ip)) {
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		} else if ($day && !$ip && !$category && !$stmt->bind_param("s", $day)) {
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		}

		// execute
		if (!$stmt->execute()) {
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// bind result variables
		if (!$stmt->bind_result($name, $ip, $category, $vote)) {
			return error_log("Binding results failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// fetch results
		while ($stmt->fetch()) {
			if (!isset($return[$ip])) {
				$return[$ip] = [
					'name' => $name,
					'votes' => [],
				];
			}
			$return[$ip]['votes'][$category] = $vote;
		}
		$stmt->free_result();

		//error_log(print_r($return, true));

		return $return;
	}

	public function get_weekly($weeknumber, $yearnumber) {
		if (!$this->db_ok()) {
			return;
		}
		$return = [];

		$week_start_end = getStartAndEndDate($weeknumber, $yearnumber);

		// prepare statement
		if (!($stmt = $this->db->prepare("SELECT user.name, user.ip, vote.category, vote.vote FROM foodVote vote JOIN foodUser user ON vote.ip = user.ip WHERE day BETWEEN ? AND ?"))) {
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		}
		// bind params
		if (!$stmt->bind_param("ss", $week_start_end[0], $week_start_end[1])) {
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// execute
		if (!$stmt->execute()) {
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// bind result variables
		if (!$stmt->bind_result($name, $ip, $category, $vote)) {
			return error_log("Binding results failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// fetch results
		while ($stmt->fetch()) {
			if (!isset($return[$ip])) {
				$return[$ip] = [
					'name' => $name,
					'votes' => [],
				];
			}
			$return[$ip]['votes'][$category] = $vote;
		}
		$stmt->free_result();

		//error_log(print_r($return, true));

		return $return;
	}

	public function delete($day, $ip, $category=null) {
		if (!$this->db_ok()) {
			return;
		}

		// prepare statement
		if ($ip && $category && !($stmt = $this->db->prepare("DELETE FROM foodVote WHERE day=? AND ip=? AND category=?"))) {
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		} else if ($ip && !$category && !($stmt = $this->db->prepare("DELETE FROM foodVote WHERE day=? AND ip=?"))) {
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		}

		// bind params
		if ($ip && $category && !$stmt->bind_param("sss", $day, $ip, $category)) {
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		} else if ($ip && !$category && !$stmt->bind_param("ss", $day, $ip)) {
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		}

		// execute
		if (!$stmt->execute()) {
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		$stmt->free_result();
	}

}
