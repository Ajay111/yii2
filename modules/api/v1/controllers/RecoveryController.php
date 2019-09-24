<?php

namespace app\modules\api\v1\controllers;

use dektrium\user\Finder;
use dektrium\user\models\RecoveryForm;
use dektrium\user\models\Token;
use dektrium\user\traits\AjaxValidationTrait;
use dektrium\user\traits\EventTrait;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

use app\models\UserModel;
use dektrium\user\traits\ModuleTrait;
/**
 * Member controller for the `api` module
 */
class RecoveryController extends Controller {

	use AjaxValidationTrait;
	use EventTrait;

    private $response = [];
    private $post_json;
    private $data_json;
    public $app_id;
    public $imei_no;

    /*
     * \Yii::$app->controller->module
     */
    public $current_module;
	
 const EVENT_BEFORE_REQUEST = 'beforeRequest';
/**
     * Event is triggered before blocking existing user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_BEFORE_BLOCK = 'beforeBlock';

    /**
     * Event is triggered after blocking existing user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_AFTER_BLOCK = 'afterBlock';

    /**
     * Event is triggered before unblocking existing user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_BEFORE_UNBLOCK = 'beforeUnblock';

    /**
     * Event is triggered after unblocking existing user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_AFTER_UNBLOCK = 'afterUnblock';


    /**
     * Event is triggered after requesting password reset.
     * Triggered with \dektrium\user\events\FormEvent.
     */
    const EVENT_AFTER_REQUEST = 'afterRequest';

    /**
     * Event is triggered before validating recovery token.
     * Triggered with \dektrium\user\events\ResetPasswordEvent. May not have $form property set.
     */
    const EVENT_BEFORE_TOKEN_VALIDATE = 'beforeTokenValidate';

    /**
     * Event is triggered after validating recovery token.
     * Triggered with \dektrium\user\events\ResetPasswordEvent. May not have $form property set.
     */
    const EVENT_AFTER_TOKEN_VALIDATE = 'afterTokenValidate';

    /**
     * Event is triggered before resetting password.
     * Triggered with \dektrium\user\events\ResetPasswordEvent.
     */
    const EVENT_BEFORE_RESET = 'beforeReset';

    /**
     * Event is triggered after resetting password.
     * Triggered with \dektrium\user\events\ResetPasswordEvent.
     */
    const EVENT_AFTER_RESET = 'afterReset';

    /** @var Finder */
    protected $finder;

    /**
     * @param string           $id
     * @param \yii\base\Module $module
     * @param Finder           $finder
     * @param array            $config
     */
    public function __construct($id, $module, Finder $finder, $config = []) {
        $this->finder = $finder;
        parent::__construct($id, $module, $config);
    }

	
    public function beforeAction($event){
        $this->current_module = \Yii::$app->controller->module;
        $this->post_json = $this->current_module->post_json;
        $this->data_json = $this->current_module->data_json;
        $this->response['status'] = "1";
        $this->response['message'] = "Success";
         $this->response['data'] = "";
        return parent::beforeAction($event);
    }
	
	public function actionValidateemailotp(){
        if (isset($this->data_json['otp']) && $this->data_json['otp'] != ''){
			  $user= \dektrium\user\models\Token::find()->where(['otp_code'=>$this->data_json['otp']])->one();
			   if($user){
				   $expire=$this->getIsExpired($user->created_at);
				    if($expire){
						$userModel = \app\models\UserModel::find()->where(['id'=>$user->user_id])->one();
						if ($userModel){
							if (isset($this->data_json['password'])){
								if($this->data_json['name']){
									$name=$this->data_json['name'];
								}else{
									$name='';
								}
								if($this->data_json['phone_no']){
									$phone_no=$this->data_json['phone_no'];
								}else{
									$phone_no='';
								}
								$userModel->password=$this->data_json['password'];
								$userModel->name=$name;
								$userModel->phone_no=$phone_no;
							if ($userModel->save()) {
									$this->response['status'] = "1";
									 $this->response['user_detail'] = \app\models\UserModel::find()->where(['id'=>$user->user_id])->one();
									 return $this->response;
								 }else{
									 $this->response['status'] = "0";
									$this->response['message'] = "Bad Request, please provide a password";
								 }
							}else{
								$this->response['status'] = "0";
								$this->response['message'] = "Bad Request, please provide a password";
								}
						 }else{
								$this->response['status'] = "0";
								$this->response['message'] = "Not A Valid Users OTP!";
							}
							
						
					}else{
							$this->response['status'] = "0";
							$this->response['message'] = "OTP is expired!";
					}
			   }else{
				   $this->response['status'] = "0";
					$this->response['message'] = "OTP is Not Valid!";
			   }
		}else{
				$this->response['status'] = "0";
				$this->response['message'] = "OTP can't be empty";
		}
		return $this->response;
	}
	
	public function actionSendotptoemail() {

        /** @var RecoveryForm $model */
        $model = \Yii::createObject([
                    'class' => RecoveryForm::className(),
                    'scenario' => RecoveryForm::SCENARIO_REQUEST,
        ]);
        $event = $this->getFormEvent($model);
		$this->trigger(self::EVENT_BEFORE_REQUEST, $event);
		$model->email=$this->data_json['email'];
		$checkEmail =  \app\models\UserModel::find()->where(['email'=>$model->email])->one();
        if ($checkEmail) {
				$user = \Yii::createObject([
                    'class' => \app\models\UserModel::className(),
                    'scenario' => 'create',
					]);
				$user->role = \app\models\UserModel::ROLE_ORG_USER;
				$user->org_id = 3;
				$user->status = 1;
				$user->name=$this->data_json['email'];
				$user->username=$this->data_json['email'];
				$user->phone_no='';
				$user->password="password";
				$user->email=$this->data_json['email'];
				
				// if ($user->validate()) {
					// if($user->save()){
						$user = $this->finder->findUserByEmail($user->email);
						if (isset($user) and $user !== NULL) {
							if ($model->sendRecoveryMessageOtpToEmail()) {
								$this->trigger(self::EVENT_AFTER_REQUEST, $event);
								$this->response['status'] = "1";
								$this->response['message'] = "Success";
							 
							}
						} else{
							$this->response['status'] = "0";
							$this->response['message'] = "There is no user with such email.";
						   }
					// } else {
						
							// $this->response['status'] = "0";
							// $this->response['message'] = "Email is not a valid email address.";
					// }
			//	}
				//$this->response['status'] = "0";
				//$this->response['message'] = "You Have Already Registered Please Login.";
			
        }else{
				$user = \Yii::createObject([
                    'class' => \app\models\UserModel::className(),
                    'scenario' => 'create',
					]);
				$user->role = \app\models\UserModel::ROLE_ORG_USER;
				$user->org_id = 3;
				$user->status = 1;
				$user->name=$this->data_json['email'];
				$user->username=$this->data_json['email'];
				$user->phone_no='';
				$user->password="password";
				$user->email=$this->data_json['email'];
				
				 if ($user->validate()) {
					 if($user->save()){
						$user = $this->finder->findUserByEmail($user->email);
						if (isset($user) and $user !== NULL) {
							if ($model->sendRecoveryMessageOtpToEmail()) {
								$this->trigger(self::EVENT_AFTER_REQUEST, $event);
								$this->response['status'] = "1";
								$this->response['message'] = "Success";
							 
							}
						} else{
							$this->response['status'] = "0";
							$this->response['message'] = "There is no user with such email.";
						   }
					} else {
						
							$this->response['status'] = "0";
							$this->response['message'] = "Email is not a valid email address.";
					}
				}else{
							$this->response['status'] = "0";
							$this->response['message'] = "Not Valid Input.";
				}
					 
				 }
		return $this->response;
    }
	
	
    public function actionOtpverify(){
        if (isset($this->data_json['otp']) && $this->data_json['otp'] != ''){
             $user= \dektrium\user\models\Token::find()->where(['otp_code'=>$this->data_json['otp']])->one();
             if($user){
                    $expire=$this->getIsExpired($user->created_at);
                     if($expire){
                    $userModel = \app\models\UserModel::find()->where(['id'=>$user->user_id])->one();
                    if (isset($this->data_json['password'])){
                     $userModel->password=$this->data_json['password'];
                     if ($userModel->save()) {
                     $this->response['user_detail'] = \app\models\UserModel::find()->where(['id'=>$user->user_id])->one();
                     return $this->response;
                 }
                 }else{
                         throw new \yii\web\BadRequestHttpException("Bad Request, please provide a password"); 
                 }
        }
                     else{
                          throw new \yii\web\BadRequestHttpException("OTP is expired!"); // HTTP Code 400
                     }
             }else
             {
                 throw new \yii\web\BadRequestHttpException("OTP is expired "); // HTTP Code 400
             }
              }
        else{
             throw new \yii\web\BadRequestHttpException("OTP can't be empty "); // HTTP Code 400
        }
    }
     public function getIsExpired($created_at)
    {
        $expirationTime =1800;
        return ($created_at + $expirationTime) > time();
    }
    public function actionForgetpassword() {
	if (isset($this->data_json['email']) && $this->data_json['email'] != ''){
            /** @var RecoveryForm $model */
        $model = \Yii::createObject([
                    'class' => RecoveryForm::className(),
                    'scenario' => RecoveryForm::SCENARIO_REQUEST,
        ]);
        $event = $this->getFormEvent($model);
        $this->trigger(self::EVENT_BEFORE_REQUEST, $event);
        $model->email=$this->data_json['email'];
        $user = $this->finder->findUserByEmail($model->email);
         if (isset($user) and $user !== NULL) {
                  if ($model->sendRecoveryMessageOtp()) {
                        $this->trigger(self::EVENT_AFTER_REQUEST, $event);
                        $this->response['status'] = "1";
                        $this->response['message'] = "Success";
                        $this->response['data'] =  \app\models\UserModel::find()->where(['email'=>$model->email])->one();
                        return $this->response;
                    }
                } {
                      throw new \yii\web\BadRequestHttpException("user, There is no user with such email"); // HTTP Code 400
                    }
        }else
             throw new \yii\web\BadRequestHttpException("user, Please select a email Id"); // HTTP Code 400
	 }
         
public function actionBlock() {
    if (isset($this->data_json['user_id']) && $this->data_json['user_id'] != ''){
        $id=$this->data_json['user_id'];
         if ($id == \Yii::$app->user->getId()) {
             throw new \yii\web\BadRequestHttpException( 'You can not block your own account'); // HTTP Code 400
           
        } else {
            $user = $this->findModel($id);
            $event = $this->getUserEvent($user);
            if ($user->getIsBlocked()) {
                $this->trigger(self::EVENT_BEFORE_UNBLOCK, $event);
                $user->unblock();
                $this->trigger(self::EVENT_AFTER_UNBLOCK, $event);
                
                        $this->response['status'] = "1";
                        $this->response['message'] = "User has been unblocked";
                        $this->response['data'] =  \app\models\UserModel::find()->where(['id'=>$id])->one();
                        return $this->response;
             //   \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'User has been unblocked'));
            } else {
                $this->trigger(self::EVENT_BEFORE_BLOCK, $event);
                $user->block();
                $this->trigger(self::EVENT_AFTER_BLOCK, $event);
                        $this->response['status'] = "1";
                        $this->response['message'] = "User has been blocked";
                        $this->response['data'] =  \app\models\UserModel::find()->where(['id'=>$id])->one();
                        return $this->response;
              //  \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'User has been blocked'));
            }
        }
    }
}

 protected function findModel($id) {
        $user = UserModel::findOne($id);
        if ($user === null) {
            throw new NotFoundHttpException('The requested page does not exist');
        }

        return $user;
    }
}
