<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

class Event {}

#$db_exists = file_exists("daypilot.sqlite");

#$db = new PDO('sqlite:daypilot.sqlite');
$db = new PDO('mysql:host=fowie.com;dbname=CoxHome', 'CoxHome', '1590N1500W');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 

function AddItem($dow, $onTime, $offTime, $state)
{
	global $db;
	$insert = "INSERT INTO Schedule (DayOfWeek, OnTime, OffTime, Status) VALUES ('?', '?', '?', '?')";
	$stmt = $db->prepare($insert);
	$stmt->execute(array($dow, $onTime, $offTime, $state));
	$stmt = $db->prepare("SELECT last_insert_id()");
	$stmt->execute();
	return $stmt->fetch()[0];
}

function DeleteItem($id)
{
	global $db;
	$query = "DELETE FROM Schedule WHERE ID = ?";
	$stmt = $db->prepare($query);
	$stmt->execute(array($id));	
}

function GetAllItems()
{
	global $db;
	$query = "SELECT * FROM Schedule";	
	$stmt = $db->prepare($query);
	$stmt->execute();
	$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$events = array();

	foreach($items as $row) {
	// figure out what the date would be given today and the supplied day of week
	// step 1, get today's date
	$dayOfWeek = date("N"); //1-7 Mon-Sun
	$itemDayOfWeek = $row['DayOfWeek'];
	if($itemDayOfWeek < $dayOfWeek)
		$itemDate = date("Y-m-d", strtotime("-".($dayOfWeek - $itemDayOfWeek)." days"));
	else if($itemDayOfWeek > $dayOfWeek)
		$itemDate = date("Y-m-d", strtotime("+".($itemDayOfWeek - $dayOfWeek)." days"));
	else
		$itemDate = date("Y-m-d");

	  $e = new Event();
	  $e->id = $row['ID'];
	  $e->text = $row['State'];
	  $e->start = $itemDate."T".$row['OnTime'];
	  $e->end = $itemDate."T".$row['OffTime'];
	  $events[] = $e;
	}	
	return json_encode($events);
}


?>
