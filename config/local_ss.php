<?php

$custome_array = (array_replace_recursive(
                require(dirname(__FILE__) . '/config.php'), array(
            'components' => [
                'db' => [
                    'dsn' => 'mysql:host=localhost;dbname=tri_algn',
                    'username' => 'root',
                    'password' => 'password',
                ],
            ],
            'params' => [
                'adminEmail' => 'rahman.kld@gmail.com',
                'baseurl' => 'http://align.ss/',
                'base_url' => 'http://align.ss',
                'tmp' => '/tmp/',
                'lane_enable' => FALSE,
                'mail_enable' => FALSE,
                'technical_team_email' => ['rahman.kld@gmail.com', 'vikas.k.c@gmail.com'],
                
            ],
                )
        ));

unset($custome_array['modules']['audit']);
return $custome_array;
?>