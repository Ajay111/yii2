<?php

namespace app\modules\api;

/**
 * api module definition class
 */
class Module extends \yii\base\Module {

    /**
     * @inheritdoc
     */
    //public $controllerNamespace = 'app\modules\api\controllers';
     public $controllerNamespace = 'app\modules\api\v1\controllers';

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();
        //\Yii::$app->request->enableCsrfValidation = false;
        //\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        //$headers = \Yii::$app->response->headers;
	//$headers->add('Access-Control-Allow-Origin', '*');
        
        $this->modules = [
            'v1' => [
                'class' => 'app\modules\api\v1\Module',
            ],
        ];
    }

}
