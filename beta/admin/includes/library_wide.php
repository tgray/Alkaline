<div id="primary" class="column">
	<a href="<?php echo BASE . ADMIN; ?>dashboard/"><img src="/images/alkaline.png" alt="Alkaline" class="bumper" /></a><br /><br />
	<ul id="navigation">
		<li><a href="<?php echo BASE . ADMIN; ?>dashboard/">Dashboard</a><img src="/images/pointer.png" alt="" class="hide" /></li>
		<li><a href="<?php echo BASE . ADMIN; ?>library/">Library</a><img src="/images/pointer.png" alt="" /></li>
		<li><a href="<?php echo BASE . ADMIN; ?>features/">Features</a><img src="/images/pointer.png" alt="" class="hide" /></li>
		<li><a href="<?php echo BASE . ADMIN; ?>settings/">Settings</a><img src="/images/pointer.png" alt="" class="hide" /></li>
		<li><a href="http://www.alkalineapp.com/help/" target="_blank">Help</a><img src="/images/block_cyan.png" alt="" class="hide" /></li>
		<li class="logout"><a href="">Logout</a><img src="/images/block_red.png" alt="" /></li>
	</ul>
</div>
<div id="tertiary_wide" class="column">
	<img src="/images/empty/64.png" alt="" class="bumper" /><br /><br />
	
	<?php $alkaline->viewNotification(); ?>