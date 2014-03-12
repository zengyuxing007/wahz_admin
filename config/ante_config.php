<?php
Config::set( 'config_all',
	array( 
		'task' => '任务', 
		'tank' => '战车'
	)
);

Config::set( 'config_reward', array(
    1 => "钢笔",
    2 => "IPAD",
    3 => "Iphone4S",
    4 => "MacBook",
    )
);


Config::set('mail_send_url', 'http://192.168.1.234:8080/SendMail');
Config::set('reward_send_url', 'http://192.168.1.234:8080/SendReward');
Config::set('kickout_player_url','http://192.168.1.234:8080/KickOutPlayer');
//post = "{\"uid\":$uid,\"freezing_until_time\":$time}";
Config::set('freeze_account_url','http://192.168.1.234:8080/FreezeAccount');
//post = "{\"uid\":$uid}"
Config::set('unseal_account_url','http://192.168.1.234:8080/UnsealAccount');



?>
