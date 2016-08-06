<?php
require_once '_db.php';

class Result {}
$response = new Result();

$insert = "DELETE FROM Schedule WHERE id = :id";

$stmt = $db->prepare($insert);

$stmt->bindParam(':id', $_POST['id']);

try
{
	$stmt->execute();
	$response->result = 'OK';
	$response->message = 'Deleted.';
}
catch (PDOException $e)
{
	$response->result = 'ERROR';
	$response->message = $e->getMessage();	
}

header('Content-Type: application/json');
echo json_encode($response);

?>
