<?php

namespace app\entites;

use Yii;
use app\models\form\WbsForm;
use app\models\Wbs;
use app\models\WbsUser;
use app\models\Group;
use app\models\Notification;
use app\services\NotificationService;


class WbsEntity {

    /**
     * @var \app\models\form\WbsForm
     */
    public $form_model_wbs;

    /**
     * @var \app\models\Wbs
     */
    public $model_wbs;

    public function __construct($form_model_wbs = null, $model_wbs = null, $wbs_id = null) {
        $this->form_model_wbs = $form_model_wbs;
        $this->model_wbs = new Wbs();

        if ($model_wbs != null) {
            $this->model_wbs = $model_wbs;
        } else if ($wbs_id != null) {
            $this->model_wbs = Wbs::findOne($wbs_id);
        }
        if($model_wbs != null)
             $this->model_wbs_original = Wbs::findOne($model_wbs->id);
    }

    public function save() {
        $this->model_wbs = $this->form_model_wbs->wbs;
        $this->model_wbs->setAttributes([
            'wbs_title' => $this->form_model_wbs->wbs_title,
            'start_date' => $this->form_model_wbs->start_date,
            'end_date' => $this->form_model_wbs->end_date,
            'end_date' => $this->form_model_wbs->end_date,
            'wbs_group_id' => $this->form_model_wbs->wbs_group_id,
            'wbs_user_id' => $this->form_model_wbs->wbs_user_id,
            'status' => $this->form_model_wbs->status != '' ? $this->form_model_wbs->status : '1',
         //   'status' =>$this->form_model_wbs->status,
         ]);
        if ($this->model_wbs->isNewRecord) {
            $this->model_wbs->owner_id = \Yii::$app->user->identity->id;
        }
        if ($this->validate()) {
            if ($this->model_wbs->save()) {
                $this->assign_user_to_wbs();
            }
            $this->genrateNotification();
           
            return true;
        } else {
            throw new \yii\web\BadRequestHttpException("Bad Request, WbsEntity didn't not validate"); // HTTP Code 400
        }
        return false;
    }
    
    //***************  Assign User To WBS
    private function assign_user_to_wbs() {
            WbsUser::updateAll(['status' => 0], "wbs_id =" . $this->model_wbs->id);
            if (!empty($this->form_model_wbs->wbsuser)) {
                foreach ($this->form_model_wbs->wbsuser as $user_id) {
                    $wbs_user_model = WbsUser::find()->where(['user_id' => $user_id, 'wbs_id' => $this->model_wbs->id])->one();
                    if (empty($wbs_user_model)) {
                        $wbs_user_model = new \app\models\WbsUser();
                        //$wbs_user_model->availability = $this->model_wbs->check_availability;
                    } else {
                        //$wbs_user_model->availability = $this->model_wbs->check_availability;
                    }

                    $wbs_user_model->wbs_id = $this->model_wbs->id;
                    $wbs_user_model->user_id = $user_id;
                    $wbs_user_model->status = Wbs::STATUS_WBS_USER_ACTIVE;
                    if ($wbs_user_model->save()) {

                    } else {

                    }
                }
            }      
    }

    public function validate() {
        return $this->model_wbs->validate();
    }

    public function getDetail() {
        return \app\models\WbsUser::findAll(["wbs_id" => $this->model_wbs->id]);
         
    }
    public function getwbs(){

        $wbs_list = \app\helpers\Utility::object_to_array($this->model_wbs);

         $wbs_list['wbs_list'] = \app\models\WbsUser::findAll(["wbs_id" => $this->model_wbs->id]);
		 return $wbs_list;
        }
    public function genrateNotification() {
        \app\models\Notification::updateAll(['notification_status' => 0], "detail_id =" . $this->model_wbs->id);
         if (isset($this->model_wbs_original) && isset($this->model_wbs_original->id)) {
             if($this->model_wbs->status == 0){
                   $status=false;
                 foreach ($this->model_wbs->wbsusers as $action_user_model) {
                        if($action_user_model->user_id==\Yii::$app->user->identity->id){
                            $status=true;
                            $notfication_service = new NotificationService($action_user_model->user_id, Notification::NOTIFICATION_TYPE_CALENDER,Notification::NOTIFICATION_SUB_TYPE_WBS_REMOVED_SELF);
                            $notfication_service->sendWbsNotification($this,$this->model_wbs_original->created_by);
                        } else{
                            $notfication_service = new NotificationService($action_user_model->user_id, Notification::NOTIFICATION_TYPE_CALENDER,Notification::NOTIFICATION_SUB_TYPE_WBS_REMOVED_OTHER);
                            $notfication_service->sendWbsNotification($this,$this->model_wbs_original->created_by);    
                        }
                        }if($status==0){
                            $notfication_service = new NotificationService(\Yii::$app->user->identity->id, Notification::NOTIFICATION_TYPE_CALENDER,Notification::NOTIFICATION_SUB_TYPE_WBS_REMOVED_SELF);
                            $notfication_service->sendWbsNotification($this,$this->model_wbs_original->created_by);
                            }
                }
            else{  $status=false;
                foreach ($this->model_wbs->wbsusers as $action_user_model) {
                     if($action_user_model->user_id==\Yii::$app->user->identity->id){
                          $status=true;
                          $notfication_service = new NotificationService($action_user_model->user_id, Notification::NOTIFICATION_TYPE_CALENDER,Notification::NOTIFICATION_SUB_TYPE_WBS_UPDATED_SELF);
                          $notfication_service->sendWbsNotification($this);
                          }else{
                              $notfication_service = new NotificationService($action_user_model->user_id, Notification::NOTIFICATION_TYPE_CALENDER,Notification::NOTIFICATION_SUB_TYPE_WBS_UPDATED_OTHER);
                              $notfication_service->sendWbsNotification($this,$action_user_model->created_by);
                          }
                }if($status==0){
                      $notfication_service = new NotificationService(\Yii::$app->user->identity->id, Notification::NOTIFICATION_TYPE_CALENDER,Notification::NOTIFICATION_SUB_TYPE_WBS_UPDATED_SELF);
                      $notfication_service->sendWbsNotification($this);
                  }
            }
        }else
         {   $status=false;
             foreach ($this->model_wbs->wbsusers as $action_user_model) {
                 if($action_user_model->user_id==\Yii::$app->user->identity->id){
                        $status=true;
                        $notfication_service = new NotificationService($action_user_model->user_id, Notification::NOTIFICATION_TYPE_CALENDER,Notification::NOTIFICATION_SUB_TYPE_WBS_CREATED_SELF);
                        $notfication_service->sendWbsNotification($this,\Yii::$app->user->identity->id);
                 }else{
                        $notfication_service = new NotificationService($action_user_model->user_id, Notification::NOTIFICATION_TYPE_CALENDER,Notification::NOTIFICATION_SUB_TYPE_WBS_CREATED_OTHER);
                        $notfication_service->sendWbsNotification($this,$this->model_wbs->created_by);
                 }
                }if($status==0){
                    $notfication_service = new NotificationService(\Yii::$app->user->identity->id, Notification::NOTIFICATION_TYPE_CALENDER,Notification::NOTIFICATION_SUB_TYPE_WBS_CREATED_SELF);
                    $notfication_service->sendWbsNotification($this,\Yii::$app->user->identity->id);
                }
        }
         }
         

}
