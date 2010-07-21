<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo (defined('TITLE') ? TITLE : 'Alkaline'); ?></title>
	<link rel="stylesheet" href="<?php echo BASE . CSS; ?>blueprint/screen.css" type="text/css" media="screen, projection" />
	<link rel="stylesheet" href="<?php echo BASE . CSS; ?>blueprint/print.css" type="text/css" media="print" />	
	<!--[if lt IE 8]><link rel="stylesheet" href="<?php echo BASE . CSS; ?>blueprint/ie.css" type="text/css" media="screen, projection" /><![endif]-->
	<link rel="stylesheet" href="<?php echo BASE . CSS; ?>alkaline.css" type="text/css" media="screen, projection" />
	<link rel="stylesheet" href="<?php echo BASE . CSS; ?>smoothness/jquery-ui-1.8.custom.css" type="text/css" media="screen, projection" />
	<script src="<?php echo BASE . JS; ?>jquery/jquery-1.4.2.min.js" type="text/javascript"></script>
	<script src="<?php echo BASE . JS; ?>jquery/jquery-ui-1.8.custom.min.js" type="text/javascript"></script>
	<script src="<?php echo BASE . JS; ?>jquery/jquery.flot.js" type="text/javascript"></script>
	 <!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo BASE . JS; ?>jquery/excanvas.min.js"></script><![endif]-->
	<script src="<?php echo BASE . JS; ?>alkaline.js" type="text/javascript"></script>
	<?php $alkaline->dejectJS(); ?>
</head>
<body>
	<div id="header" class="container">
		<a href="/admin/"><img src="/images/shutter.png" alt="Alkaline" /></a>
	</div>
	<hr />
	<?php
	if($user->perm()){
		?>
		<div id="navigation" class="container">
			<ul>
				<li><a href="/admin/dashboard/">Dashboard</a></li>
				<li><a href="/admin/library/">Library</a></li>
				<li><a href="/admin/shoebox/">Shoebox (6)</a></li>
				<li><a href="/admin/sections/">Sections</a>
					<ul>
						<li><a href="/admin/tags/">Tags</a></li>
						<li><a href="/admin/collections/">Collections</a></li>
						<li><a href="/admin/narratives/">Narratives</a></li>
						<li><a href="/admin/comments/">Comments</a></li>
					</ul>
				</li>
				<li><a href="/admin/settings/">Settings</a>
					<ul>
						<li><a href="/admin/users/">Users</a></li>
						<li><a href="/admin/preferences/">Preferences</a></li>
						<li><a href="/admin/configuration/">Configuration</a></li>
						<li><a href="/admin/themes/">Themes</a></li>
						<li><a href="/admin/extensions/">Extensions</a></li>
						<li><a href="/admin/maintenance/">Maintenance</a></li>
					</ul>
				</li>
				<li><a href="/admin/logout/">Logout</a></li>
			</ul>
		</div>
		<hr />
		<?php
	}
	if($alkaline->isNotification()){
		?>
		<div id="notification" class="container">
			<?php $alkaline->viewNotification(); ?>
		</div>
		<hr />
		<?php
	}
	?>