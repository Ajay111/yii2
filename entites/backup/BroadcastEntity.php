<?php

namespace app\entites;

use Yii;
use app\models\form\BroadcastForm;
use app\models\Broadcast;
use app\models\Group;
use app\models\BroadcastUser;
use app\models\Notification;
use app\modules\api\v1\models\GroupUser;
use app\services\NotificationService;


class BroadcastEntity {

    public $form_model_broadcast;

    public $model_broadcast;
    public $model_broadcast_original;

    public function __construct($form_model_broadcast = null, $model_broadcast = null) {
        $this->form_model_broadcast = $form_model_broadcast;
        $this->model_broadcast = new Broadcast();

        if ($model_broadcast != null) {
             $this->model_broadcast = $model_broadcast;
        } else if ($model_broadcast != null) {
            $this->model_broadcast = Broadcast::findOne($model_broadcast);
        }
        if($model_broadcast != null)
             $this->model_broadcast_original = Broadcast::findOne($model_broadcast->id);
    }

    public function save() {
        $this->model_broadcast = $this->form_model_broadcast->broadcast;
        $this->model_broadcast->setAttributes([
                'broadcast_message_title' => $this->form_model_broadcast->broadcast_message_title,
                'broadcast_message' => $this->form_model_broadcast->broadcast_message,
                'notification_type' => $this->form_model_broadcast->notification_type,
                'broadcast_group_id' => $this->form_model_broadcast->broadcast_group_id != '' ? $this->form_model_broadcast->broadcast_group_id : '0',
                'broadcast_user_id' => $this->form_model_broadcast->broadcast_user_id != '' ? $this->form_model_broadcast->broadcast_user_id : '0',
                'status' => $this->form_model_broadcast->status != '' ? $this->form_model_broadcast->status : '1',
              'notification_sub_type' => $this->form_model_broadcast->notification_sub_type != '' ? $this->form_model_broadcast->notification_sub_type : 0,
               // 'status' => $this->form_model_broadcast->status != '' ? $this->form_model_broadcast->status : '1',
         ]);
      //  $org_id=\Yii::$app->user->identity->org_id;
     // print_r($this->model_broadcast->attributes);die;
       if ($this->validate()) {
           
            if ($this->model_broadcast->save()) {
              
                $this->assign_user_to_broadcast();
            }
            $this->genrateNotification();
           
            return true;
        } else {
            //throw new \yii\web\BadRequestHttpException("Bad Request, WbsEntity didn't not validate"); // HTTP Code 400
        }
        return false;
    }
      public function getDetail(){
        $action = \app\helpers\Utility::object_to_array($this->model_broadcast);
        $action['group_users'] = \app\models\BroadcastUser::findAll(['brodecast_id' => $this->model_broadcast->id,'status'=>1]);  
        $action['notification'] = \app\models\Notification::find()->select(['notification.id','notification.message','notification.detail_id','notification.user_id','notification.user_name','notification.mail_status','notification.status'])
                    ->rightJoin('broadcast_user', 'broadcast_user.user_id = notification.user_id and broadcast_user.brodecast_id=notification.detail_id')
                    ->where(['notification.detail_id' => $this->model_broadcast->id])
                    ->andWhere(['notification.notification_status'=>1])
                    ->andWhere(['broadcast_user.status'=>1])
                       ->all();
        
    return $action;
    }
     private function assign_user_to_broadcast() {
         \app\models\BroadcastUser::updateAll(['status' => 0], "brodecast_id =" . $this->model_broadcast->id);
            if (!empty($this->form_model_broadcast->muser)) {
                foreach ($this->form_model_broadcast->muser as $user_id) {
                    $brodecast_user_model = \app\models\BroadcastUser::find()->where(['user_id' => $user_id, 'brodecast_id' => $this->model_broadcast->id])->one();
                    if (empty($brodecast_user_model)) {
                        $brodecast_user_model = new \app\models\BroadcastUser();
                       $status='1';
                    } else {
                       // $status=$brodecast_user_model->status;
                        //$wbs_user_model->availability = $this->model_wbs->check_availability;
                    }

                    $brodecast_user_model->brodecast_id = $this->model_broadcast->id;
                    $brodecast_user_model->user_id = $user_id;
                    $brodecast_user_model->status = $status;
                 //   print_r($brodecast_user_model->save());die;
                    if ($brodecast_user_model->save()) {

                    } else {

                    }
                }
            }  
         
     }
    public function validate() {
        return $this->model_broadcast->validate();
    }

     public function genrateNotification() {
         \app\models\Notification::updateAll(['notification_status' => 0], "detail_id =" . $this->model_broadcast->id);
         if (isset($this->model_broadcast_original) && isset($this->model_broadcast_original->id)) {
             
        }else
         {  
            if($this->form_model_broadcast->notification_type==1){
             $status=false;
             foreach ($this->model_broadcast->broadcastusers as $action_user_model) {
                 if($action_user_model->user_id==\Yii::$app->user->identity->id){
                       $notfication_service = new NotificationService($action_user_model->user_id, Notification::NOTIFICATION_TYPE_BROADCAST_MEETING,Notification::NOTIFICATION_SUB_TYPE_BROADCAST_MEETING_CREATED);
                        $notfication_service->sendBroadcastNotification($this,\Yii::$app->user->identity->id);
                 }else{
                        $notfication_service = new NotificationService($action_user_model->user_id, Notification::NOTIFICATION_TYPE_BROADCAST_MEETING,Notification::NOTIFICATION_SUB_TYPE_BROADCAST_MEETING_CREATED);
                        $notfication_service->sendBroadcastNotification($this);
                 }
                }if($status==0){
                    $notfication_service = new NotificationService(\Yii::$app->user->identity->id, Notification::NOTIFICATION_TYPE_BROADCAST_MEETING,Notification::NOTIFICATION_SUB_TYPE_BROADCAST_MEETING_CREATED);
                    $notfication_service->sendBroadcastNotification($this,\Yii::$app->user->identity->id);
                }
             
         }else if($this->form_model_broadcast->notification_type==2){
             $status=false;
             foreach ($this->model_broadcast->broadcastusers as $action_user_model) {
                 if($action_user_model->user_id==\Yii::$app->user->identity->id){
                     $status=true;
                       $notfication_service = new NotificationService($action_user_model->user_id, Notification::NOTIFICATION_TYPE_BROADCAST_ACTION,Notification::NOTIFICATION_SUB_TYPE_BROADCAST_ACTION_CREATED);
                        $notfication_service->sendBroadcastNotification($this,\Yii::$app->user->identity->id);
                 }else{
                        $notfication_service = new NotificationService($action_user_model->user_id, Notification::NOTIFICATION_TYPE_BROADCAST_ACTION,Notification::NOTIFICATION_SUB_TYPE_BROADCAST_ACTION_CREATED);
                        $notfication_service->sendBroadcastNotification($this);
                 }
                }if($status==0){
                    $notfication_service = new NotificationService(\Yii::$app->user->identity->id, Notification::NOTIFICATION_TYPE_BROADCAST_ACTION,Notification::NOTIFICATION_SUB_TYPE_BROADCAST_ACTION_CREATED);
                    $notfication_service->sendBroadcastNotification($this,\Yii::$app->user->identity->id);
                }
         }
         else if($this->form_model_broadcast->notification_type==3){
             $status=false;
             
             foreach ($this->model_broadcast->broadcastusers as $action_user_model) {
                 if($action_user_model->user_id==\Yii::$app->user->identity->id){
                       $notfication_service = new NotificationService($action_user_model->user_id, Notification::NOTIFICATION_TYPE_BROADCAST_WBS,Notification::NOTIFICATION_SUB_TYPE_BROADCAST_WBS_CREATED);
                        $notfication_service->sendBroadcastNotification($this,\Yii::$app->user->identity->id);
                 }else{
                        $notfication_service = new NotificationService($action_user_model->user_id, Notification::NOTIFICATION_TYPE_BROADCAST_WBS,Notification::NOTIFICATION_SUB_TYPE_BROADCAST_WBS_CREATED);
                        $notfication_service->sendBroadcastNotification($this);
                 }
                }if($status==0){
                    $notfication_service = new NotificationService(\Yii::$app->user->identity->id, Notification::NOTIFICATION_TYPE_BROADCAST_WBS,Notification::NOTIFICATION_SUB_TYPE_BROADCAST_WBS_CREATED);
                    $notfication_service->sendBroadcastNotification($this,\Yii::$app->user->identity->id);
                }
         }
            
        }
         }
         

}
