<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
<title>MultiWii Configurator</title>
<!-- Bootstrap -->
<link href="../bootstrap-3.3.6-dist/css/bootstrap.min.css" rel="stylesheet">


<link href="../styles/style.css" rel="stylesheet">

</head>

<body>

<?php
	session_start();

	$x = '/dev/spidev0.0';
	if (!file_exists($x)) {
		die('Error: "'.$x.'"" does not exist!');
	}

	$x = 'whereis avrdude';
	exec($x,$output) or die('Failed to run: '.$x);

	$location=explode(' ',implode($output));
	if (count($location)<2) die('Failed to run: '.$x);

	$location=$location[1];

	if (!is_executable($location)) {
		die('Error: "'.$location.' does not exist or is not executable!');
	}

	//TODO: panel0: info & welcome; if sources not exist show button to download git multiwii to /opt/ location (fixed location)
	//TODO: panel1: Makefile
	//TODO: panel2: config.h parsed and save button (or just simple text for the time being)
	//TODO: panel3: compile & flash
		//check if build/...hex exist if not - dont show flash button (only recompile)

	$current_page = "info"; //assume status is the current page

	if(isset($_GET["p"])) $current_page = $_GET["p"];

	$pages = [
        "info" => "Info",
        "config" => "Config",
        "compile" => "Compile & flash"
	];

?>

<nav class="navbar navbar-inverse navbar-fixed-top">
	<div class="container">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<div class="navbar-brand"><?php echo strtoupper($pages[$current_page]); ?></div>
		</div>
		<div id="navbar" class="collapse navbar-collapse">
			<ul class="nav navbar-nav">
<?php
foreach ($pages as $key => $value) {
	if ($current_page == $key)
		echo '<li class="active"><a href="?p='.$key.'">'.$value.'</a></li>';
	else
		echo '<li><a href="?p='.$key.'">'.$value.'</a></li>';
}
?>
			</ul>
		</div>
	</div>
</nav>

<div class="container">

<?php
include('p_'.$current_page.'/content.php');
?>

<div id="info" class="alert alert-info" style="display: none;"></div>
<div id="danger" class="alert alert-danger" style="display: none;"></div>
</div>



<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="../jquery/jquery-2.2.0.min.js"></script>
<!-- Include all compiled plugins (below), or include individual files as needed -->
<script src="../bootstrap-3.3.6-dist/js/bootstrap.min.js"></script>

</body>
</html>

