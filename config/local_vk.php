<?php

$custome_array = (array_replace_recursive(
                require(dirname(__FILE__) . '/config.php'), array(
            'components' => [
                'db' => [
                    'dsn' => 'mysql:host=localhost;dbname=algn-tli',
                    'username' => 'root',
                    'password' => 'password',
                ],
            ],
            'params' => [
                'base_url' => 'http://algntriline.tlitech.net/',
            ],
//            'params' => [
//                'adminEmail' => 'rahman.kld@gmail.com',
//                'baseurl' => 'http://playground.vk',
//                'tmp' => '/tmp/',
//                'sms_lane_enable' => FALSE,
//                'amazon_mail_enable' => FALSE,
//                'amazon_mail_from' => 'vikas@triline.in',
//                'amazon_retun_path' => 'vikas@triline.in',
//                'amazon_mail_subject_crtical_item' => 'NRB Critical Item Alert',
//                'ses_access_key' => 'AKIAJ2IHQAEE6HWXSHSA',
//                'ses_secret_key' => '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',
//                'technical_team_email' => ['rahman.kld@gmail.com', 'vikas.k.c@gmail.com'],
//            ],
                )
        ));

unset($custome_array['modules']['audit']);
return $custome_array;
?>