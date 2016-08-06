<?php
require_once("_db.php");
error_reporting(E_ALL);
ini_set("display_errors", 1);


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
	print("Command: ".$command);
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
<script type="text/javascript">
function addEventHandlers(dp) {
  dp.onEventMoved = function (args) {
      $.post("backend_move.php", 
              {
                  id: args.e.id(),
                  newStart: args.newStart.toString(),
                  newEnd: args.newEnd.toString()
              }, 
              function(data, status) {
 		if(data.result == "OK")
		{
             	  console.log("Moved.");
		}
		else
		{
		  console.log(data);
		  console.log(status);
		}
              });
  };

  dp.onEventResized = function (args) {
      $.post("backend_resize.php", 
              {
                  id: args.e.id(),
                  newStart: args.newStart.toString(),
                  newEnd: args.newEnd.toString()
              }, 
              function() {
                  console.log("Resized.");
              });
  };

  // event creating
  dp.onTimeRangeSelected = function (args) {
      var name = prompt("Desired fan speed (HIGH or LOW):", "HIGH");
      dp.clearSelection();
      if (!name) return;
      var e = new DayPilot.Event({
          start: args.start,
          end: args.end,
          id: DayPilot.guid(),
          resource: args.resource,
          text: name
      });

      $.post("backend_create.php", 
              {
                  start: args.start.toString(),
                  end: args.end.toString(),
                  name: name
              }, 
              function(data, status) {
		if(data.result == "OK")
		{
		  alert("Successfully created.");
                  console.log("Created.");
      		  dp.events.add(e);
		} 
		else
		{
		  console.log("Data:");
		  console.log(data);
		  console.log("Status:");
		  console.log(status);
		  alert("Error creating event:"+data.message);
		  status = "error";
		}
              });

  };

  dp.onEventClick = function(args) {
      var r = confirm("Are you sure you want to delete this item?");
	if( r == true )
	{
		$.post("backend_delete.php",
		{
			id: args.e.id()
		},
		function(data, status) {
			if(data.result == "OK")
			{
				dp.events.remove(args.e);
				console.log("Deleted.");
			}
			else
			{
				console.log("Data:");
				console.log(data);
				console.log("Status:");
				console.log(status);
				alert("Error deleting event:"+data.message);
			}
		});
	}
  };
}

</script>

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
<!-- 
<form name="change" method="get">
<input type="submit" name="Toggle" value="Pump"/> <?php if($currentState["Pump"] == $on) echo "On"; else echo "Off"; ?><br/>
<input type="submit" name="Toggle" value="Hi"/><?php if($currentState["Hi"] == $on) echo "On"; else echo "Off"; ?><br/>
<input type="submit" name="Toggle" value="Low"/><?php if($currentState["Low"] == $on) echo "On"; else echo "Off"; ?><br/>
</form> -->
<div id="dpWeek"></div>
<script type="text/javascript">

  var day = new DayPilot.Calendar("dpWeek");
  day.viewType = "Week";
  day.events.list = <?php echo GetAllItems(); ?>;
  addEventHandlers(day);
  day.init();


</script>
<?php //echo GetAllItems(); ?>
<form action="backend_create.php" method="post">
<input type="hidden" name="start" value="2016-08-05T20:00:01"/>
<input type="hidden" name="end" value="2016-08-05T21:00:01"/>
<input type="hidden" name="id" value="4"/>
<input type="submit"/>
</form>
</body>
</html>
