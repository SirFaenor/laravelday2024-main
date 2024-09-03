<p class="reset social_links" data-animscroll data-as-delay="300ms" data-as-animation="fadeIn">
<?php 
	foreach($App->Config['social_data'] as $name => $data):
		echo '		<a class="'.$name.'" href="'.$data['link'].'" target="_blank"><img src="/imgs/layout/icons/'.$name.'.svg" alt="'.ucfirst($name).'"></a>'.PHP_EOL;
	endforeach;
?>
</p>