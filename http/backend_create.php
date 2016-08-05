<?php
require_once '_db.php';

$insert = "INSERT INTO Schedule (DayOfWeek, OnTime, OffTime, State) VALUES (:dayofweek, :ontime, :offtime, :state)";

$stmt = $db->prepare($insert);

// get the values I actuall need from the submitted datetimes
$start = date("Y-m-d", strtotime($_POST['start']));
$end = date("Y-m-d", strtotime($_POST['end']));
$startTime = date("H:i:s", strtotime($_POST['start']));
$endTime = date("H:i:s", strtotime($_POST['end']));
$dayofweek = date("N", strtotime($_POST['start']));

$stmt->bindParam(':dayofweek', $dayofweek);
$stmt->bindParam(':ontime', $startTime);
$stmt->bindParam(':offtime', $endTime);
$stmt->bindParam(':state', $_POST['name']);

$stmt->execute();

class Result {}

$response = new Result();
$response->result = 'OK';
$response->message = 'Created with id: '.$db->lastInsertId();

header('Content-Type: application/json');
echo json_encode($response);

?>
