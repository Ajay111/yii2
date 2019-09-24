<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'algn',
    'name' => 'ALGN',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'timeZone' => 'Asia/Calcutta',
    'modules' => [
        'administrator' => [
            'class' => 'app\modules\administrator\Module',
        ],
        'debug' => [
            'class' => 'yii\debug\Module',
        ],
        'v1' => [
            'class' => 'app\api\modules\v1\Module',
        ],
         'api' => [
            'class' => 'app\modules\api\Module',
        ],
       

        // 'admin' => [
        //         // you should consider using a shorter namespace here!
        //         'class' => 'app\modules\forum\modules\admin\Module',
        //     ],
        // 'api' => [
        //     'class' => 'app\modules\api',
        // ],
        'v1' => [
            'class' => 'app\modules\api\v1\module',
        ],
        'gridview' => [
            'class' => '\kartik\grid\Module'
        ],
        'user' => [
            'class' => 'dektrium\user\Module',
            'enableRegistration' => false,
            'enableUnconfirmedLogin' => true,
            'confirmWithin' => 21600,
            'cost' => 12,
            'admins' => ['admin'],
            'modelMap' => [
                'User' => 'app\models\UserModel',
            ],
            'mailer' => [
                'class' => 'app\components\Mailers',
                'viewPath' => '@app/views/member/mail',
                'sender' =>['no-reply@myhost.com' => 'Algn']
            ],
            
        ],
//        'user' => [
//            'class' => 'app\modules\user\Module',
//            'enableConfirmation' => FALSE,
//            'enableRegistration' => FALSE,
//            'cost' => 12,
//            'admins' => ['admin'],
//            'mailer' => [
//            //    'sender' => 'vikas@arthify.com', // or ['no-reply@myhost.com' => 'Sender name']
//            //    'welcomeSubject' => 'Welcome to NRB Alert System',
//            //    'confirmationSubject' => 'Confirmation subject',
//            //    'reconfirmationSubject' => 'Email change subject',
//            //    'recoverySubject' => 'Recovery Password - NRB Alert System',
//            ],
//        ],
//        'admin' => [
//            'class' => 'mdm\admin\Module',
//            'layout' => 'left-menu', // it can be '@path/to/your/layout'.
//            'controllerMap' => [
//                'assignment' => [
//                    'class' => 'mdm\admin\controllers\AssignmentController',
//                    'userClassName' => 'dektrium\user\models\User',
//                    'idField' => 'id'
//                ],
//            ],
//        ],
//        'audit' => [
//            'class' => 'bedezign\yii2\audit\Audit',
//            // the layout that should be applied for views within this module
//            'layout' => 'main',
//            // Name of the component to use for database access
//            'db' => 'db_audit',
//            // List of actions to track. '*' is allowed as the last character to use as wildcard
//            'trackActions' => ['*'],
//            // Actions to ignore. '*' is allowed as the last character to use as wildcard (eg 'debug/*')
//            'ignoreActions' => ['audit/*', 'debug/*'],
//            // Maximum age (in days) of the audit entries before they are truncated
//            'maxAge' => 'debug',
//            // IP address or list of IP addresses with access to the viewer, null for everyone (if the IP matches)
//            'accessIps' => ['127.0.0.1', '192.168.*'],
//            // Role or list of roles with access to the viewer, null for everyone (if the user matches)
//            'accessRoles' => ['admin'],
//            // User ID or list of user IDs with access to the viewer, null for everyone (if the role matches)
//            'accessUsers' => [1, 2],
//            // Compress extra data generated or just keep in text? For people who don't like binary data in the DB
//            'compressData' => true,
//            // The callback to use to convert a user id into an identifier (username, email, ...). Can also be html.
//            //'userIdentifierCallback' => ['app\models\User', 'userIdentifierCallback'],
//            // If the value is a simple string, it is the identifier of an internal to activate (with default settings)
//            // If the entry is a '<key>' => '<string>|<array>' it is a new panel. It can optionally override a core panel or add a new one.
//            'panels' => [
//                'audit/request',
//                'audit/error',
//                'audit/trail',
//                'audit/log',
//                'audit/db',
//                'audit/profiling',
//                'audit/javascript',
//                'audit/mail',
//                'audit/asset',
//                'audit/config',
//                'audit/error',
//                'audit/extra', // Links the data functions (`data()`)
//                'audit/curl', // Links the curl tracking function (`curlBegin()`, `curlEnd()` and `curlExec()`)
////                'app/views' => [
////                   // 'class' => 'app\panels\ViewsPanel',
////                ],
//            ],
//            'panelsMerge' => [
//                'audit/config' => [],
//                'audit/curl' => ['log' => false],
//            ]
//        ],
    ],
    'components' => [
//        'request' => [
//        'parsers' => [
//          'application/json' => 'yii\web\JsonParser',
//        ],
//      ],
//        'urlManager' => [
//        'enablePrettyUrl' => true,
//        'enableStrictParsing' => true,
//        'showScriptName' => false,
//        'rules' => [
//          ['class' => 'yii\rest\UrlRule', 'controller' => 'item'],
//          ['class' => 'yii\rest\UrlRule', 'controller' => 'user'],
//        ],],

//        'mailer' => [
//            'class' => 'yii\swiftmailer\Mailer',
//            'useFileTransport' => false,
//            'transport' => [
//                'class' => 'Swift_SmtpTransport',
//                'host' => 'smtp.gmail.com',
//                'username' => 'vikas.k.c@gmail.com',
//                'password' => 'lfmighktsatfxpny',
//                'port' => '465',
//                'encryption' => 'ssl',
//            ],
//        ],
        'postmark' => [
            'class' => 'yii\swiftmailer\Mailer',
            'useFileTransport' => false,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.postmarkapp.com',
                'username' => '4566cfe3-7f67-459e-aa19-786984bb6a4e',
                'password' => '4566cfe3-7f67-459e-aa19-786984bb6a4e',
                'port' => '587',
                'encryption' => 'tls',
                  'streamOptions' => [ 
            'ssl' => [ 
                'allow_self_signed' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]
            ],
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'useFileTransport' => false,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.postmarkapp.com',
                'username' => '4566cfe3-7f67-459e-aa19-786984bb6a4e',
                'password' => '4566cfe3-7f67-459e-aa19-786984bb6a4e',
                'port' => '587',
                'encryption' => 'tls',
                  'streamOptions' => [ 
            'ssl' => [ 
                'allow_self_signed' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]
            ],
        ],
        
//        'mailernrb' => [
//            'class' => 'yii\swiftmailer\Mailer',
//            'useFileTransport' => false,
//            'transport' => [
//                'class' => 'Swift_SmtpTransport',
//                'host' => 'smtp.office365.com',
//                'username' => 'Alert@nrb.co.in',
//                'password' => 'Dkfjgjnk@2017',
//                'port' => '587',
//                'encryption' => 'tls',
//            ],
//        ],
//        'mail' => [
//            'class' => 'yashop\ses\Mailer',
//            'access_key' => 'AKIAJ2IHQAEE6HWXSHSA',
//            'secret_key' => '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',
//            'host' => 'email.eu-west-1.amazonaws.com' // not required
//        ],

        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => '2XY-ShY_HlFtZ8GHONWKuvpQebzI9xPV',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'authManager' => [
            'class' => '\yii\rbac\DbManager',
            'ruleTable' => 'AuthRule', // Optional
            'itemTable' => 'AuthItem', // Optional
            'itemChildTable' => 'AuthItemChild', // Optional
            'assignmentTable' => 'AuthAssignment', // Optional
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'view' => [
            'theme' => [
                'pathMap' => ['@app/views' => '@app/themes/algn/views'],
                'baseUrl' => '@web/themes/algn',
            ],
        ],
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '/' => 'site/index',
            //        'login' => 'user/security/login',
            //        'logout' => 'user/security/logout',
                    'forgot' => 'recovery/request',
            'user/recover/<id:\d+>/<code:[A-Za-z0-9_-]+>' => 'recovery/reset',
            //'changepassword' => 'member/changepassword',
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'],
                    'categories' => ['sns'],
                    'logFile' => '@app/runtime/logs/sns/requests.log',
                ],
            ],
        ],
        'formatter' => [
            'class' => 'yii\i18n\Formatter',
            'dateFormat' => 'php:j M Y',
            'datetimeFormat' => 'php:j M Y H:i',
            'timeFormat' => 'php:H:i',
            'timeZone' => 'UTC',
        ],
        'db' => ['class' => 'yii\db\Connection',
            //'dsn' => 'mysql:host=localhost;dbname=algn_prod_06042018',
			'dsn' => 'mysql:host=localhost;dbname=algn_latest_with_chat',
             'username' => 'root',
            'password' => '',
            'charset' => 'utf8',],
    ],
    'params' => $params,
//    'as access' => [
//        'class' => 'mdm\admin\components\AccessControl',
//        'allowActions' => [
//            'site/*',
//             'sitee/*',
//            'user/*',
//            'gii/*',
//            'debug/*',
//        ]
//    ]
];

//if (YII_ENV_DEV){ //Vikas - this variable is not available here. no need to check this.
// configuration adjustments for 'dev' environment
$config['bootstrap'][] = 'debug';
$config['modules']['debug'] = [
    'class' => 'yii\debug\Module',
];

$config['bootstrap'][] = 'gii';
$config['modules']['gii'] = [
    'class' => 'yii\gii\Module',
];
//}

return $config;
