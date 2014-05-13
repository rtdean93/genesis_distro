<?php
	$responsive = $node->field_responsive['und'][0]['value'];
?>
<div id="wrap">
	<div id="header">
		<div class="wrap">
		<?php if ($logo): ?>
			<a href="<?php print $front_page; ?>" title="<?php print t('Home'); ?>" rel="home" id="logo"> <img src="<?php print $logo; ?>" alt="<?php print t('Home'); ?>" /> </a> 
		<?php endif;  
      ?>
			<header>
				<ul id="screen-options">
					<li id="desktop" class="active">Desktop</li>
					<?php
						if ($responsive ==1){  ?>
					<li id="ipad">iPad</li>
					<li id="iphone">iPhone</li>
					<?php
					};
					?>
				</ul>
				<div>
					<a class="colorbox-inline button-try" href="?width=550&amp;height=600&amp;inline=true#formsdivcontainer" rel="sales@reachlocal.com">Try ReachEdge&trade;</a> 
				</div>
			</header>
		</div>

<!-- /.wrap -->
	</div>
<!-- /#header -->
	<div id="main">
		<div class="wrap">
			<div id="content">
<?php print $messages; ?>
				<a id="main-content"></a> 
<?php print render($title_prefix); ?>
<?php if ($tabs): ?>
				<div class="tabs">
<?php print render($tabs); ?>
				</div>
<?php endif; ?>
<?php print render($page['help']); ?>
<?php if ($action_links): ?>
				<ul class="action-links">
<?php print render($action_links); ?>
				</ul>
<?php endif; ?>
<?php print render($page['content']); ?>
<!--   <?php print $feed_icons; ?> -->
			</div>
<!-- /#content -->
<?php print render($page['content_bottom']); ?>
		</div>
<!-- /.wrap -->
	</div>
<!-- /#main -->
</div>
<!-- /#wrap -->
