<?php
//http://www.gazbming.com/ 
// Author: jjkavalam@gmail.com
// Add one frame for every tick
// If there is an event at that time, then update the frame 
//
// Read the xml file given as argument
// if "<stroke .*>" matches then
// if "</stroke> matches then close current stroke
// inside stroke look for event
// <event .*>
// read that line and extract x=".*"
//
// Timing calculation
// nextFrame until 
// 1 frame for each tick
// else 1 frame for ticks_per_frame 
// -- Main --
// -- Commandline --
if ($argc == 1)
{
	die("Usage: script <datafile> <scale_factor(eg. 0.5)>");
}
$filename = $argv[1];
$scale_xy = $argv[2];

$fh = fopen($filename,"r") or exit("Unable to open file.");

// some typical movie variables
Ming_setScale(20.0000000);
ming_useswfversion(4);
$movie=new SWFMovie();
# 550 x 400
$movie->setDimension(800,600);
$movie->setBackground(rand(0,0xFF),rand(0,0xFF),rand(0,0xFF));
$movie->setRate(30); 

// -- Read file line by line --
// Parameters
$ticks_per_frame = 5;
// Initializations
$lx = 0; $ly = 0; $lt = 0;
$pattern_event = '/<event x="(.*)" y="(.*)" pressure="(.*)" event_time="(.*)"\/>/';
$pattern_stroke = '/<stroke type="(.*)" color="(.*)" alpha="(.*)" width="(.*)" meta_state="(.*)">/';
// First frame
$flag_start_stroke = 1;
$flag_start_movie = 1;

while(!feof($fh))
{
	$line = fgets($fh);

	// If stroke
	preg_match($pattern_stroke, $line, $matches);
	if (count($matches)==6)
	{
		$color = $matches[2];
		$width = $matches[4];
		$flag_start_stroke = 1;
	}

	// If event
	preg_match($pattern_event, $line, $matches);
	if (count($matches)==5)
	{
		$x = $matches[1];
		$x = $x * $scale_xy;

		$y = $matches[2];
		$y = $y * $scale_xy;

		$t = $matches[4];

		$s = new SWFShape();

		if ($flag_start_stroke == 1)
		{
			$flag_start_stroke = 0;
		}
		else
		{
			$s->movePenTo($lx, $ly);
			$s->setLine(4, 0, 0, 0);
			$s->drawLineTo($x, $y);
		}

		// Add enough frames before adding new shape
		if ($flag_start_movie == 1)
		{
			$flag_start_movie = 0;
			$lt = $t;
		}

		$delta_t = $t - $lt;
		$frames_to_pad = round($delta_t / $ticks_per_frame);

		$movie->nextFrame();
		for($i=0;$i<$frames_to_pad-1;$i++) $movie->nextFrame();

		$movie->add($s);

		$lx = $x;
		$ly = $y;
		$lt = $t;

	}
}
fclose($fh);

// save swf with same name as filename
$swfname = basename(__FILE__,".php");
$movie->save("$swfname.swf");

?>
