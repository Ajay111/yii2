<?php

namespace app\modules\api\v1;

use yii\web\Response;
use Yii;
use yii\db\Expression;
use yii\base\ActionEvent;
use yii\base\Application;
use app\models\ApiLog;
use app\models\AppDetail;

/**
 * api module definition class
 */
class Module extends \yii\base\Module {

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\api\v1\controllers';

    /**
     * @var \app\models\ApiLog 
     */
    public $model_apilog;

    /**
     * @var JSON Ojbect of PHP input 
     */
    public $post_json;

    /**
     * @var JSON Ojbect of PHP input 
     */
    public $data_json;

    /**
     * @var JSON Ojbect of PHP input 
     */
    public $app_id;

    /**
     * @var JSON Ojbect of PHP input 
     */
    public $user_id;

    /**
     * @var JSON Ojbect of PHP input 
     */
    public $org_id;

    /**
     * @var PHP input Ojbect 
     */
    public $php_input;

    /*
     * Module Base URL
     */
    public $base_url = "/api/v1/";

    /**
     *
     * @var type 
     */
    public $api_urls = [
        'api/v1/user/login',        //Done
        'api/v1/user/weblogin',        //Done
         'api/v1/user/registration',        //Done
        
        'api/v1/user/refresh',        //Done
        'api/v1/user/userdetails', 
        'api/v1/user/userlist', 
        'api/v1/group/groupdetails', //Done
        'api/v1/user/ping',        //Done
        'api/v1/user/list',         //Done
        'api/v1/user/updategoogletoken',         //Done
        'api/v1/user/updatepassword',         //Done
        'api/v1/user/wbslist',      //Done
        'api/v1/user/listallwbs',     //Done
        'api/v1/user/meetinglist',      //No Need          //list with a parameter to tell weather the detail is full or partial.
        'api/v1/user/savewbs',      //Done
        'api/v1/user/wbsupdate',      //Done
        'api/v1/user/meetingavailability',  //No Need 
        'api/v1/user/meetingsnooze',        //No Need 
        'api/v1/user/actioncomplete',       //No Need 
        'api/v1/user/contactsync',       //done
        'api/v1/user/updateprofile',       //done
        'api/v1/user/changepassword',       //done
        'api/v1/user/wbsdetails',       //done
        'api/v1/user/adduser',       //done
        'api/v1/user/userregistration',       //done
        'api/v1/user/deleteuser', 
		'api/v1/user/userlistadmin', 
        'api/v1/user/appid', 
		'api/v1/user/appversion', 
		 
        'api/v1/meeting/list',          //No Need
        'api/v1/meeting/listall',         // Done
        'api/v1/meeting/detail',    //Done            // for each indivual IDs
        'api/v1/meeting/save',      //Done      // for add   
        'api/v1/meeting/update',      //Done      // for add   
        'api/v1/meeting/cancel',            // for cancel
        'api/v1/meeting/snooze',            // for snooze
        'api/v1/meeting/order',             // for order
        'api/v1/meeting/complaint',         // for complaint
        'api/v1/meeting/acceptdecline',     // foracceptdecline
        'api/v1/meeting/searchmeeting', 
        'api/v1/meeting/searchmeetingall', 
        'api/v1/meeting/exitfrommeeting',
        
        'api/v1/action/list',           //done
        'api/v1/action/listall',           //done
        'api/v1/action/detail',     //Done             // for each indivual IDs
        'api/v1/action/save',       //Done
        'api/v1/action/update',       //Done
        'api/v1/action/username',       //Done
         'api/v1/action/readstatus',       //Done
        'api/v1/calendar/save',       //Done
        'api/v1/calendar/update',      //Done
        'api/v1/calendar/list',      //Done
        'api/v1/calendar/detail',      //Done
        
        'api/v1/wbs/savewbsaction',      //Done
        'api/v1/wbs/searchwbs',
         'api/v1/user/wbsdetail',
        'api/v1/group/create',      //Done
        'api/v1/group/view',        //Done
        'api/v1/group/update',      //Done
        'api/v1/group/delete',      //Done
        'api/v1/group/listall',     //Done
		'api/v1/group/exitgroup',     //Done
        'api/v1/action/create',     //Done
        'api/v1/action/searchaction',     //Done
		'api/v1/action/searchactionpoint',     //Done
        
        'api/v1/wbs/create',     //Done
        'api/v1/wbs/update',     //Done
        'api/v1/wbs/delete',     //Done
        'api/v1/wbs/listall',     //Done
        'api/v1/wbs/view',     //Done
        'api/v1/meeting/reminderall',
        'api/v1/meeting/reminder',
        'api/v1/broadcast/create',
        'api/v1/broadcast/listall',
        'api/v1/graph/index',
        'api/v1/graph/org',
        'api/v1/recovery/request',
        'api/v1/recovery/forgetpassword',
        'api/v1/recovery/otpverify',
        'api/v1/recovery/block', 
		'api/v1/recovery/sendotptoemail', 
		'api/v1/recovery/validateemailotp', 
		'api/v1/recovery/validateemailotp',
		'api/v1/user/signupusingemailotp',
		
		 
        
    ];

    /**
     *
     * @var type 
     */
    public $login_url = 'api/v1/user/login';
    public $registration_url = 'api/v1/user/registration';
    public $forgetpassword_url = 'api/v1/recovery/forgetpassword';
    public $otpverify_url = 'api/v1/recovery/otpverify';
	public $sendotptoemail = 'api/v1/recovery/sendotptoemail';
	public $validateemailotp = 'api/v1/recovery/validateemailotp';
	public $signupusingemailotp = 'api/v1/user/signupusingemailotp';
	public $userregistration_url = 'api/v1/user/userregistration';
	

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();
        $app = Yii::$app;
        $app->on(Application::EVENT_BEFORE_ACTION, [$this, 'onBeforeAction']);

        $app->request->enableCsrfValidation = false;

        $app->response->on(Response::EVENT_BEFORE_SEND, [$this, 'onBeforeSend']);
        $app->response->format = \yii\web\Response::FORMAT_JSON;
        $app->response->headers->add('Access-Control-Allow-Origin', '*');
    }

    public function onBeforeAction($event) {
        $this->php_input = file_get_contents("php://input");
        $this->post_json = json_decode(base64_decode($this->php_input), true);
         $this->post_json = json_decode($this->php_input, true);
        $this->post_json = json_decode($this->php_input, true);
        $this->data_json = $this->post_json['data'];
        $this->saveApiInfo()->verifyandLogin();
    }

    public function onBeforeSend($event) {
       $response = $event->sender;

        if ($response->statusCode == 200) {
            // $response->statusText = base64_encode($response->statusText);
        } else if ($response->statusCode == 400 || $response->statusCode == 401 || $response->statusCode == 403 || $response->statusCode == 404 || $response->statusCode == 409) {
            // this is required for not found (404) case. Because "onBeforeAction" method is not triggered.
            if (!isset($this->model_apilog)) {
                $this->saveApiInfo();
            }
            
            $this->model_apilog->response = $response->data['message'];

            $response->statusText = $response->data['message'];
            $response->data = null;
        } else if ($response->statusCode == 500) {
            // this is required for some cases. Because "onBeforeAction" method is not triggered.
            if (!isset($this->model_apilog)) {
                $this->saveApiInfo();
            }
            $exception = Yii::$app->errorHandler->exception;
            $response->statusText = $exception->getMessage() . " " . (isset($this->model_apilog->app_id) ? $this->model_apilog->app_id : 0 ) . " " . file_get_contents("php://input");
            $this->model_apilog->response = $exception->getMessage() . " File : " . $exception->getFile() . " Line : " . $exception->getFile() . "Trace : " . $exception->getTraceAsString();
            $response->data = null;
        }
        $this->model_apilog->http_response_code = $response->statusCode;
        $this->model_apilog->save(FALSE);
    }

    private function saveApiInfo() {
        $this->model_apilog = new ApiLog();

        $app = Yii::$app;

        $this->model_apilog->app_id = isset($this->post_json['app_id']) ? (int) $this->post_json['app_id'] : 0;
        $this->model_apilog->version_no = isset($this->post_json['version_no']) ? $this->post_json['version_no'] : '';
        $this->model_apilog->imei_no = isset($this->post_json['imei_no']) ? $this->post_json['imei_no'] : '';
        $this->model_apilog->ip = $app->getRequest()->getUserIP();
        $this->model_apilog->time = new Expression('NOW()');
        $this->model_apilog->request_url = $app->request->pathInfo;
        $this->model_apilog->request_body = $this->php_input;
        $this->model_apilog->http_response_code = 0;
        $this->model_apilog->api_response_status = 0;
        //$this->model_apilog->request_body = base64_decode($this->php_input);
        //$this->model_apilog->response=$this->php_input;

        if ($this->model_apilog->save(FALSE)) {
            
        } else {
            throw new \yii\web\ServerErrorHttpException('Api info log save error. ' . json_encode($this->model_apilog->getErrors()));
        }
        return $this;
    }

    private function verifyandLogin() {

        // STEP 1 checking the url requested is part of current API or not
        // Ideally this check is not required, 
        if (!in_array($this->model_apilog->request_url, $this->api_urls)) {
            throw new \yii\web\NotFoundHttpException("Request URL not Found"); //error 404
        }

        // STEP 2 checking whether the app_id and imei_no exists or not.
        if ($this->model_apilog->request_url == $this->login_url) {// at the time of login app_id will be missing
            if ($this->model_apilog->imei_no == "") {
                throw new \yii\web\BadRequestHttpException("Bad Request, imei_no missing"); //error 400
            }
        } 
        else if ($this->model_apilog->request_url == $this->registration_url) {// at the time of login app_id will be missing
            if ($this->model_apilog->imei_no == "") {
                throw new \yii\web\BadRequestHttpException("Bad Request, imei_no missing"); //error 400
            }
        }
        else if ($this->model_apilog->request_url == $this->forgetpassword_url) {// at the time of login app_id will be missing
            if ($this->model_apilog->imei_no == "") {
                throw new \yii\web\BadRequestHttpException("Bad Request, imei_no missing"); //error 400
            }
        }
         else if ($this->model_apilog->request_url == $this->otpverify_url) {// at the time of login app_id will be missing
            if ($this->model_apilog->imei_no == "") {
                throw new \yii\web\BadRequestHttpException("Bad Request, imei_no missing"); //error 400
            }
        }
		  else if ($this->model_apilog->request_url == $this->sendotptoemail) {// at the time of login app_id will be missing
            if ($this->model_apilog->imei_no == "") {
                throw new \yii\web\BadRequestHttpException("Bad Request, imei_no missing"); //error 400
            }
        }
		  else if ($this->model_apilog->request_url == $this->validateemailotp) {// at the time of login app_id will be missing
            if ($this->model_apilog->imei_no == "") {
                throw new \yii\web\BadRequestHttpException("Bad Request, imei_no missing"); //error 400
            }
        }
		else if ($this->model_apilog->request_url == $this->signupusingemailotp) {// at the time of login app_id will be missing
            if ($this->model_apilog->imei_no == "") {
                throw new \yii\web\BadRequestHttpException("Bad Request, imei_no missing"); //error 400
            }
        }
		
        else if ($this->model_apilog->request_url == $this->userregistration_url) {// at the time of login app_id will be missing
            if ($this->model_apilog->imei_no == "") {
                throw new \yii\web\BadRequestHttpException("Bad Request, imei_no missing"); //error 400
            }
        }
        else {
            if ($this->model_apilog->app_id == 0 || $this->model_apilog->imei_no == "") {
                throw new \yii\web\BadRequestHttpException("Bad Request, imei_no or app id missing"); //error 400
            }

            // STEP 3 checking the request made from the app is active or not.
            $active_app = AppDetail::find()->where(['id' => $this->model_apilog->app_id, 'status' => 1])->one();
            if (empty($active_app)) {
                throw new \yii\web\ConflictHttpException("App is not active"); //error 409
            }

            //Todo
            //STEP4 check wheather user is still active or not and asigned the app.
            //throw new \yii\web\ForbiddenHttpException(""); //error 403
            $user = \app\models\UserModel::findOne(['id' => $active_app->user_id]);
            if (\Yii::$app->getUser()->login($user, 10)) {
                //login successful;
            } else {
                throw new \yii\web\ForbiddenHttpException("Forbidden - User unable to login."); //error 403
                //unable to login
            }
        }

        //STEP check for active app
    }

}

