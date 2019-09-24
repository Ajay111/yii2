<?php

namespace app\modules\api\v1\controllers;

use app\modules\user\Finder;
use app\modules\user\models\Account;
use app\models\LoginForm;
use yii\web\Controller;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use app\models\ActionHistory;
use app\models\WbsUser;
use app\models\Wbs;
use app\models\AppDetail;
use yii\helpers\Json;
use Yii;
use app\modules\api\v1\models\UserGroup;
use app\modules\api\v1\models\User;

use app\models\UserRegistration;

use dektrium\user\traits\EventTrait;
use dektrium\user\Module;

use app\models\form\ChangePasswordForm;

/**
 * Member controller for the `api` module
 */
class UserController extends Controller {

    protected $finder;
//public $response = [];

    private $response = [];
    private $post_json;
    private $data_json;
    public $app_id;
    public $imei_no;
    
    use EventTrait;
    const EVENT_BEFORE_CREATE = 'beforeCreate';
    const EVENT_AFTER_CREATE = 'afterCreate';
    const EVENT_BEFORE_UPDATE = 'beforeUpdate';
    const EVENT_AFTER_UPDATE = 'afterUpdate';
    /*
     * \Yii::$app->controller->module
     */
    public $current_module;

    public function beforeAction($event) {
        $this->current_module = \Yii::$app->controller->module;
        $this->post_json = $this->current_module->post_json;
        $this->data_json = $this->current_module->data_json;
        $this->response['status'] = "1";
        $this->response['message'] = "Success";
        $this->response['data'] = "";
        return parent::beforeAction($event);
    }
   
    public function actionPing($from="") {
        $notification_grp = \app\models\Notification::find()->where('acknowledge_status = 0 and user_id="' . \Yii::$app->user->identity->id . '"')->groupBy(['notification_type', 'detail_id'])->limit(30)->all();

        foreach ($notification_grp as $notification_grp_main) {
            $notitifcation_grp1 = \app\models\Notification::find()->where('acknowledge_status = 0 and user_id="' . \Yii::$app->user->identity->id . '" and notification_type="' . $notification_grp_main->notification_type . '" and detail_id="' . $notification_grp_main->detail_id . '"')->orderBy('id asc')->all();

            foreach ($notitifcation_grp1 as $notification) {
                try {
                    $firbase_tocken = $notification->user->activeapp != NULL ? $notification->user->activeapp->firebase_token : '';
                    if ($firbase_tocken != "") {
                        $firebase = new \app\components\GoogleFirebase($notification, '1', '1');

                        $response = $firebase->send($firbase_tocken);
                        $response_result = json_decode($response);
                    } else {
                        $response_result = null;
                    }

                    $notification->cron_status = '1';
                    $notification->send_count = ($notification->send_count + 1);
                    $notification_model_detail = new \app\models\NotificationFirebaseDetail();
                    $notification_model_detail->notification_id = $notification->id;

                    if ($response_result == null) {
                        $notification->status = 0;
                        $notification_model_detail->firebase_message = "No Token";
                    } else {
                        if ($response_result->success) {
                            $notification->status = 1;
                            $notification_model_detail->firebase_id = isset($response_result->results[0]->message_id) ? $response_result->results[0]->message_id : '';
                        } else {
                            $notification->status = 0;
                            $notification_model_detail->firebase_message = isset($response_result->results[0]->error) ? $response_result->results[0]->error : '';
                        }
                    }

                    if ($notification->update()) {
                        
                    } else {
                        print_r($notification->getErrors());
                    }

                    if ($notification_model_detail->save()) {
                        
                    } else {
                        print_r($notification_model_detail->getErrors());
                    }

                    //return TRUE;
                } catch (\Exception $e) {
                    \app\components\Techteammail::send($e->getMessage(), ' Send Notification To Member');
                }
            }
        }
        return $this->response;
    }

    public function actionLogin() {
          if (isset($this->data_json['phone_no']) && $this->data_json['phone_no'] != ''){
                $phone_no=$this->data_json['phone_no'];
                $substr_phone_no = substr($phone_no, 0);
                $user_model = \app\modules\api\v1\models\User::find()->where(['phone_no'=>$substr_phone_no])->one();
                if($user_model){
                       if(empty($this->data_json['otp'])){
                           $otp=mt_rand(100000,999999);
						$phone_no='+91'.$phone_no;
                        $this->sendOtpMessage($phone_no,$otp,$type=2);
                        \app\models\TempUserOtp::updateAll(['status' => 0], "mobile_no =" . $substr_phone_no);
                            $temp_user_otp = new \app\models\TempUserOtp();
                            $temp_user_otp->otp=$otp;
                            $temp_user_otp->mobile_no=$substr_phone_no;
                            $temp_user_otp->status=1;
                            if($temp_user_otp->save()){
                                $this->response['status'] = "1";
                                $this->response['message'] = "OTP generated.";
                                $this->response['otp'] = $otp;
                                $this->response['mobile_no'] = $this->data_json['phone_no'];
                                return $this->response;
                            }
                } if (isset($this->data_json['otp']) && $this->data_json['otp'] != '') {
                        $temp_user_otp= \app\models\TempUserOtp::find()->where(['mobile_no'=>$substr_phone_no,'status'=>1])->one();
                        if($temp_user_otp->otp==$this->data_json['otp']){
                        $this->response['otp'] = $this->data_json['otp'];
                        $this->response['mobile_no'] = $this->data_json['phone_no'];
                        $this->processLogin();
                        return $this->response;
                           }
                        else{
                             throw new \yii\web\BadRequestHttpException("Otp did not matched. "); // HTTP COde 400
                        }
                   }
               }
               else{
                    throw new \yii\web\BadRequestHttpException("It looks like this mobile number is not registered. "); // HTTP COde 400
            }
          }else
              throw new \yii\web\BadRequestHttpException("It looks like this mobile is empty. "); // HTTP COde 400
          
    }

    public function actionRefresh(){
        $data=array();
            $this->response['last_wbs_sync_time'] = $this->getLastWbsUpdateTime();
            $this->response['wbs_list'] = $this->refreshWbslistall();
            $this->response['last_meeting_sync_time'] = $this->getLastWbsUpdateTime();
            $this->response['meeting_detail_all'] = array(); // = $this->getMeetinglist();
            foreach ($this->getRefreshMeetinglist() as $m) {
                $meetingentity = new \app\entites\MeetingEntity(null, $m);
                array_push($this->response['meeting_detail_all'], $meetingentity->getDetail());
            }
            $this->response['last_action_sync_time'] = $this->getLastWbsUpdateTime();
            $this->response['action_detail_all'] = array(); // = $this->getMeetinglist();
        foreach ($this->getRefreshActionlist() as $ac) {
                    $actionentity = new \app\entites\ActionPointEntity(null, $ac);
                    array_push($this->response['action_detail_all'], $actionentity->getDetail());
                }
        return $this->response;
         
    }
    public function refreshWbslistall(){
        $wbs=array();
            $org_id=\Yii::$app->user->identity->org_id;
            $owner=\Yii::$app->user->identity->id;
            $date=date('Y-m-d');
            $wbs_list= \app\models\Wbs::find()
            ->joinWith(['wbsusers'])
             ->where(['or', 'wbs.owner_id='.\Yii::$app->user->identity->id,'wbs_user.user_id=' . \Yii::$app->user->identity->id])
             ->andWhere("wbs.status = 1 and wbs.org_id ='".$org_id."'")      
             ->andWhere("wbs.end_date >=  '$date'")
             ->orderBy('wbs.id DESC')
                    //->createCommand();
          //  print_r($wbs_list->sql);die;
                   ->all();
       $resultSets = [];
        foreach($wbs_list as $key => $data):
                $wbs_user = \app\models\WbsUser::findAll(["wbs_id" => $data->id]);
                $resultSets[$key]['id'] = $data->id;
                $resultSets[$key]['owner_id'] = $data->owner_id;
                $resultSets[$key]['wbs_title'] = $data->wbs_title;
                $resultSets[$key]['start_date'] = $data->start_date;
                $resultSets[$key]['end_date'] = $data->end_date;
                $resultSets[$key]['wbs_group_id'] = $data->wbs_group_id;
                $resultSets[$key]['wbs_user_id'] = $data->wbs_user_id;
                $resultSets[$key]['status'] = $data->status;
                $resultSets[$key]['users'] = array_values($wbs_user);
                  $resultSets[$key]['notification'] = \app\models\Notification::find()->select(['notification.id','notification.detail_id','notification.user_id','notification.user_name','notification.mail_status','notification.status'])
                ->rightJoin('wbs_user', 'wbs_user.user_id = notification.user_id and wbs_user.wbs_id=notification.detail_id')
               ->where(['notification.detail_id' => $data->id])
               ->andWhere(['notification.notification_status'=>1])
               ->andWhere(['wbs_user.status'=>1])
              //   ->createCommand();
               ->all();
                endforeach;
              // $wbs['wbs_list'] = $resultSets;
               return $resultSets;
    }

    private function getRefreshMeetinglist($last_update_time = 000000) {
        $date=date('Y-m-d H:i:s');
        return \app\models\Meeting::find()
                ->joinWith(['meetingusers'])
                ->where(['or', 'meeting_user.user_id='.\Yii::$app->user->identity->id,'meeting.responsible_user_id=' . \Yii::$app->user->identity->id])
                 ->andWhere("meeting.end_datetime >  '$date'")
                ->orderBy('meeting.id DESC')
                ->all();
    }
    private function getRefreshActionlist($last_update_time = 000000) {
        $date=date('Y-m-d H:i:s');
         return  \app\models\ActionPoint::find()
                ->joinWith(['actionusers'])
                ->where(['or', 'action_user.user_id='.\Yii::$app->user->identity->id,'action_point.action_assigned_by=' . \Yii::$app->user->identity->id])
                ->andWhere(['=', 'meeting_id', '0'])
                ->andWhere("action_point.deadline >  '$date'")
                ->andWhere("action_point.status > 0")
                ->orderBy('action_point.id DESC')
                ->all();
               // ->createCommand();
         //print_r($aa->sql);
        
    }
    public function actionUpdategoogletoken() {
        //$this->processLogin();
        $user = \Yii::$app->user->identity;
        $active_app = AppDetail::findOne($this->current_module->model_apilog->app_id);
        $active_app->firebase_token = $this->data_json['firebase_token'];
        $active_app->app_version = $this->current_module->model_apilog->version_no;
        $active_app->save();
        return $this->response;
    }

    
    public function actionDeleteuser() {
         if (isset($this->data_json['user_id']) && $this->data_json['user_id'] != ''){
            $user =  \app\modules\api\v1\models\User::find()->where(['id'=>$this->data_json['user_id']])->one();
            if($user){
                $user->delete(); 
                \app\models\UserRegistration::deleteAll(['user_id' => $this->data_json['user_id']]);
                $this->response['status'] = "1";
                $this->response['message'] = "User Id $this->data_json['user_id'] Deleted succesfully.";
                return $this->response;
            } else 
              throw new \yii\web\NotFoundHttpException('The requested User does not exist.');   
            // delete record   
           
         }
    }
    
    public function actionUpdatepassword() {
        $user = \Yii::$app->user->identity;
        $user->resetPassword($this->data_json['password']);
        return $this->response;
    }
        function unique_multidim_array($array, $key) { 
            $temp_array = array(); 
            $i = 0; 
            $key_array = array(); 

            foreach($array as $val) { 
                if(mb_strlen($val['mobile_no'])!=10){
                    $sub=mb_strlen($val['mobile_no'])-10;
                    }else{
                        $sub=0;
                        }
                    $val['mobile_no'] = substr($val['mobile_no'], $sub);  
                if (!in_array($val[$key], $key_array)) { 
                    $key_array[$i] = $val[$key]; 
                    $temp_array[$i] = $val; 
                } 
                $i++; 
            } 
            return $temp_array; 
        } 
    function sendOtpMessage($to,$otp,$type){
        
                        $id = "ACbd2eebeea0079e04ad8b875811889745";
                        $token = "f5fe0bdca76fb7f6277d5fd301b6fe6e";
                        $url = "https://api.twilio.com/2010-04-01/Accounts/$id/SMS/Messages";
                        $from = "+17274780497";
						$to = $to;
						if($type==1)
                        $body = " One Time Password for Algn registration is $otp.";
                        if($type==2)
                        $body = " One Time Password for Algn Login is $otp.";
                        
                        $data = array (
                           'From' => $from,
                           'To' => $to,
                           'Body' => $body,
                        );
                        $post = http_build_query($data);
                        $x = curl_init($url );
                        curl_setopt($x, CURLOPT_POST, true);
                        curl_setopt($x, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($x, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($x, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                        curl_setopt($x, CURLOPT_USERPWD, "$id:$token");
                        curl_setopt($x, CURLOPT_POSTFIELDS, $post);
                        $y = curl_exec($x);
                        curl_close($x);
                        $true='1';
                      //  var_dump($post);
                      //  var_dump($y);
    }
    public function actionUserregistration() {
         if (isset($this->data_json['phone_no']) && $this->data_json['phone_no'] != ''){
            $name=  $this->data_json['name'];
            $phone_no=$this->data_json['phone_no'];
            $substr_phone_no = substr($phone_no, 0);
           $user_model = \app\modules\api\v1\models\User::find()
                  ->where(['phone_no'=>$substr_phone_no])->one();
              if($user_model){
                 throw new \yii\web\BadRequestHttpException("It looks like this mobile number is a already registered. "); // HTTP COde 400
           }
           else{
               if(empty($this->data_json['otp'])){
                   //$otp=rand(1000000,100);
                   $otp=mt_rand(100000,999999);
				   $phone_no='+91'.$phone_no;
                   $this->sendOtpMessage($phone_no,$otp,$type=1);
                    \app\models\TempUserOtp::updateAll(['status' => 0], "mobile_no =" . $substr_phone_no);
                            $temp_user_otp = new \app\models\TempUserOtp();
                            $temp_user_otp->otp=$otp;
                            $temp_user_otp->mobile_no=$substr_phone_no;
                            $temp_user_otp->status=1;
                            if($temp_user_otp->save()){
                                $this->response['status'] = "1";
								$this->response['otp'] = $otp;
                                $this->response['message'] = "OTP generated.";
							//	$this->response['mobile_no'] = $this->data_json['phone_no'];
                                return $this->response;
                            }
                } if (isset($this->data_json['otp']) && $this->data_json['otp'] != '') {
                   $temp_user_otp= \app\models\TempUserOtp::find()->where(['mobile_no'=>$substr_phone_no,'status'=>1])->one();
                        if($temp_user_otp->otp==$this->data_json['otp']){
                            $this->processReg();
                           }
                        else{
                             throw new \yii\web\BadRequestHttpException("Otp did not matched. "); // HTTP COde 400
                        }
                        return $this->response;
                   }
           }
          }
          throw new \yii\web\BadRequestHttpException("Mobile number did not found. "); // HTTP COde 400
    }
        
    public function actionRegistration() {
        $this->processReg();
        return $this->response;
    }
    
   private function processReg(){
         if (isset($this->data_json['phone_no']) and isset($this->data_json['name'])) {
             $user = \Yii::createObject([
                    'class' => \app\models\UserModel::className(),
                    'scenario' => 'create',
        ]);
           
        $user->role = \app\models\UserModel::ROLE_ORG_USER;
        $user->org_id = 3;
        $user->status = 1;
        $user->name=$this->data_json['name'];
        $substr_phone_no = substr($this->data_json['phone_no'], 0);
        $user->username=$substr_phone_no;
        $user->phone_no=$substr_phone_no;
        $user->password="password";
        $user->email='';
        if ($user->create()) {
             if (isset($this->data_json['profile_image']) && $this->data_json['profile_image'] != '') {
                $data= $this->data_json['profile_image'];
                $data = str_replace('data:image/png;base64,', '', $data);
                $data = str_replace(' ', '+', $data);
                $data = base64_decode($data);
                if (!(file_exists(Yii::getAlias('@app') . '/web/upload/users/' . $user->id))) {
                            mkdir(Yii::getAlias('@app') . '/web/upload/users/' . $user->id);
                            chmod(Yii::getAlias('@app') . '/web/upload/users/' . $user->id, 0777);
                        }
                $random=rand(100,10000000);
                $TEMP_FILE = Yii::getAlias('@app') . '/web/upload/users/' . $user->id . '/'.$user->id . '.png';
                $success = file_put_contents($TEMP_FILE, $data);
                 if (chmod($TEMP_FILE, 0777)) {
               }
                $user->profile_image=$user->id . '.png';
                 $user->update();
             }
            $details = $this->unique_multidim_array($this->data_json['users'],'mobile_no'); 
            $count=count($details);
             if($count >0){
                  foreach ($details as $detail):
                    $phone_no=$detail['mobile_no'];
                    $user_model =  \app\modules\api\v1\models\User::find()->where(['phone_no'=>$phone_no])->one();
                    $user_registration= new \app\models\UserRegistration();
                        if($user_model){  
                          $availability="1";
                          $flags="1";
                          $user_registration->root_user_id=$user_model->id;
                          $user_registration->profile_image=$user_model->profile_image;
                       }else{
                           $availability="0";
                        $flags="0";
                     }  
                        $user_registration->user_id=$user->id;
                        $user_registration->org_id=3;
                        $user_registration->name=$detail['name'];
                        $user_registration->phone_no=$detail['mobile_no'];
                        $user_registration->status="1";
                        $user_registration->flags=$flags;
                        $user_registration->availability=$availability;
                        $user_registration->created_by=$user->id;
                        $user_registration->save();
                endforeach;
                 
             }
               
            $this->processLogin();
        }
           
         }else{
              throw new \yii\web\BadRequestHttpException("Bad Request, username or password missing"); // HTTP COde 400
         }
        
      
    }

    public function actionContactsync(){
		 if (isset($this->data_json['user_id']) and isset($this->data_json['user_id'])) {
              $user_model = \app\modules\api\v1\models\User::find()
                  ->where(['id'=>$this->data_json['user_id']])->one();
             if($user_model){
                $details = $this->unique_multidim_array($this->data_json['users'],'mobile_no'); 
                $count=count($details);
             if($count >0){
                 //\app\models\UserRegistration::updateAll(['status' => 0], "user_id =" . $this->data_json['user_id']);
                 foreach ($details as $detail):
                    $phone_no=$detail['mobile_no'];
                    $user_reg =  \app\models\UserRegistration::find()->where(['user_id'=>$this->data_json['user_id'],'phone_no'=>$phone_no])->one();
                    if($user_reg){
                           $user =  \app\modules\api\v1\models\User::find()->where(['phone_no'=>$phone_no])->one();
                           if($user){
                            $user_reg->status="1";
                            $user_reg->flags='1';
                            $user_reg->root_user_id=$user->id;
                            $user_reg->availability='1';
                            $user_reg->profile_image=$user->profile_image;
                            $user_reg->update();
                           }
                       }
                     else{ 
                           $user =  \app\modules\api\v1\models\User::find()->where(['phone_no'=>$phone_no])->one();
                           $user_registration= new \app\models\UserRegistration();
                           if($user){ 
                                $availability="1";
                                $flags="1";
                                $user_registration->root_user_id=$user->id;
                                $user_registration->profile_image=$user_model->profile_image;
                             }else{
                                $availability="0";
                                $flags="0";
                           }  
                        $user_registration->user_id=$this->data_json['user_id'];
                        $user_registration->org_id=3;
                        $user_registration->name=$detail['name'];
                        $user_registration->phone_no=$detail['mobile_no'];
                        $user_registration->status="1";
                        $user_registration->flags=$flags;
                        $user_registration->availability=$availability;
                     //   $user_registration->created_by=$user->id;
                        $user_registration->save();
                     }
                     
                  endforeach;
                   $this->response['root_user'] = $this->getRootUser();
		  $this->response['user_register'] = \app\models\UserRegistration::find()->where(['status' => 1,'user_id'=>$this->data_json['user_id']])->all();
                 return $this->response;
              
             }
            }
            else{
                throw new \yii\web\BadRequestHttpException("This user is not exists ! "); // HTTP COde 400
            }
            
         }
       
    }
    
    public function actionUpdateprofile() {
         if (isset($this->data_json['user_id']) and isset($this->data_json['user_id'])) {
             $user = $this->findModel($this->data_json['user_id']);
             if($user){
                    $user->scenario = 'update';
                    $event = $this->getUserEvent($user);
                    $olgimage = $user->profile_image;
                    $this->trigger(self::EVENT_BEFORE_UPDATE, $event);
                    if (isset($this->data_json['name'])){
                        $user->name=$this->data_json['name'];
                    }
                    if (isset($this->data_json['email'])){
                        $user->email=$this->data_json['email'];
                    }
                    if (isset($this->data_json['phone_no'])){
                        $user->phone_no=$this->data_json['phone_no'];
                    }
                    if (isset($this->data_json['password'])){
                      //  $user->password=$this->data_json['password'];
                    }
                if ($user->save()) {
                       if (isset($this->data_json['profile_image']) && $this->data_json['profile_image'] != '') {
                        $data= $this->data_json['profile_image'];
                        $data = str_replace('data:image/png;base64,', '', $data);
                        $data = str_replace(' ', '+', $data);
                        $data = base64_decode($data);
                        if (!(file_exists(Yii::getAlias('@app') . '/web/upload/users/' . $user->id))) {
                            mkdir(Yii::getAlias('@app') . '/web/upload/users/' . $user->id);
                            chmod(Yii::getAlias('@app') . '/web/upload/users/' . $user->id, 0777);
                        }
                        $random=rand(100,10000000);
                        $TEMP_FILE = Yii::getAlias('@app') . '/web/upload/users/' . $user->id . '/'.$user->id . '.png';
                        $success = file_put_contents($TEMP_FILE, $data);
                         if (chmod($TEMP_FILE, 0777)) {
                       }
                        $user->profile_image=$user->id . '.png';
                         $user->update();
                    } else {
                        $user->profile_image = $olgimage;
                        $user->update();
                    }
                  $this->trigger(self::EVENT_AFTER_UPDATE, $event);
                  $this->response['user_detail'] = $this->getUserprofile();
                  return $this->response;
                }
             }else{
                  throw new NotFoundHttpException('The requested User does not exist');
             }
         }
        throw new NotFoundHttpException('The requested page does not exist');
    }
    
    public function actionAdduser(){
        if (isset($this->data_json['username']) and isset($this->data_json['password'])) {
             $user = \Yii::createObject([
                    'class' => \app\models\UserModel::className(),
                    'scenario' => 'create',
        ]);
        $user->role = \app\models\UserModel::ROLE_ORG_USER;
        $user->org_id = \Yii::$app->user->identity->org_id;
        $user->status = 1;
        $user->name=$this->data_json['name'];
        $user->email=$this->data_json['email'];
        $user->username=$this->data_json['username'];
        $user->phone_no=$this->data_json['phone_no'];
        $user->password=$this->data_json['password'];
	$username=$user->username;
        $email=$user->email;
        
        $user_model = \app\modules\api\v1\models\User::find()
                  ->where(['phone_no'=>$user->phone_no])
                ->orWhere(["or","username="."'$username'","email="."'$email'"])->one();
    //    print_r($user->create());die;
        if($user_model){
             throw new \yii\web\BadRequestHttpException("This username or phone no is already exists ! "); // HTTP COde 400
            }
        $event = $this->getUserEvent($user);
       
        $this->trigger(self::EVENT_BEFORE_CREATE, $event);
        //$user->save();
       // print_r('success');die;
        if ($user->create()) {
            //  print_r('hello jjj');die;
            // return $this->response['user_detail'] = $this->getUserprofile();
             if (isset($this->data_json['profile_image']) && $this->data_json['profile_image'] != '') {
                $data= $this->data_json['profile_image'];
                $data = str_replace('data:image/png;base64,', '', $data);
                $data = str_replace(' ', '+', $data);
                $data = base64_decode($data);
                if (!(file_exists(Yii::getAlias('@app') . '/web/upload/users/' . $user->id))) {
                            mkdir(Yii::getAlias('@app') . '/web/upload/users/' . $user->id);
                            chmod(Yii::getAlias('@app') . '/web/upload/users/' . $user->id, 0777);
                        }
                $random=rand(100,10000000);
                $TEMP_FILE = Yii::getAlias('@app') . '/web/upload/users/' . $user->id . '/'.$user->id . '.png';
                $success = file_put_contents($TEMP_FILE, $data);
                 if (chmod($TEMP_FILE, 0777)) {
               }
                $user->profile_image=$user->id . '.png';
                $user->update();
             }
           
            //  print_r('hello gggg');die;
            $this->trigger(self::EVENT_AFTER_CREATE, $event);
                    $this->response['status'] = "1";
                    $this->response['message'] = "Successfull";
                    $this->response['user_detail'] = \app\models\UserModel::find()->where(['id'=>$user->id])->one();
            return  $this->response;
          //  $this->processLogin();
        }
           
         }else{
              throw new \yii\web\BadRequestHttpException("Bad Request, username or password missing"); // HTTP COde 400
         }
        
      
    }
     protected function findModel($id) {
        $user = \app\models\UserModel::findOne($id);
        if ($user === null) {
            throw new NotFoundHttpException('The requested page does not exist');
        }

        return $user;
    }
    public function actionChangepassword() {
        /** @var User $user */
       
        $user = \Yii::createObject([
                    'class' => \app\models\form\ChangePasswordForm::className(),
        ]);
        
       // $this->performAjaxValidation($user);
         if (isset($this->data_json['new_password'])){
            $user->new_password=$this->data_json['new_password'];
        }
        if (isset($this->data_json['re_password'])){
            $user->re_password=$this->data_json['re_password'];
        }
        if (isset($this->data_json['current_password'])){
            $user->current_password=$this->data_json['current_password'];
        }
        if ($user->save()) {
            $this->response['user_detail'] = $this->getUserprofile();
            return $this->response;
        }
       throw new \yii\web\BadRequestHttpException("Bad Request, Password does not match"); 
    }
    
     protected function performAjaxValidation($model) {
        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax) {
            if ($model->load(\Yii::$app->request->post())) {
                \Yii::$app->response->format = Response::FORMAT_JSON;
                echo json_encode(ActiveForm::validate($model));
                \Yii::$app->end();
            }
        }
    }
    public function actionList() {

        $this->response['last_user_sync_time'] = $this->getLastUserUpdateTime();
        $this->response['user_list'] = $this->getUserlist();

        Yii::$app->db->createCommand()
                ->update('notification', ['acknowledge_status' => 1, 'acknowledge_date' => date('Y-m-d H:i:s ')], 'notification_type = 1 and acknowledge_status = 0 and user_id = ' . \Yii::$app->user->identity->id)
                ->execute();

        return $this->response;
    }

   
    
    public function actionListall(){
        $group = UserGroup::find()->all();
        
    }
    public function actionUserdetails(){
        if (isset($this->data_json['user_id']) && $this->data_json['user_id'] != '') {
            $user_id=$this->data_json['user_id'];
            $users=\app\modules\api\v1\models\User::find()
                ->where(["id"=>$user_id])->one();
             if ($users == null) {
                throw new \yii\web\ForbiddenHttpException("Forbidden");
            } else {
                return $this->response['data']=$users;
              }
           
        }
       
    }
    public function actionSavewbs() {

        if (isset($this->data_json['wbs_id']) && $this->data_json['wbs_id'] != '') {
            $wbs_model = \app\models\Wbs::getModel($this->data_json['wbs_id'], \Yii::$app->user->identity->id);
//            if($wbs_model->status=='0'){
//                        $this->response['status'] = "0";
//                        $this->response['message'] = " Ooph!, This WBS is Inactive";
//                        return $this->response;
//            }
            if ($wbs_model == null) {
                throw new \yii\web\ForbiddenHttpException("Forbidden");
            } else {
                $form_model = new \app\models\form\WbsForm($this->data_json['wbs_id']);
              }
        } else {
            $wbs_model = Yii::createObject([
                        'class' => \app\models\Wbs::className(),
            ]);
            $form_model = new \app\models\form\WbsForm();
          }
        if ($form_model->load(['WbsForm' => $this->data_json])) {
            if ($form_model->validate()) {
                    $array1 = array();
                    if(!empty($this->data_json['wbsgroups'])) {
                        $selected_groups_ids = explode(",", $this->data_json['wbsgroups']);
                        $wbsuser[0]['group_id'] = $selected_groups_ids;
                        $groups_member_id = array();
                            foreach($selected_groups_ids as $row => $grp_id): 
                            $selected_member_group = '';
                            $group_member = \app\models\Group::findOne($grp_id);
                            $selected_member_group = $group_member->users;
                            $groups_member_id = explode(",", $group_member->users);
                            $array1 = array_merge($array1,$groups_member_id);
                            endforeach;
                            $array1 = array_unique($array1);
                            $strUserId = implode(",",$array1);
                     }
                        if(!empty($this->data_json['wbsusers'])){    
                            $wbsusers = explode(",", $this->data_json['wbsusers']);
                            $array1 = array_merge($wbsusers,$array1);
                            $array1 = array_unique($array1);
                            }
                            $form_model->wbsuser=$array1;
                            $form_model->wbs_group_id=$this->data_json['wbsgroups'];
                            $form_model->wbs_user_id=$this->data_json['wbsusers'];
                            $form_model->status=$this->data_json['status'];
                            
                            $wbsentity = new \app\entites\WbsEntity($form_model, $wbs_model);  
                            $wbsentity->save();
                             $this->response['wbs_list'] = $this->getWbslistall();
            } else {
                $this->response['status'] = "0";
                $this->response['message'] = "Error(s): {" . \app\helpers\Utility::convertModelErrorToString($form_model) . "}";
            }
        } else {
            throw new \yii\web\BadRequestHttpException("Bad Request, WBS form model not loaded or didn't validate"); // HTTP Code 400
        }
        return $this->response;
    }
    
 public function actionWbslist() {
    $wbs=array();
        $wbs['status'] = "1";
        $wbs['message'] = "Success";
    $org_id=\Yii::$app->user->identity->org_id;
        $owner=\Yii::$app->user->identity->id;
        $wbs_list= \app\models\Wbs::find()
     ->joinWith(['wbsusers'])->where(['=', 'wbs_user.user_id', \Yii::$app->user->identity->id])
                ->andWhere("wbs.status=1 and wbs.org_id ='".$org_id."'")               
    ->orderBy('id DESC')
                ->all();
       $resultSets = [];
        foreach($wbs_list as $key => $data):
                $wbs_user = \app\models\WbsUser::findAll(["wbs_id" => $data->id]);
                $resultSets[$key]['id'] = $data->id;
                $resultSets[$key]['owner_id'] = $data->owner_id;
                $resultSets[$key]['wbs_title'] = $data->wbs_title;
                $resultSets[$key]['start_date'] = $data->start_date;
                $resultSets[$key]['end_date'] = $data->end_date;
                $resultSets[$key]['wbs_group_id'] = $data->wbs_group_id;
                $resultSets[$key]['wbs_user_id'] = $data->wbs_user_id;
                $resultSets[$key]['status'] = $data->status;
                $resultSets[$key]['users'] = array_values($wbs_user);
       endforeach;
    $wbs['wbs_list'] = $resultSets;
               return $wbs;   
    }
     public function actionListallwbs() {
        $wbs=array();
        $wbs['status'] = "1";
        $wbs['message'] = "Success";
        $org_id=\Yii::$app->user->identity->org_id;
        $owner=\Yii::$app->user->identity->id;
        $wbs_list= \app\models\Wbs::find()
        ->joinWith(['wbsusers'])->where(['=', 'wbs_user.user_id', \Yii::$app->user->identity->id])
                ->Orwhere(['=', 'wbs_user.created_by', \Yii::$app->user->identity->id])
                ->andWhere("wbs.status=1 and wbs.org_id ='".$org_id."'")               
    ->orderBy('id DESC')
                ->all();
       $resultSets = [];
       if($wbs_list){
             foreach($wbs_list as $key => $data):
                $wbs_user = \app\models\WbsUser::findAll(["wbs_id" => $data->id]);
                $wbs_meeting = \app\models\Meeting::find()
                ->joinWith(['meetingusers'])
                ->where(['or', 'meeting_user.user_id='.\Yii::$app->user->identity->id,'meeting.created_by=' . \Yii::$app->user->identity->id])
                ->andWhere("meeting.wbs_id = $data->id")
                ->andWhere("meeting.origin_source = 2")
                ->orderBy('meeting.id DESC')
                ->all();
                 $wbs_action=\app\models\ActionPoint::find()
                ->joinWith(['actionusers'])
                ->where(['or', 'action_user.user_id='.\Yii::$app->user->identity->id,'action_point.action_assigned_by=' . \Yii::$app->user->identity->id])
                ->Orwhere(['=', 'action_point.created_by', \Yii::$app->user->identity->id])
                ->andWhere("action_point.wbs_id = $data->id")
                ->andWhere("action_point.origin_source = 3")
                //->andWhere(['=', 'origin_source', 3])
                //->andWhere("action_point.deadline >  '$date'")
                ->andWhere("action_point.status > 0")
                ->orderBy('action_point.id DESC')
                ->all();
                $resultSets[$key]['id'] = $data->id;
                $resultSets[$key]['owner_id'] = $data->owner_id;
                $resultSets[$key]['wbs_title'] = $data->wbs_title;
                $resultSets[$key]['start_date'] = $data->start_date;
                $resultSets[$key]['end_date'] = $data->end_date;
                $resultSets[$key]['wbs_group_id'] = $data->wbs_group_id;
                $resultSets[$key]['wbs_user_id'] = $data->wbs_user_id;
                $resultSets[$key]['status'] = $data->status;
                $resultSets[$key]['users'] = array_values($wbs_user);
                $resultSets[$key]['wbs_meeting'] = array_values($wbs_meeting);
                $resultSets[$key]['wbs_action'] = array_values($wbs_action);
       endforeach;
            $wbs['wbs_list'] = $resultSets;
               return $wbs;   
       }else{
        $wbs['status'] = "0";
        $wbs['message'] = "Data not Available";
        return $wbs;   
       }
      
    }
    public function actionWbsdetail(){
        if (isset($this->data_json['wbs_id']) and isset($this->data_json['wbs_id'])) {
         $wbs_detail= \app\models\Wbs::find()->where(["id" => $this->data_json['wbs_id']])
                ->all();
         $resultSets = [];
       if($wbs_detail){
             foreach($wbs_detail as $key => $data):
                $wbs_user = \app\models\WbsUser::findAll(["wbs_id" =>$data->id]);
                $resultSets[$key]['id'] = $data->id;
                $resultSets[$key]['owner_id'] = $data->owner_id;
                $resultSets[$key]['wbs_title'] = $data->wbs_title;
                $resultSets[$key]['start_date'] = $data->start_date;
                $resultSets[$key]['end_date'] = $data->end_date;
                $resultSets[$key]['wbs_group_id'] = $data->wbs_group_id;
                $resultSets[$key]['wbs_user_id'] = $data->wbs_user_id;
                $resultSets[$key]['created_at'] = $data->created_at;
                $resultSets[$key]['created_by'] = $data->created_by;
                $resultSets[$key]['updated_at'] = $data->updated_at;
                $resultSets[$key]['updated_by'] = $data->updated_by;
                $resultSets[$key]['status'] = $data->status;
                $resultSets[$key]['users'] = array_values($wbs_user);
               endforeach;
          }
           $this->response['wbs_detail'] = $resultSets;
               return $this->response; 
      
    }
    }
    public function actionWbsdetails() {
        
          if (isset($this->data_json['wbs_id']) and isset($this->data_json['wbs_id'])) {
              $id=$this->data_json['wbs_id'];
              
               $wbs=array();
        $wbs['status'] = "1";
        $wbs['message'] = "Success";
        $org_id=\Yii::$app->user->identity->org_id;
        $owner=\Yii::$app->user->identity->id;
        $wbs_list= \app\models\Wbs::find()->where(["id" => $id])
                ->all();
       $resultSets = [];
      
       if($wbs_list){
             foreach($wbs_list as $key => $data):
                // print_r($data->id);die;
                $wbs_user = \app\models\WbsUser::findAll(["wbs_id" => $id]);
                $wbs_meeting = \app\models\Meeting::find()
                ->joinWith(['meetingusers'])
                ->where(['or', 'meeting_user.user_id='.\Yii::$app->user->identity->id,'meeting.created_by=' . \Yii::$app->user->identity->id])
                ->andWhere("meeting.wbs_id = $data->id")
                ->andWhere("meeting.origin_source = 2")
                ->orderBy('meeting.id DESC')
                ->all();
                 $wbs_action=\app\models\ActionPoint::find()
                ->joinWith(['actionusers'])
                ->where(['or', 'action_user.user_id='.\Yii::$app->user->identity->id,'action_point.action_assigned_by=' . \Yii::$app->user->identity->id])
                ->Orwhere(['=', 'action_point.created_by', \Yii::$app->user->identity->id])
                ->andWhere("action_point.wbs_id = $data->id")
                ->andWhere("action_point.origin_source = 3")
                ->andWhere("action_point.status > 0")
                ->orderBy('action_point.id DESC')
                ->all();
                $resultSets[$key]['id'] = $data->id;
                $resultSets[$key]['owner_id'] = $data->owner_id;
                $resultSets[$key]['wbs_title'] = $data->wbs_title;
                $resultSets[$key]['start_date'] = $data->start_date;
                $resultSets[$key]['end_date'] = $data->end_date;
                $resultSets[$key]['wbs_group_id'] = $data->wbs_group_id;
                $resultSets[$key]['wbs_user_id'] = $data->wbs_user_id;
                $resultSets[$key]['status'] = $data->status;
                $resultSets[$key]['users'] = array_values($wbs_user);
                $resultSets[$key]['wbs_meeting'] = array_values($wbs_meeting);
                $resultSets[$key]['wbs_action'] = array_values($wbs_action);
       endforeach;
            $wbs['wbs_list'] = $resultSets;
               return $wbs;   
       }else{
        $wbs['status'] = "0";
        $wbs['message'] = "Data not Available";
        return $wbs;   
       }
          }
       
      
    }
    private function processLogin() {
          if (isset($this->data_json['phone_no'])) {    
            $model = \Yii::createObject(LoginForm::className());
            
           // $model->login = $this->data_json['username'];
           // $model->password = $this->data_json['password'];
             $substr_phone_no = substr($this->data_json['phone_no'], 0);
            $model->login = $substr_phone_no;
            $model->password = "password";
           if ($model->login()) {
                 if (isset($this->data_json['web']) && $this->data_json['web']==1) {
                    $member_app = AppDetail::find()->where(['user_id' => \Yii::$app->user->identity->id, 'status' => 1])->all();
                    if(empty($member_app)){
                        $member_app_model = new AppDetail();
                        $member_app_model->user_id = \Yii::$app->user->identity->id;
                        $member_app_model->org_id = \Yii::$app->user->identity->org_id;
                        $member_app_model->imei_no = $this->data_json['imei_no']!='' ? $this->data_json['imei_no']:'866225024275816';
                        $member_app_model->os_type = $this->data_json['os_type']!='' ? $this->data_json['os_type']:'Web';
                        $member_app_model->manufacturer_name = $this->data_json['manufacturer_name']!='' ? $this->data_json['manufacturer_name']:'Login from web';
                        $member_app_model->os_version =  $this->data_json['os_version']!='' ? $this->data_json['os_version']:'0';
                        $member_app_model->app_version = $this->data_json['app_version']!='' ? $this->data_json['app_version']:'0';
                        $member_app_model->firebase_token = $this->data_json['firebase_token']!='' ? $this->data_json['firebase_token']:'0';
                       // $member_app_model->firebase_token_web = $this->data_json['firebase_token_web']!='' ? $this->data_json['firebase_token_web']:'0';
                        $member_app_model->date_of_install = new Expression('NOW()');
                        $member_app_model->save();
                    }
                $this->response['status'] = "1";
		$this->response['app_detail'] = AppDetail::find()->where(['user_id' => \Yii::$app->user->identity->id, 'status' => 1])->all();
                $this->response['user_detail']=\app\modules\api\v1\models\User::find()->where(['id'=>\Yii::$app->user->identity->id])->one();
                return $this->response;
                }
                $member_app = AppDetail::find()->where(['user_id' => \Yii::$app->user->identity->id, 'status' => 1])->all();
                if (!empty($member_app)) {
                    if (isset($this->data_json['confirm_overwrite']) && $this->data_json['confirm_overwrite'] == "1") {
                        if (AppDetail::updateAll(['date_of_uninstall' => new Expression('NOW()'), 'status' => 0], 'user_id ="' . \Yii::$app->user->identity->id . '" and status=' . '1')) {
                            $this->processLoginIntoDb(TRUE);
                        } else {
                            throw new \yii\web\ServerErrorHttpException('App registartion error : unable to diable old apps');
                        }
                    } else {
                        $this->response['status'] = "0";
                        $this->response['message'] = "you are already registered with some other device. Do you want to disable the previous device";
                    }
                } else {
                    $this->processLoginIntoDb();
                }
            } else {
                throw new \yii\web\UnauthorizedHttpException("Invalid username or password"); // HTTP code 401
            }
        } else {
            throw new \yii\web\BadRequestHttpException("Bad Request, username or password missing"); // HTTP COde 400
        }
    }

    private function processLoginIntoDb($confirm_overwrite = FALSE) {
        $this->response['status'] = "1";
        $member_app_model = new AppDetail();
        $member_app_model->user_id = \Yii::$app->user->identity->id;
        $member_app_model->org_id = \Yii::$app->user->identity->org_id;
        $member_app_model->imei_no = $this->data_json['imei_no'];
        $member_app_model->os_type = $this->data_json['os_type'];
        $member_app_model->manufacturer_name = $this->data_json['manufacturer_name'];
        $member_app_model->os_version = $this->data_json['os_version'];
        $member_app_model->app_version = $this->data_json['app_version'];
        $member_app_model->firebase_token = $this->data_json['firebase_token'];
        $member_app_model->date_of_install = new Expression('NOW()');
         
        if ($member_app_model->save()) {
            $this->response['message'] = "success, request processed successfully";
            $this->response['app_id'] = $member_app_model->id;
            $this->response['user_id'] = $member_app_model->user_id;
            $this->response['name'] = $this->getUserprofile()->name;
            $this->response['username'] = \Yii::$app->user->identity->name;
            $this->response['profile_image'] = $this->getUserprofile()->profile_image;
            $this->response['org_name'] = \Yii::$app->user->identity->organization->name;
            $this->response['sendbird_app_id'] = \Yii::$app->user->identity->organization->sendbird_app_id;
            $this->response['last_user_sync_time'] = $this->getLastUserUpdateTime();
            $this->response['user_detail'] = $this->getUserprofile();
            $this->response['user_register'] = $this->getUserlist();
            $this->response['root_user'] = $this->getRootUser();
            $this->response['last_wbs_sync_time'] = $this->getLastWbsUpdateTime();
            $this->response['wbs_list'] = $this->getWbslistall();
            $this->response['last_group_sync_time'] = $this->getLastGroupUpdateTime();
            $this->response['group_list'] = $this->getGrouplist();
            $this->response['last_meeting_sync_time'] = $this->getLastWbsUpdateTime();
            $this->response['meeting_detail_all'] = array(); // = $this->getMeetinglist();
            foreach ($this->getMeetinglist() as $m) {
                $meetingentity = new \app\entites\MeetingEntity(null, $m);
                array_push($this->response['meeting_detail_all'], $meetingentity->getDetail());
            }
            $this->response['last_action_sync_time'] = $this->getLastWbsUpdateTime();
            $this->response['action_detail_all'] = array(); // = $this->getMeetinglist();
    
            foreach ($this->getActionlist() as $ac) {
                    $actionentity = new \app\entites\ActionPointEntity(null, $ac);
                    array_push($this->response['action_detail_all'], $actionentity->getDetail());
                }

            \app\models\Notification::updateAll(['acknowledge_status' => 1, 'acknowledge_date' => new Expression('NOW()'), 'app_id' => $member_app_model->id], 'user_id ="' . \Yii::$app->user->identity->id . '" and acknowledge_status=' . '0');
        } else {
            throw new \yii\web\ServerErrorHttpException("App registartion error : " . json_encode($member_app_model->getErrors()));
        }
    }
    private function getUserprofile() {
        return \app\models\UserModel::find()->where(['id'=>\Yii::$app->user->identity->id])->one();
    }

//    private function getUserlist($last_update_time = 000000) {
////        $group=array();
////        $connection = Yii::$app->getDb();
////        $command = $connection->createCommand("SELECT `id`, `name`, `email`, `status`, `profile_image`  FROM `user` WHERE `org_id`='".\Yii::$app->user->identity->org_id."' ORDER BY `name` DESC ");
////        $resultSets=[];         
////		  foreach($command->queryAll() as $key => $data):
////                    $resultSets[$key]['id']=$data['id'];
////                    $resultSets[$key]['name']= trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $data['name'])));
////                    $resultSets[$key]['email']=$data['email'];
////                    $resultSets[$key]['status']=$data['status'];
////                    $resultSets[$key]['profile_image']=$data['profile_image'];
////            endforeach;
////            return $resultSets;
//        return \app\models\UserModel::find()->select(['id', 'name', 'email', 'status', 'profile_image'])->where(['=', 'org_id', \Yii::$app->user->identity->org_id])->andWhere(['>', 'updated_at', $last_update_time])->orderBy('updated_at asc')->all();
//    }
     private function getUserlist($last_update_time = 000000) {
        return \app\models\UserRegistration::find()->where(['status' => 1,'user_id'=>\Yii::$app->user->identity->id])->all();
    }
    private function getRootUser($last_update_time = 000000){
        return \app\models\UserModel::find()->select(['id', 'name', 'email', 'status', 'profile_image'])->where(['=', 'org_id', \Yii::$app->user->identity->org_id])->andWhere(['>', 'updated_at', $last_update_time])->orderBy('updated_at asc')->all();
    }
    public function actionUserlist($last_update_time = 000000) {
	 $group=array();
        $id=\Yii::$app->user->identity->id;
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("SELECT u.id,u.name,u.created_at,u.email,u.phone_no,u.username,u.profile_image FROM user JOIN user_registration as ur on user.id=ur.user_id JOIN user as u on u.id= ur.root_user_id WHERE user.org_id='".\Yii::$app->user->identity->org_id."' AND user.status=1 and u.id <>0 and user.id= $id");
        $resultSets=[];         
		  foreach($command->queryAll() as $key => $data):
                    $resultSets[$key]['id']=$data['id'];
                    $resultSets[$key]['users']=$data['name'];
                    $resultSets[$key]['created_at']=$data['created_at'];
                    $resultSets[$key]['email']=$data['email'];
                    $resultSets[$key]['phone_no']=$data['phone_no'];
                    $resultSets[$key]['username']=$data['username'];
                    $resultSets[$key]['profile_image']=$data['profile_image'];
                    $resultSets[$key]['status']=$data['status'];
                    $resultSets[$key]['blocked_at']=$data['blocked_at'];
                    $resultSets[$key]['confirmed_at']=$data['confirmed_at'];
                    
                    
            endforeach;
            return $resultSets;
       }
        public function actionUserlistadmin($last_update_time = 000000) {
		
        $group=array();
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("SELECT * FROM `user` WHERE `org_id`='".\Yii::$app->user->identity->org_id."'");
        $resultSets=[];         
		  foreach($command->queryAll() as $key => $data):
                    $resultSets[$key]['id']=$data['id'];
                    $resultSets[$key]['users']=$data['name'];
                    $resultSets[$key]['created_at']=$data['created_at'];
                    $resultSets[$key]['email']=$data['email'];
                    $resultSets[$key]['phone_no']=$data['phone_no'];
                    $resultSets[$key]['username']=$data['username'];
                    $resultSets[$key]['profile_image']=$data['profile_image'];
					$resultSets[$key]['status']=$data['status'];
                    $resultSets[$key]['blocked_at']=$data['blocked_at'];
                    $resultSets[$key]['confirmed_at']=$data['confirmed_at'];
                    
          endforeach;
		 
           return $resultSets;
       }

       public function actionUserlistCopy($last_update_time = 000000) {
		
        $group=array();
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("SELECT * FROM `user` WHERE `org_id`='".\Yii::$app->user->identity->org_id."' AND status=1");
        $resultSets=[];         
		  foreach($command->queryAll() as $key => $data):
                    $resultSets[$key]['id']=$data['id'];
                    $resultSets[$key]['users']=$data['name'];
                    $resultSets[$key]['created_at']=$data['created_at'];
                    $resultSets[$key]['email']=$data['email'];
                    $resultSets[$key]['phone_no']=$data['phone_no'];
                    $resultSets[$key]['username']=$data['username'];
                    $resultSets[$key]['profile_image']=$data['profile_image'];
            endforeach;
            return $resultSets;
       }
private function getGrouplist($last_update_time = 000000) {
    
        $group=array();
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("SELECT `user_group`.* FROM `user_group` INNER JOIN `user` ON FIND_IN_SET(user.id,user_group.users) > '0' AND user.id='".\Yii::$app->user->identity->id."' AND user_group.status=1 GROUP BY `user_group`.`id` ORDER BY `user_group`.`id` DESC");
        $resultSets=[];         
        foreach($command->queryAll() as $key => $data):
                    $id=$data['id'];
                     $resultSets[$key]['id']=$data['id'];
                     $resultSets[$key]['users']=$data['users'];
                     $resultSets[$key]['group_name']=$data['group_name'];
                     $resultSets[$key]['org_id']=$data['org_id'];
                     $resultSets[$key]['created_at']=$data['created_at'];
                     $resultSets[$key]['created_by']=$data['created_by'];
                     $resultSets[$key]['updated_at']=$data['updated_at'];
                     $resultSets[$key]['updated_by']=$data['updated_by'];
                     $resultSets[$key]['status']=$data['status'];
                    $resultSets[$key]['group_users']=\app\modules\api\v1\models\GroupUser::findAll(['group_id' => $data['id'],'status'=>1]);
                    $resultSets[$key]['notification'] = \app\models\Notification::find()->select(['detail_id','user_id','user_name','mail_status','status'])
                       ->where(['detail_id' => $data['id']])
                       ->all();
                     endforeach;
       return $resultSets;
    }
    private function getLastUserUpdateTime($last_update_time = 000000) {
        return \app\models\UserModel::find()->where(['=', 'org_id', \Yii::$app->user->identity->org_id])->andWhere(['=', 'role', '13'])->max('updated_at');
    }

    private function getWbslistall($last_update_time = 000000) {
    $wbs=array();
        $org_id=\Yii::$app->user->identity->org_id;
                 $owner=\Yii::$app->user->identity->id;
         $wbs_list= \app\models\Wbs::find()
     ->joinWith(['wbsusers'])->where(['=', 'wbs_user.created_by', \Yii::$app->user->identity->id])
                ->andWhere("wbs.org_id ='".$org_id."'")               
    ->orderBy('wbs.id DESC')
                ->all();
       $resultSets = [];
        foreach($wbs_list as $key => $data):
                $wbs_user = \app\models\WbsUser::findAll(["wbs_id" => $data->id]);
                $resultSets[$key]['id'] = $data->id;
                $resultSets[$key]['owner_id'] = $data->owner_id;
                $resultSets[$key]['wbs_title'] = $data->wbs_title;
                $resultSets[$key]['start_date'] = $data->start_date;
                $resultSets[$key]['end_date'] = $data->end_date;
                $resultSets[$key]['wbs_group_id'] = $data->wbs_group_id;
                $resultSets[$key]['wbs_user_id'] = $data->wbs_user_id;
                $resultSets[$key]['status'] = $data->status;
                $resultSets[$key]['users'] = array_values($wbs_user);
               $resultSets[$key]['notification'] = \app\models\Notification::find()->select(['id','detail_id','user_id','user_name','mail_status','status'])
                       ->where(['detail_id' => $data->id])
                       ->andWhere(['notification_status'=>1])
                       ->andWhere(['not in', 'user_id', \Yii::$app->user->identity->id])
                       ->all();
       endforeach;
            return $resultSets;
    }

    private function getLastWbsUpdateTime($last_update_time = 000000) {
        return \app\models\Wbs::find()->where(['=', 'owner_id', \Yii::$app->user->identity->id])->max('updated_at');
    }
    
 private function getLastGroupUpdateTime($last_update_time = 000000) {
        return \app\modules\api\v1\models\UserGroup::find()->where(['=', 'created_by', \Yii::$app->user->identity->id])->max('updated_at');
    }

    private function getMeetinglist($last_update_time = 000000) {
          return \app\models\Meeting::find()
                ->joinWith(['meetingusers'])
                ->where(['or', 'meeting_user.user_id='.\Yii::$app->user->identity->id,'meeting.responsible_user_id=' . \Yii::$app->user->identity->id])
                ->orderBy('meeting.id DESC')
                ->all();
     }

    private function getLastMeetingUpdateTime($last_update_time = 000000) {
        return \app\models\MeetingUser::find()->where(['=', 'user_id', \Yii::$app->user->identity->id])->max('updated_at');
    }

    private function getActionlist($last_update_time = 000000) {
        return  \app\models\ActionPoint::find()
                ->joinWith(['actionusers'])
                ->where(['or', 'action_user.user_id='.\Yii::$app->user->identity->id,'action_point.action_assigned_by=' . \Yii::$app->user->identity->id])
                //->andWhere(['=', 'meeting_id', '0'])
                ->orderBy('action_point.id DESC')
                ->all();
        }

	public function actionAppid() {
        if (isset($this->data_json['user_id']) && $this->data_json['user_id'] != '') {
			$user_id=$this->data_json['user_id'];
			$appid = \app\models\AppDetail::find()->select(['id','user_id','org_id','imei_no','os_type','manufacturer_name','os_version','app_version','status'])->where(['user_id'=>$user_id,'status'=>1])->one();
			if ($appid == null) {
                throw new \yii\web\ForbiddenHttpException("Forbidden. App Id not found.");
            } else {
			    $this->response['data'] =  \app\models\AppDetail::find()->select(['id','user_id','org_id','imei_no','os_type','manufacturer_name','os_version','app_version','status'])->where(['user_id'=>$user_id,'status'=>1])->one();
            }
        } else {
            throw new \yii\web\BadRequestHttpException("Bad Request, App Id Not Found"); // HTTP Code 400
        }
        return $this->response;
    }
		public function actionAppversion() {
        if (isset($this->data_json['version_id']) && $this->data_json['version_id'] != '') {
			$version_id=$this->data_json['version_id'];
			$version_details=\app\models\AppVersion::find()->where(['id'=>$version_id,'status' => 1])->all();
			if ($version_details == null) {
                	$this->response['status'] = "0";
					$this->response['message'] = "App Version not found";
            } else {
				
			    $this->response['data'] =  $version_details;
				$this->response['version'] = $version_id;
				}
        } else {
			$this->response['status'] = "0";
			$this->response['message'] = "App Version not found";
        }
        return $this->response;
    }
}