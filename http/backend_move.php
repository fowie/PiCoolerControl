<?php
require_once '_db.php';

class Result {}

$insert = "UPDATE Schedule SET DayOfWeek = :dayofweek, OnTime = :ontime, OffTime = :offtime  WHERE id = :id";

$stmt = $db->prepare($insert);

// split apart the datetime
$newDayOfWeek = date("N", strtotime($_POST['newStart']));
$newStart = date("H:i:s", strtotime($_POST['newStart']));
$newEnd = date("H:i:s", strtotime($_POST['newEnd']));

$stmt->bindParam(':dayofweek', $newDayOfWeek);
$stmt->bindParam(':ontime', $newStart);
$stmt->bindParam(':offtime', $newEnd);
$stmt->bindParam(':id', $_POST['id']);

$f = fopen("log.txt", "w");
fwrite($f, "Start\n");
fwrite($f, $newDayOfWeek."\n");
fwrite($f, $newStart."\n");
fwrite($f, $newEnd."\n");
fwrite($f, $_POST['id']."\n");
fwrite($f, print_r($stmt, true));
fwrite($f, "End\n");
fclose($f);

$response = new Result();
try
{
	$stmt->execute();
	$response->result = 'OK';
	$response->message = 'Update successful';
}
catch (PDOException $e)
{
	$response->result = 'ERROR';
	$response->message = $e->getMessage();	
}

header('Content-Type: application/json');
echo json_encode($response);

?>
