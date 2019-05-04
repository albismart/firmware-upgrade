<?php
 ob_start();
 session_start();
 function host() { return $_SERVER['HTTP_HOST']; }
 function request() { return $_SERVER['REQUEST_URI']; }
 function browsing() { return host().request(); };
 function domain() { return 'http://' . host(); }
 function get($key, $default = null) {
 	$k = (strpos($key,'.')===false) ? null : ltrim(strstr($key,'.'),'.');
 	$s = ($k==null) ? $key : strstr($key, '.', true); 
 	
 	if(!$k) { return (isset($_SESSION[$s])) ? $_SESSION[$s] : $default; }
 	return (isset($_SESSION[$s]) && isset($_SESSION[$s][$k])) ? $_SESSION[$s][$k] : $default;
 }
 function session($key) { return (isset($_SESSION[$key])) ? $_SESSION[$key] : array(); }
 function save($key, $data) { $_SESSION[$key] = array_filter($data); }
 function delete($key) { unset($_SESSION[$key]); }

 if(isset($_POST) && isset($_GET['page'])) {
 	switch ($_GET['page']) {
 		case 'snmp-settings': 
			$snmp = session('snmp');
			if(isset($_POST['discovery'])) { $snmp['discovery'] = $_POST['discovery']; }
			if(isset($_POST['destination'])) { $snmp['destination'] = $_POST['destination']; }
			if(isset($_POST['filename'])) { $snmp['filename'] = $_POST['filename']; }
			if(isset($_POST['upgradestatus'])) { $snmp['upgradestatus'] = $_POST['upgradestatus']; }
 			save('snmp', $snmp);
 		break;
 		case 'system-settings': 
			$system = session('system');
			if(isset($_POST['path'])) { $system['path'] = $_POST['path']; }
			if(isset($_POST['server'])) { $system['server'] = $_POST['server']; }
			if(isset($_POST['cmts'])) { $system['cmts'] = $_POST['cmts']; }
			if(isset($_POST['community'])) { $system['community'] = $_POST['community']; }
			if(count($system)>0) { save('system', $system); } else { delete('system'); }
 		break;
 		case 'upgrade-flow': 
			$config = session('config');
			if(isset($_POST['firmware'])) { $system['firmware'] = $_POST['firmware']; }
			if(isset($_POST['modem'])) { $system['modem'] = $_POST['modem']; }
 		break;
 	}
 }

?>
<!DOCTYPE html>
<html>
<head>
	<title>Firmware Upgrade - AlbiSmart</title>
	<link rel="icon" href="//asmart.ams3.cdn.digitaloceanspaces.com/favicon/color.ico" />
	<link rel="stylesheet" href="//unpkg.com/spectre.css/dist/spectre.min.css">
</head>
<body>
	<div class="container">
		<div class="columns">
			<div class="column col-12">
				<img src="//asmart.ams3.cdn.digitaloceanspaces.com/logo/logo-name.svg" style="width:220px;margin: 20px 20px 10px;float:left">
				<p style="margin-top:35px;float: left;"> Browsing: <?php echo browsing(); ?> </p>
				<div class="buttons" style="float:right;margin: 25px">
					<a class="btn" href="<?php echo domain(); ?>?page=snmp-settings">SNMP Object ID's</a>
					<a class="btn" href="<?php echo domain(); ?>?page=system-settings">System Settings</a>
					<a class="btn btn-success" href="<?php echo domain(); ?>?page=upgrade-flow">Upgrade Flow →</a>
				</div>
				<div class="clearfix"></div>	
				<div class="divider"></div>	
			</div>
		</div>

		<?php if(isset($_GET['page'])) { $page = $_GET['page']; } else { $page = null; } ?>

		<?php if($page==null) { ?>

			<h3 style="font-weight: 300; margin: 100px auto;width: 100%; text-align: center;"> Initiate the progress by clicking Start or continue with configurations </h3>

			<a class="btn btn-success" href="<?php echo domain(); ?>?page=upgrade-flow" style="height:70px;width:200px;margin: auto;font-size:22px;line-height: 60px;display: block;"> START </a>

			<a class="btn" href="<?php echo domain(); ?>?page=system-settings" style="width:125px;margin: 50px auto;display: block;"> Configure </a>

		<?php } ?>

		<?php if($page=='snmp-settings') { ?>
			<form method="POST" action="<?php echo domain(); ?>?page=snmp-settings">
				<dic class="columns">
					<div class="column col-6 col-mx-auto" style="margin:40px auto;text-align: center;">
						<h2>Update Session based SNMP Settings</h2>
					</div>
				</div>
				<dic class="columns">
					<div class="column col-3"></div>
					<div class="column col-6">
						<div class="form-group">
						  <label class="form-label">CMTS, active Cable Modems (Default: 1.3.6.1.2.1.10.127.1.3.3.1.3)</label>
						  <input class="form-input" name="discovery" placeholder="Valid OID" value="<?php echo get('snmp.discovery'); ?>">
						</div>
					</div>
					<div class="column col-3"></div>
					<div class="column col-3"></div>
					<div class="column col-6"> <div class="divider" style="margin: 25px 0"></div> </div>
					<div class="column col-3"></div>
					<div class="column col-3"></div>
					<div class="column col-6">
						<div class="form-group">
						  <label class="form-label">Cable Modem, set firmware requests destination (Default: 1.3.6.1.2.1.69.1.3.1.0)</label>
						  <input class="form-input" name="destination" placeholder="Valid OID" value="<?php echo get('snmp.destination'); ?>">
						</div>
					</div>
					<div class="column col-3"></div>

					<div class="column col-3"></div>
					<div class="column col-6">
						<div class="form-group">
						  <label class="form-label">Cable Modem, set firmware filename (Default: 1.3.6.1.2.1.69.1.3.2.0)</label>
						  <input class="form-input" name="filename" placeholder="Valid OID" value="<?php echo get('snmp.filename'); ?>">
						</div>
					</div>
					<div class="column col-3"></div>

					<div class="column col-3"></div>
					<div class="column col-6">
						<div class="form-group">
						  <label class="form-label">Cable Modem, set status to proceed firmware upgrade (Default: 1.3.6.1.2.1.69.1.3.3.0)</label>
						  <input class="form-input" name="upgradestatus" placeholder="Valid OID" value="<?php echo get('snmp.upgradestatus'); ?>">
						</div>
					</div>
					<div class="column col-3"></div>
					<div class="column col-3"></div>
					<div class="column col-6" style="margin-top: 30px">
						<button type="submit" class="btn btn-success">Save changes</button>
						<button type="button" class="btn" style="float:right" onclick="defaults()">Reset defaults</button>
					</div>

				</div>
			</form>
		<?php } ?>

		<?php if($page=='system-settings') { ?>
			<form method="POST" action="<?php echo domain(); ?>?page=system-settings">
				<dic class="columns">
					<div class="column col-6 col-mx-auto" style="margin:40px auto;text-align: center;">
						<h2>Update Session based System Settings</h2>
					</div>
				</div>
				<dic class="columns">
					<div class="column col-3"></div>
					<div class="column col-6">
						<div class="form-group">
						  <label class="form-label">TFTP full path (Default: /home/albismart/tftpboot)</label>
						  <input class="form-input" name="path" placeholder="String" value="<?php echo get('system.path'); ?>">
						</div>
					</div>
					<div class="column col-3"></div>

					<div class="column col-3"></div>
					<div class="column col-6">
						<div class="form-group">
						  <label class="form-label">TFTP Server IP (Default: 172.22.0.13)</label>
						  <input class="form-input" name="server" placeholder="Valid IP" value="<?php echo get('system.server'); ?>">
						</div>
					</div>
					<div class="column col-3"></div>

					<div class="column col-3"></div>
					<div class="column col-6">
						<div class="form-group">
						  <label class="form-label">CMTS IP (Default: 172.22.0.22)</label>
						  <input class="form-input" name="cmts" placeholder="Valid IP" value="<?php echo get('system.cmts'); ?>">
						</div>
					</div>
					<div class="column col-3"></div>

					<div class="column col-3"></div>
					<div class="column col-6">
						<div class="form-group">
						  <label class="form-label">CMTS Read Community (Default: albismart)</label>
						  <input class="form-input" name="community" placeholder="String" value="<?php echo get('system.community'); ?>">
						</div>
					</div>
					<div class="column col-3"></div>

					<div class="column col-3"></div>
					<div class="column col-6" style="margin-top: 30px">
						<button type="submit" class="btn btn-success">Save changes</button>
						<button type="button" class="btn" style="float:right" onclick="defaults()">Reset defaults</button>
					</div>

				</div>
			</form>
		<?php } ?>

		<?php if($page=='upgrade-flow') { ?>
			<?php
			$modems = snmpget(get('system.cmts','172.22.0.22'), get('system.community','albismart'), get('snmp.discovery','1.3.6.1.2.1.10.127.1.3.3.1.3'), 100000, 2);

			var_dump($modems); ?>
			<dic class="columns">
				<div class="column col-6 col-mx-auto" style="margin:40px auto 15px;text-align: center;">
					<h2>Session based Upgrade Flow</h2>
				</div>
			</div>

			<dic class="columns">
				<div class="column col-3"></div>
				<div class="column col-6" style="text-align: center">
					<h5>TFTP Server IP: <small><?php echo (get('system.server')) ? get('system.server') : '172.22.0.13'; ?></small> <a href="<?php echo domain(); ?>?page=system-settings">(change)</a></h5>
					<h5>TFTP Path: <small><?php echo (get('system.path')) ? get('system.path') : '/home/albismart/tftpboot/'; ?></small>  <a href="<?php echo domain(); ?>?page=system-settings">(change)</a></h5>
					<h5>CMTS IP: <small><?php echo (get('system.cmts')) ? get('cmts.server') : '172.22.0.22'; ?></small>  <a href="<?php echo domain(); ?>?page=system-settings">(change)</a></h5>
					<h5>CMTS Read Community: <small><?php echo (get('system.community')) ? get('cmts.community') : 'albismart'; ?></small> <a href="<?php echo domain(); ?>?page=system-settings">(change)</a></h5>
				</div> <div class="column col-3"></div>

				<div class="column col-12"> <div class="divider"></div> </div>

				<div class="column col-6 col-mx-auto" style="text-align: center;">
					<form action="<?php echo domain(); ?>?page=upgrade-flow" method="post" enctype="multipart/form-data">
						<h2>Upload a firmware file</h2>
						<input type="file" name="firmware" onchange="document.forms[0].submit()">
					</form>
				</div>
			</div>
		<?php } ?>
		

	</div>

	<script> function defaults() { for (var i=0;i<document.getElementsByTagName("input").length; i++) { document.getElementsByTagName("input")[i].value = ''; } document.forms[0].submit(); } </script>
</body>
</html>