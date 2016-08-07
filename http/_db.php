<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

class Event {}

#$db_exists = file_exists("daypilot.sqlite");

#$db = new PDO('sqlite:daypilot.sqlite');
$db = new PDO('mysql:host=fowie.com;dbname=CoxHome', 'CoxHome', '1590N1500W');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 

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
	//print("Day of week: ".$dayOfWeek."\n");
	$itemDayOfWeek = $row['DayOfWeek'];
	//print("Item day ofw eek: ".$itemDayOfWeek."\n");
	if($itemDayOfWeek < $dayOfWeek && $dayOfWeek < 7)
	{
		$itemDate = date("Y-m-d", strtotime("-".($dayOfWeek - $itemDayOfWeek)." days"));
	}
	else if($itemDayOfWeek > $dayOfWeek)
	{
		$itemDate = date("Y-m-d", strtotime("+".($itemDayOfWeek - $dayOfWeek)." days"));
	}
	else if($itemDayOfWeek != 7 && $dayOfWeek == 7)
	{
		$itemDate = date("Y-m-d", strtotime("+".$itemDayOfWeek." days"));
	}
	else
	{
		$itemDate = date("Y-m-d");
	}
	//print("Date: ".$itemDate."\n");

	  $e = new Event();
	  $e->id = $row['ID'];
	  $e->text = $row['State'];
	  $e->start = $itemDate."T".$row['OnTime'];
	  $e->end = $itemDate."T".$row['OffTime'];
	  $events[] = $e;
	}	
	return json_encode($events);
}

function GetState()
{
	global $db;
	$query = "SELECT * FROM Cooler_State LIMIT 1";
	$stmt = $db->prepare($query);
	$stmt->execute();
	$dbstate = $stmt->fetch(PDO::FETCH_ASSOC);
	$state = array("Current State End Time"=>$dbstate['CS_End_Time'], "Current State"=>$dbstate['CS'], "Next State"=>$dbstate['NS'], "Next State Duration"=>$dbstate['NS_Duration']);
	return $state;
}
?>
