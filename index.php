<?php

	ob_start();
	session_start();
	function host() { return $_SERVER['HTTP_HOST']; }
	function request() { return $_SERVER['REQUEST_URI']; }
	function browsing() { return host().request(); };
	function domain() { return (strpos(request(), '?')===false) ? 'http://' . host() . request() : 'http://' . host() . strstr(request(), '?', true); }

	// Session handler
	function session($key) { return (isset($_SESSION[$key])) ? $_SESSION[$key] : array(); }
	function save($key, $data) { $_SESSION[$key] = array_filter($data); }
	function delete($key) { unset($_SESSION[$key]); }
	function get($key, $default = null) {
		$k = (strpos($key,'.')===false) ? null : ltrim(strstr($key,'.'),'.');
		$s = ($k==null) ? $key : strstr($key, '.', true); 
		
		if(!$k) { return (isset($_SESSION[$s])) ? $_SESSION[$s] : $default; }
		return (isset($_SESSION[$s]) && isset($_SESSION[$s][$k])) ? $_SESSION[$s][$k] : $default;
	}

	// SNMP Aliases
	function modemstatus($id) { return snmpget(cmts(), community(), '1.3.6.1.2.1.10.127.1.3.3.1.9.'.$id, 50000,2); }
	function discovery() {	return get('snmp.discovery','1.3.6.1.2.1.10.127.1.3.3.1.3'); }

	// System Aliases
	function server() {		return get('system.server','172.22.0.13'); }
	function cmts() {		return get('system.cmts','172.22.0.22'); }
	function path() { 		return get('system.path','/home/albismart/tftpboot/'); }
	function community() {	return get('system.community','albismart'); }

	// Config Aliases
	function modem() { return get('config.modem', @$_GET['modem']); }
	function firmware() { return get('config.firmware', @$_GET['firmware']); }

	// Saving changes
	if(isset($_POST) && isset($_GET['page'])) {
		switch ($_GET['page']) {
			case 'snmp-settings': 
				$snmp = session('snmp');
				if(isset($_POST['discovery'])) { $snmp['discovery'] = $_POST['discovery']; }
				if(isset($_POST['destination'])) { $snmp['destination'] = $_POST['destination']; }
				if(isset($_POST['filename'])) { $snmp['filename'] = $_POST['filename']; }
				if(isset($_POST['upgradestatus'])) { $snmp['upgradestatus'] = $_POST['upgradestatus']; }
				if(count($snmp)>0) { save('snmp', $snmp); } else { delete('snmp'); }
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
				$system = session('system');
				$config = session('config');
				if(isset($_POST['firmware'])) { $config['firmware'] = $_POST['firmware']; }
				if(isset($_POST['modem'])) { $config['modem'] = $_POST['modem']; }
				if(count($config)>0) { save('config', $config); } else { delete('config'); }
				$server = (isset($system['server'])) ? $system['server'] : '172.22.0.13';
				$cmts = (isset($system['cmts'])) ? $system['cmts'] : '172.22.0.22';
				$path = (isset($system['path'])) ? $system['path'] : '/home/albismart/tftpboot/';

				if(isset($config['modem']) && isset($config['firmware'])) {
					$setServer = snmpset($config['modem'], 'public', '1.3.6.1.2.1.69.1.3.1.0', 'a', $server, 150000, 1);
					$setFirmware = snmpset($config['modem'], 'public', '1.3.6.1.2.1.69.1.3.2.0', 's', $config['firmware'], 150000, 1);
					$setUpgrade = snmpset($config['modem'], 'public', '1.3.6.1.2.1.69.1.3.3.0', 'i', 1, 150000, 1);

					$success = ($setServer && $setFirmware && $setUpgrade) ? 1 : 0;
					header('Location: ' . domain() . '?page=upgrade-status&success='.$success.'&modem='.$config['modem'].'&firmware='.$config['firmware']);
				}
			break;
			case 'upload':
				$uploadedFirmware = basename($_FILES["new_firmware"]["name"]);
				if(move_uploaded_file($_FILES['new_firmware']['tmp_name'], path() . $uploadedFirmware)) {
					$config = session('config');
					$config['firmware'] = $uploadedFirmware;
					save('config', $config);
				}
				header('Location: ' . domain() . '?page=upgrade-flow');
			break;
		}
	}

?><!DOCTYPE html>
<html>
<head>
	<title>Firmware Upgrade - AlbiSmart</title>
	<link rel="icon" href="//asmart.ams3.cdn.digitaloceanspaces.com/favicon/color.ico" />
	<link rel="stylesheet" href="//unpkg.com/spectre.css/dist/spectre.min.css">
</head>
<body>
	<div class="container" style="overflow: hidden;">
		<div class="columns">
			<div class="column col-12">
				<a href="<?php echo domain(); ?>">
					<img src="//asmart.ams3.cdn.digitaloceanspaces.com/logo/logo-name.svg" style="width:220px;margin: 20px 20px 10px;float:left">
				</a>
				<p class="text-gray" style="margin-top:35px;float: left;"> Browsing: <?php echo array_shift(explode('&', browsing())); ?> </p>
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

			<h3 class="text-center" style="font-weight: 300; margin: 100px auto;width: 100%;"> Initiate the progress by clicking Start or continue with configurations </h3>

			<a class="btn btn-success" href="<?php echo domain(); ?>?page=upgrade-flow" style="height:70px;width:200px;margin: auto;font-size:22px;line-height: 60px;display: block;"> START → </a>

			<a class="btn" href="<?php echo domain(); ?>?page=system-settings" style="width:125px;margin: 50px auto;display: block;"> Configure </a>

		<?php } ?>

		<?php if($page=='snmp-settings') { ?>
			<form method="POST" action="<?php echo domain(); ?>?page=snmp-settings">
				<div class="columns">
					<div class="column col-6 col-mx-auto" style="margin:40px auto;text-align: center;">
						<h2>Update Session based SNMP Settings</h2>
					</div>
				</div>
				<div class="columns">
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
				<div class="columns">
					<div class="column col-6 col-mx-auto" style="margin:40px auto;text-align: center;">
						<h2>Update Session based System Settings</h2>
					</div>
				</div>
				<div class="columns">
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
				$modems = snmprealwalk(cmts(), community(), discovery(), 150000, 1);
				$images = glob(path()."*.{bin,img,b7b}", GLOB_BRACE);
			?>
			<div class="columns">
				<div class="column col-6 col-mx-auto" style="margin:40px auto 15px;text-align: center;">
					<div class="panel">
						<div class="panel-header">
							<div class="panel-title"><h3 style="font-weight: 300"> <font style="float:left">ⓘ</font> Session based Upgrade Flow</h3></div>
							<div class="divider"></div>
						</div>
						<div class="panel-body">
							<div class="columns">
								<div class="column col-3"></div>
								<div class="column col-6 text-center">
									<h5>TFTP Server IP:
										<small><?php echo server(); ?></small> <a href="<?php echo domain(); ?>?page=system-settings">(change)</a>
									</h5>
									<h5>TFTP Path:
										<small><?php echo path(); ?></small>  <a href="<?php echo domain(); ?>?page=system-settings">(change)</a>
									</h5>
									<h5>CMTS IP:
										<small><?php echo cmts(); ?></small>  <a href="<?php echo domain(); ?>?page=system-settings">(change)</a>
									</h5>
									<h5>CMTS Read Community:
										<small><?php echo community(); ?></small> <a href="<?php echo domain(); ?>?page=system-settings">(change)</a>
									</h5>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="columns">				
				<div class="column col-3"></div>
				<div class="column col-6 text-center">
					<form action="<?php echo domain(); ?>?page=upgrade-flow" class="column col-12" method="post" enctype="multipart/form-data">
						<div class="panel">
							<div class="panel-header">
								<div class="panel-title"><h3 style="font-weight: 300;margin:0">Select a modem and firmware</h3></div>
							</div>
							
							<div class="divider"></div>

							<div class="panel-body">
								<div class="columns">
									<div class="form-group column col-12">
									  <label>Modem:</label>
									  <select class="form-select" name="modem" style="width:100%">
									  	<?php if($modems && count($modems)>0) { foreach($modems as $oid => $modem) {
									  		$modemIP = ""; $modemID = "";
									  		if($modem=='0.0.0.0') continue;
									  		if(is_object($modem) && isset($modem->value)) { $modemIP = $modem->value; }
									  		if(is_string($modem)) {
									  			if(strpos($modem, "IpAddress:")===false) {
								                	$modemIP = $modem;
								                } else {
								                	$modemIP = ltrim(strstr($modem, ": "), ": ");
								                }
									  		}
									  		
									  		$modemID = ltrim(strstr($oid, '3.3.1.3.'), '3.3.1.3.');
							                $modemStatus = modemstatus($modemID);
							                if(is_object($modemStatus)) { $modemStatus = $modemStatus->value; }
							                if(is_string($modemStatus)) { $modemStatus = str_replace("INTEGER: ", '', $modemStatus); }
							                
							                if($modemStatus!=6) continue;
							                
									  		$description = snmpget($modemIP, 'public', '1.3.6.1.2.1.1.1.0', 100000, 2); 
									  		if(is_object($description)) { $description = $description->value; }
							                if(is_string($description)) { $description = str_replace("STRING: ", '', $description); } ?>
									    	<option value="<?php echo $modemIP; ?>"><?php echo $modemIP . ' – ' . $description; ?></option>
									    <?php } } if(!$modems || count($modems)==0) { ?>
									    	<option value=""> No modems found, refresh the page </option>
									    <?php } ?>
									  </select>
									</div>
								</div>
								
								<div class="divider" style="margin: 25px 0 15px"></div>
								
								<div class="columns" id="select-firmware">
									<div class="form-group column col-11" style="float:left">
									  <label>Select firmware:</label>
									  <input class="form-input" list="firmwares" name="firmware" placeholder="Select an existing firmware" />
									  <datalist  id="firmwares">
									  	<?php if($images && count($images)>0) { foreach($images as $image) {
									  		$firmware = str_replace(path(),'', $image);
									  		echo '<option value="'.$firmware.'">';
									    } } ?>
									  </datalist>
									</div>
									<div class="form-group column col-auto text-right" style="float: right;">
										<button type="button" class="btn btn-primary btn-block" style="margin-top: 24px; font-size: 32px;padding-top: 0" onclick="document.getElementById('select-firmware').style.display='none';document.getElementById('upload-firmware').style.display='block';"> + </button>
									</div>
								</div>

								<div class="columns" id="upload-firmware" style="display: none">
									<div class="column col-auto text-right" style="float:left">
										<button type="button" class="btn btn-block" style="margin-top:24px;" onclick="document.getElementById('select-firmware').style.display='block';document.getElementById('upload-firmware').style.display='none';"> ← </button>
									</div>
									<div class="form-group column col-11" style="float:left">
									  <label>Upload a firmware image:</label><br/>

									  <button type="button" class="btn btn-block" onclick="document.getElementById('browse-firmware').click()"> Browse a new Firmware </button>
									</div>
								</div>
							</div>

							<div class="divider" style="margin: 20px 0 0"></div>

							<div class="panel-footer">
								<button type="submit" class="btn btn-success btn-block btn-lg"> UPGRADE MODEM </button>
							</div>
						</div>
					</form>
				</div>
				<div class="column col-3"></div>
			</div>

			<form style="position: absolute;left: -9999px" name="upload" action="<?php echo domain(); ?>?page=upload" method="POST" enctype="multipart/form-data">
				<input type="file" name="new_firmware" id="browse-firmware" onchange="document.forms['upload'].submit()">
			</form>
		<?php } ?>

		<?php if($page=='upgrade-status') { delete('config'); ?> 

			<div class="columns">
				<div class="column col-6 col-mx-auto" style="margin:40px auto 15px;text-align: center;">
					<div class="panel">
						<div class="panel-header">
							<div class="panel-title"><h3 style="font-weight: 300;margin:0"><font style="float:left">ⓘ</font> Session based Upgrade Flow</h3></div>
						</div>
						<div class="divider"></div>
						<div class="panel-body">
							<div class="columns">
								<div class="column col-12 text-left">
									<h5>
										<button type="button" class="btn s-circle" style="width: 36px;margin-right: 10px">ⓘ</button>
										TFTP Server IP:
										<small><?php echo server(); ?></small>
									</h5>
									<h5>
										<button type="button" class="btn s-circle" style="width: 36px;margin-right: 10px">ⓘ</button>
										TFTP Path:
										<small><?php echo path(); ?></small>
									</h5>
									<h5>
										<button type="button" class="btn s-circle" style="width: 36px;margin-right: 10px">ⓘ</button>
										CMTS IP:
										<small><?php echo cmts(); ?></small>
									</h5>
									<h5>
										<button class="btn btn-success s-circle" style="width: 36px;margin-right: 10px">✓</button>
										Modem IP:
										<small><?php echo modem(); ?></small>
									</h5>
									<h5>
										<button class="btn btn-success s-circle" style="width: 36px;margin-right: 10px">✓</button>
										Firmware:
										<small><?php echo firmware(); ?></small>
									</h5>
								</div>
							</div>
						</div>
						<div class="divider" style="margin-bottom: 0"></div>
						<div class="panel-footer text-center text-italic">
							<cite> Status: Upgrade Initiated successfully, you can monitor your modem now. </cite>
						</div>
					</div>
				</div>
			</div>

			<div class="columns">
				<div class="column col-6 col-mx-auto">
					<a href="<?php echo domain(); ?>?page=upgrade-flow" class="btn btn-success btn-block" style="margin-top:50px;padding: 50px;font-size:24px;line-height: 5px;background: #fff;color: #32b643">
						Upgrade another modem →
					</a>
				</div>
			</div>

		<?php } ?>

	</div>

	<script> function defaults() { for (var i=0;i<document.getElementsByTagName("input").length; i++) { document.getElementsByTagName("input")[i].value = ''; } document.forms[0].submit(); } </script>
</body>
</html>