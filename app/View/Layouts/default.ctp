<!DOCTYPE html>
<html lang="es">
	<head>
		<?php echo $this->Html->charset(); ?>
		<title><?php echo __('SeguriMapas').': '.$title_for_layout; ?></title>
		<?php
			echo $this->Html->meta('icon');
	
			echo $this->Html->css(array('bootstrap.min', 'aq','datepicker'));
			echo $this->Html->script(array('https://www.google.com/jsapi', 'jquery.min', 'jquery-ui.min', 'bootstrap.min', 'bootstrap-datepicker', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyB1EjUV_8Lmq6YkAQ04jwRttfGft94bXX0&sensor=true&libraries=places,drawing', 'map'));
		?>
	</head>
	<body>
		<header id="top">
			<?php echo $this->Html->link($this->Html->image('segurimapas_logo.png', array('alt' => __('SeguriMapas'))), '/', array('id' => 'logo', 'escape' => false)); ?>
			
			<button class="btn btn-large btn-success" id="add_incidente" type="button">Agregar incidente</button>
		</header>
		<div class="container-fluid">
			<?php //echo $this->element('top_nav'); ?>
			<div id="content">
				<?php echo $this->Session->flash(); ?>
				<?php echo $this->fetch('content'); ?>
			</div>
		</div>
		<footer>
		  <a href="http://cochavalley.com/" target="_new" id="brand">&copy; CochaValley 2012</a>
		</footer>
		<?php echo $this->element('sql_dump'); ?>
		
	</body>
</html>