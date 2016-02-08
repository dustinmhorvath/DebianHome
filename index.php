<?php

/*
*   +------------------------------------------------------------------------------+
*       SERVER STATUS SCRIPT
*   +------------------------------------------------------------------------------+
*/


/*
*   +------------------------------------------------------------------------------+
*        Configuration
*   +------------------------------------------------------------------------------+
*/
// Leave secondary empty if you don't have another mount that you want to watch.
$secondary = "";
// Put the domain of sites for top bar here. You might need to specify these
//  below if your configuration is more complicated.
$domain = "nichnologist.net";

// Define service checks
$services = Array(
	Array("80", 				"Internet Connection", 				"google.com"),
	Array("80",				"HTTP", 					""),
	Array("postfix",		 	"Postfix", 					""),
	Array("cron",				"Cron",						""),
	Array("3306", 				"MySQL", 					""),
	Array("445",				"Samba", 					""),
	Array("32400",				"Plex",						"localhost"),
	Array("21", 				"FTP", 						""),
	Array("22", 				"Internal SSH", 				""),
	Array("22", 				"External SSH", 				"$hostname"),
	Array("transmission",			"Transmission", 				""),
	Array("3389",		 	        "XRDP (Remote Desktop)",			""),
	Array("apache2",			"Apache2",					""),
	Array("openvpn",			"Openvpn",					""),
	Array("631",                        	"CUPS", 					""),
);

// Define Header Links
$links = Array(
	Array("Transmission",			"http:/\/torrent.$domain"),
	Array("Webmail", 			"http:/\/mail.$domain"),
	Array("Google Cloud Print", 		"https:/\/www.google.com/cloudprint?user=0#printers"),
	Array("RPi1 Status", 			"http:/\/rpi1.$domain/"),
	Array("RPi2 Status", 			"http:/\/rpi2.$domain"),
	Array("net Status", 			"http:/\/server.$domain"),
	Array("Gateway",			"https:/\/$domain:8080")
);

//Temperature readouts -- enter C or F (Default: C)
$degtype = "F";
$root_partition = chop(` df | grep /$ | awk {'print $(NF-5)'} `);
$hostname = gethostname();

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
//Start timer
$time_start = microtime_float();

//Get IP address
$_ip = gethostbyname($hostname);

//Generate Service table
$data0 = "";
foreach ($services as $service) {
	$status = "offline";
	if(is_numeric($service[0])){
		if($service[2]==""){
			$service[2] = "localhost";
		}

		$fp = @fsockopen("$service[2]", $service[0], $errno, $errstr, 1);

		if ($fp) {
			$status = "online";
			fclose($fp);
		}

		@fclose($fp);
	} else{
		exec("pgrep $service[0]", $output, $return);

		if ($return == 0) {
			$status = "online";
		}
	}

	$data0 .= "<tr><td>$service[1]</td><td class='status-$status'>$status</td></tr>"; //'#FFC6C6' '#D9FFB3'
}


//GET SERVER LOADS
$loadresult = @exec('uptime');
preg_match("/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/",$loadresult,$avgs);

//GET SERVER UPTIME
$uptime = explode(' up ', $loadresult);
$uptime = explode(',', $uptime[1]);
$uptime = $uptime[0].', '.$uptime[1];

//GET MEMORY DATA
$used = `free -m | grep "buffers/cache" | awk '{print $3}'`;
$totalram = chop(`free -m | grep Mem | awk '{print $2}'`);
$usedram_percent = round($used*100/$totalram);

$rootfs = chop(`df -h | grep $root_partition | awk '{ print $5}'`);
$rootfssize = chop(`df -h | grep $root_partition | awk '{print $2}'`);
$rootfsused = chop(`df -h | grep $root_partition | awk '{print $3}'`);

$extfs = chop(`df -h | grep $secondary | awk '{ print $5}'`);
$extfssize = chop(`df -h | grep $secondary | awk '{print $2}'`);
$extfsused = chop(`df -h | grep $secondary | awk '{print $3}'`);

//get ps data
$ps = (`ps aux | wc -l`)-1;

//Get network connection total
$numtcp = `netstat -nt | grep tcp | wc -l`;
$numudp = `netstat -nu | grep udp | wc -l`;

//Temperature value
if ($degtype == "F") {
$degrees="&deg;F";
}
else{
$degrees="&deg;C";
}

$cputemp= `cat /sys/class/thermal/thermal_zone0/temp`/1000;
if ($degtype == "F") {
$cputemp = $cputemp*9/5+32;
}
$cputemp= round(($cputemp), 1);

$gputemp= `temp | sed 's/temp=/\/' | sed 's/.C/\/'`;
if ($degtype == "F") {
$gputemp = $gputemp*9/5+32;
}
$gputemp= round(($gputemp), 1);

$pulldate = `git show --format='%ad' | grep "[0-9]:[0-9][0-9]:[0-9][0-9]"`;

$data1 = "<tr><td>Load Average </td><td>$avgs[1], $avgs[2], $avgs[3]</td>\n";
$data1 .= "<tr><td>Server Uptime</td><td>".$uptime."</td></tr>\n";
$data1 .= "<tr><td>Memory In Use	</td><td>$usedram_percent% (".$used."MB/".$totalram."MB)</td></tr>\n";
$data1 .= "<tr><td>Root	</td><td>$rootfs ($rootfsused/$rootfssize)</td></tr>\n";
if (!empty($secondary)) {
  $data1 .= "<tr><td>Secondary	</td><td>$extfs ($extfsused/$extfssize)</td></tr>\n";
}
$data1 .= "<tr><td>Server processes	</td><td>$ps Processes</td></tr>\n";
$data1 .= "<tr><td>Open Connections	</td><td>TCP: $numtcp\tUDP: $numudp</td></tr>\n";
$data1 .= "<tr><td>CPU Temp	</td><td>$cputemp $degrees</td></tr>\n";
$data1 .= "<tr><td>Last Pull	</td><td>$pulldate</td></tr>\n";

//$data1 .= "<tr><td>GPU Temp</td><td>$gputemp $degrees</td></tr>\n";



//Generate Links
//<li><a href="/transmission">Transmission</a></li>
foreach ($links as $link){
$linkdata .= "<li><a href='$link[1]'>$link[0]</a></li>";
}

?>
<!DOCTYPE html>
<!--[if lt IE 7 ]><html class="ie ie6" lang="en"> <![endif]-->
<!--[if IE 7 ]><html class="ie ie7" lang="en"> <![endif]-->
<!--[if IE 8 ]><html class="ie ie8" lang="en"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html lang="en"> <!--<![endif]-->
<head>

    <!-- Basic Page Needs
  ================================================== -->
	<meta charset="utf-8">
	<title><?php echo $hostname; ?> Status</title>
	
    <!-- Mobile Specific Metas
  ================================================== -->
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    
    <!-- CSS
  ================================================== -->
	<link rel="stylesheet" href="css/zerogrid.css">
	<link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
	<link rel="stylesheet" href="css/flexslider.css" type="text/css" media="screen" />

	<!--[if lt IE 8]>
       <div style=' clear: both; text-align:center; position: relative;'>
         <a href="http://windows.microsoft.com/en-US/internet-explorer/products/ie/home?ocid=ie6_countdown_bannercode">
           <img src="http://storage.ie6countdown.com/assets/100/images/banners/warning_bar_0000_us.jpg" border="0" height="42" width="820" alt="You are using an outdated browser. For a faster, safer browsing experience, upgrade for free today." />
        </a>
      </div>
    <![endif]-->
    <!--[if lt IE 9]>
		<script src="js/html5.js"></script>
		<script src="js/css3-mediaqueries.js"></script>
	<![endif]-->

	<link href='./images/favicon.png' rel='icon' type='image/x-icon'/>
</head>
<body>
<!--------------Header--------------->
<div class="wrap-header">
<header>
	<div id="logo"><a href="#"><img src="./images/logo.png"/></a></div>

	<nav>
		<ul>
			<?php echo $linkdata; ?>
		</ul>
	</nav>
</header>
</div>

<!--------------Content--------------->
<section id="content">
	<div class="zerogrid">
		<div class="row">
			<div id="main-content">
				<article style="margin-left: 20px;">
					<div class="heading">
						<h2><?php echo $hostname; ?> Service Status</h2>
					</div>
					<div class="content">
						<table style="width:500px;">
							<tr><th>Service</th><th>Status</th></tr>
							<?php
								echo $data0;
							?>
						</table>
					</div>
				</article>
				<article style="position: absolute; top: 0px; left: 650px;">
					<div class="heading">
						<h2>System Status</h2>
					</div>
					<div class="content">
						<table style="width:500px;">
						<tr><th />&nbsp;<th /></tr>
						<?php
							echo $data1;
						?>
						</table>
					</div>
				</article>
			</div>
		</div>
	</div>
</section>
<!--------------Footer--------------->
<div class="wrap-footer">
	<footer>
		<div class="wrapfooter">
			<p>Page generated in <?php echo round(microtime_float() - $time_start, 1); ?> seconds</p>
		</div>
	</footer>
</div>
</body></html>
