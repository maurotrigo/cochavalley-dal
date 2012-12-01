<!DOCTYPE html>
<html lang="es">
	<head>
		<?php echo $this->Html->charset(); ?>
		<title><?php echo __('CrimenMap').': '.$title_for_layout; ?></title>
		<?php
			echo $this->Html->meta('icon');
	
			echo $this->Html->css(array('bootstrap.min', 'aq'));
			echo $this->Html->script(array('https://www.google.com/jsapi', 'jquery.min', 'jquery-ui.min', 'bootstrap.min', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyB1EjUV_8Lmq6YkAQ04jwRttfGft94bXX0&sensor=true', 'map'));
		?>
	</head>
	<body>
		<div class="container-fluid navbar-wrapper">
			<?php echo $this->element('top_nav'); ?>
			<div id="content">
				<?php echo $this->Session->flash(); ?>
				<?php echo $this->fetch('content'); ?>
			</div>
			<footer>
			  <p>&copy; CochaValley 2012</p>
			</footer>
		</div>
		<?php echo $this->element('sql_dump'); ?>
		
	</body>
</html>