<?php

return (array_replace_recursive(
                require(dirname(__FILE__) . '/config.php'), array(
            'components' => [
                'db' => [
                    'dsn' => 'mysql:host=localhost;dbname=algn_prod',
                    'username' => 'root',
                    'password' => 'tli*5MAY',
                ],
            ],
            'params' => [
                'base_url' => 'http://algn.in/',
            ],
//            'params' => [
//                'adminEmail' => 'rahman.kld@gmail.com',
//                'baseurl' => 'http://demo3.tlitech.net',
//                'tmp' => '/tmp/',
//                'sms_lane_enable' => FALSE,
//                'amazon_mail_enable' => FALSE,
//                'amazon_mail_from' => 'NRB Alert <alert@nrbbearings.co.in>',
//                'amazon_retun_path' => 'alert@nrbbearings.co.in',
//                'amazon_mail_subject_crtical_item' => 'NRB Critical Item Alert',
//                'ses_access_key' => 'AKIAJ2IHQAEE6HWXSHSA',
//                'ses_secret_key' => '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',
//                'technical_team_email' => ['rahman.kld@gmail.com', 'vikas.k.c@gmail.com'],
//                'complaint_image_path'=>"/home/tlitech/ccacomplaint/",
//            ],
                )
        ));
?>