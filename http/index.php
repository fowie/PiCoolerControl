<?php
require_once("_db.php");
$on = "0";
$off = "1";

$gpiowrite = "gpio -g write ";
$gpioread = "gpio -g read ";

$currentState = array("Pump" => $off, "Hi" => $off, "Low" => $off);
$pins = array("Pump" => "2", "Hi" => "3", "Low" => "4");

UpdateStates();

if(isset($_GET['Toggle']))
{
	print("Toggling ".$_GET['Toggle'].".  Current state: ".$currentState[$_GET['Toggle']]);
	if($currentState[$_GET['Toggle']] == $on)
	{
		GpioSet($_GET['Toggle'], $off);
	}
	else
	{
		GpioSet($_GET['Toggle'], $on);
	}
}

function GpioSet($pin, $newVal)
{
	global $gpiowrite, $on, $off, $pins, $currentState;
	$command = $gpiowrite." ".$pins[$pin]." ".$newVal;
	//print("Command: ".$command);
	exec($command);
}

function UpdateStates()
{
	global $pins, $currentState;
	foreach($pins as $pin => $pinNum)
	{
		//print("Getting state for ".$pin." pin number ".$pinNum." was: ".$currentState[$pin]);
		$currentState[$pin] = GpioGet($pin);
		//print("Setting new state for ".$pin." as ".$currentState[$pin]);
	}
}

function GpioGet($pin)
{
	global $gpioread, $pins, $on, $off, $currentState;
	$output = array();
	$command = $gpioread." ".$pins[$pin];
	exec($command, $output);
	//print("pin:".$pin." = ".$output[0]);
	return($output[0]);
}


UpdateStates();
?>
<html>
<head>
<title>Cooler Control</title>
<link type="text/css" rel="stylesheet" href="media/layout.css" />    
<!-- helper libraries -->
<script src="js/jquery-1.9.1.min.js" type="text/javascript"></script>
<!-- daypilot libraries -->
<script src="js/daypilot/daypilot-all.min.js" type="text/javascript"></script>
<style>
            .buttons a {
                text-decoration: none;
                color: black;
                display: inline-block;
                margin-right: 5px;
            }
            .selected-button {
                border-bottom: 2px solid orange;
            }
</style>

</head>
<body>
<?php
//connect to DB

//get current relay settings

//provide buttons for changing each relay

?>
<form name="change" method="get">
<input type="submit" name="Toggle" value="Pump"/> <?php if($currentState["Pump"] == $on) echo "On"; else echo "Off"; ?><br/>
<input type="submit" name="Toggle" value="Hi"/><?php if($currentState["Hi"] == $on) echo "On"; else echo "Off"; ?><br/>
<input type="submit" name="Toggle" value="Low"/><?php if($currentState["Low"] == $on) echo "On"; else echo "Off"; ?><br/>
</form>
<div id="dpWeek"></div>
<script type="text/javascript">

  var day = new DayPilot.Calendar("dpWeek");
  day.viewType = "Week";
  day.init();


  day.events.load("GetAllItems.php");

</script>
<?php echo GetAllItems(); ?>
</body>
</html>
