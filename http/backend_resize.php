<?php
require_once '_db.php';

$update = "UPDATE Schedule SET OnTime = :ontime, OffTime = :offtime WHERE id = :id";

$stmt = $db->prepare($update);

// get the values I actuall need from the submitted datetimes
$start = date("Y-m-d", strtotime($_POST['newStart']));
$end = date("Y-m-d", strtotime($_POST['newEnd']));
$startTime = date("H:i:s", strtotime($_POST['newStart']));
$endTime = date("H:i:s", strtotime($_POST['newEnd']));
$id = $_POST['id'];

//$f = fopen("log.txt", "w");
//fwrite($f, $startTime."\n");
//fwrite($f, $endTime."\n");
//fwrite($f, $_POST['id']."\n");

$stmt->bindParam(':ontime', $startTime);
$stmt->bindParam(':offtime', $endTime);
$stmt->bindParam(':id', $id);

//print("Statement:");
//print_r($stmt);

//fwrite($f, print_r($stmt, true)."\n");

$stmt->execute();

//fwrite($f, print_r($stmt->errorInfo(), true)."\n");
//print_r($stmt->errorInfo());
//fclose($f);

class Result {}

$response = new Result();
$response->result = 'OK';
$response->message = 'Update successful';

header('Content-Type: application/json');
echo json_encode($response);

?>
