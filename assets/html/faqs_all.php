<?php
	  # FAQS
	  if($App->faqs['faqs']): ?>

<section id="faqs_section" class="center_width" data-animscroll data-as-delay="0" data-as-animation="fadeIn">
	<h3 class="format_title mode3"><?php $App->Lang->echoT('faqs_title'); ?></h3>
	<?php 

		$c = 0;
		echo '		<div class="faqs_btns">'.PHP_EOL;
		foreach($App->faqs['cats'] as $C):
			if(isset($App->faqs['faqs'][$C['id']]) && count($App->faqs['faqs'][$C['id']]) > 0):
				echo ' 		<a class="btn red faq_cat_btn'.($c++ <= 0 ? ' active' : '').'" href="#faqs_'.$C['id'].'">'.$C['title'].'</a>'.PHP_EOL;
			endif;
		endforeach;
		echo '		</div>'.PHP_EOL;

		foreach($App->faqs['faqs'] as $id_cat => $Faqs):
			echo '		<ul id="faqs_'.$id_cat.'" class="reset faqs_collection">'.PHP_EOL;
			foreach($Faqs as $F):
				echo '		<li>
								<button type="button" class="openclose_btn">
									'.$App->Lang->returnT('aria_label_click_toggle',array('object' => '')).'
								</button>
								<h5 class="question">'.$F['title'].'</h5>
								<p class="answer">'.$F['descr'].'</p>
							</li>'.PHP_EOL;
			endforeach;
			echo '		</ul>'.PHP_EOL;
		endforeach;
	?>
</section>

<?php endif; ?>
