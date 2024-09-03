<?php 

return array(
	'maps'		=> array(
						'key'	=> (isset($DEVELOPER_SEVERS) && is_array($DEVELOPER_SEVERS) ?
										(array_search($_SERVER['SERVER_ADDR'],$DEVELOPER_SEVERS) !== false ? 'AIzaSyAZRD12Vvqfgh5hZfhAckkmSV5MQiqB9Dk' : '')
										: ''
									)
						,'link'	=> 'https://www.google.com/maps/place/Osteria+Ai+Pioppi/@45.843458,12.1788533,17z/data=!3m1!4b1!4m5!3m4!1s0x47793da259fc8ab5:0x1ecea621b5735036!8m2!3d45.843458!4d12.181042'
					)
	,'analytics'=> array(
						'key'		=> 'UA-141487189-1'
						,'domain'	=> 'aipioppi.com'
					)
	,'recaptcha'=>array(
						'key'		=> ''
						,'secret'	=> ''
					)
);
