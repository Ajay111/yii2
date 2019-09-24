<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


namespace app\entites;

use Yii;
use app\models\form\GroupForm;
use app\models\Group;
use app\models\Notification;
use app\services\NotificationService;
use \app\modules\api\v1\models\GroupUser;

class GroupEntity {
    /**
     * @var \app\models\form\GroupForm
     */
    public $form_model_group;

    /**
     * @var \app\models\Group
     */
    public $model_group;
    
    
    public function __construct($form_model_group = null, $model_group = null ,$group_id = null) {
        $this->form_model_group = $form_model_group;
        $this->model_group = new Group();
        if ($model_group != null) {
            $this->model_group = $model_group;
        } else if ($group_id != null) {
            $this->model_group = Group::findOne($group_id); 
        }
         if ($this->model_group != null)
            $this->model_group_original = Group::findOne($model_group->id);
}
    public function save() {
         $this->model_group = $this->form_model_group->group_model;
         $this->model_group->setAttributes([
            'users' => $this->form_model_group->users,
            'group_name' => $this->form_model_group->group_name,
	    'created_by' => \Yii::$app->user->identity->id,
             'status' => $this->form_model_group->status != '' ? $this->form_model_group->status : '0',
            
            // 'org_id'=>\Yii::$app->user->identity->org_id,
            ]);
        $this->model_group->org_id=\Yii::$app->user->identity->org_id;
         if ($this->validate()) {
            if ($this->model_group->save()) {
                $this->generateGroupUser();
                 $this->genrateNotification();
            }
            return true;
        } else {
            throw new \yii\web\BadRequestHttpException("Bad Request, GroupEntity didn't not validate"); // HTTP Code 400
        }
        return false;
    }
	public function exitFromGroup($group_model){
			\app\modules\api\v1\models\GroupUser::updateAll(['status' => 0], 'user_id ="' . \Yii::$app->user->identity->id . '" and group_id='. $this->model_group->id);
			$group_user = \app\modules\api\v1\models\GroupUser::findAll(["group_id" => $this->model_group->id,'status' => 1]);
			$users='';
			foreach ($group_user as $grp):
			$users.=$grp->user_id.',';
			endforeach;
			$users=rtrim($users,',');
			\app\models\Group::updateAll(['users' => $users], "id =" . $this->model_group->id);
			 $this->genrateExitGroupNotification();
		
	}
    public function generateGroupUser(){
        \app\modules\api\v1\models\GroupUser::updateAll(['status' => 0], "group_id =" . $this->model_group->id);
        
         if (!empty($this->model_group->users)) {
            $group_user=explode(',',$this->model_group->users);
            foreach ($group_user as $user_id) {
               $user_model = \app\modules\api\v1\models\User::find()->where(['id' => $user_id])->one();
               $group_user_model = \app\modules\api\v1\models\GroupUser::find()->where(['user_id' => $user_id, 'group_id' => $this->model_group->id])->one();
                if($group_user_model){
                    $status="1";
                   } else {
			$group_user_model= new \app\modules\api\v1\models\GroupUser();
                        $status="1";
                 }
                    $group_user_model->group_id=$this->model_group->id;
                    $group_user_model->user_id=$user_id;
                    $group_user_model->username=$user_model->username;
                    $group_user_model->email=$user_model->email;
                    $group_user_model->name=$user_model->name;
                    $group_user_model->org_id=$user_model->org_id;
                    $group_user_model->status=$status;
                    if($group_user_model->save()){
                      }
                    else{}
                }
        }
    }
    
    public function getDetail(){
        $action = \app\helpers\Utility::object_to_array($this->model_group);
        // $action['group_users'] = \app\modules\api\v1\models\User::find()->where(['IN', 'id',explode(',',$this->model_group->users )])->all();
        $action['group_users'] = \app\modules\api\v1\models\GroupUser::findAll(['group_id' => $this->model_group->id,'status'=>1]);  
        $action['notification'] = \app\models\Notification::find()->select(['notification.id','notification.message','notification.detail_id','notification.user_id','notification.user_name','notification.mail_status','notification.status'])
                         ->rightJoin('group_user', 'group_user.user_id = notification.user_id and group_user.group_id=notification.detail_id')
                          ->where(['notification.detail_id' => $this->model_group->id])
                        ->andWhere(['notification.notification_status'=>1])
                     //   ->andWhere(['not in', 'user_id', \Yii::$app->user->identity->id])
                       ->all();
        
//        $action['notification'] = \app\models\Notification::find()->select(['notification.id','notification.detail_id','notification.user_id','notification.user_name','notification.mail_status','notification.status'])
//                ->rightJoin('action_user', 'action_user.user_id = notification.user_id and action_user.action_id=notification.detail_id')
//               ->where(['notification.detail_id' => $this->model_action_point->id])
//               ->andWhere(['notification.notification_status'=>1])
//               ->andWhere(['action_user.status'=>1])
        return $action;
    }
    
	 public function genrateExitGroupNotification(){
		\app\models\Notification::updateAll(['notification_status' => 0], "detail_id =" . $this->model_group->id);
        $this->model_user_created_by = \app\modules\api\v1\models\User::findOne($this->model_group->created_by);
        $this->model_user_updated_by = \app\modules\api\v1\models\User::findOne($this->model_group->updated_by);
		if (isset($this->model_group_original) && isset($this->model_group_original->id)) {
            $selected_users_ids =  \app\modules\api\v1\models\GroupUser::findAll(['group_id' => $this->model_group->id]); 
			if($selected_users_ids){
                $status=false;
                foreach ($selected_users_ids as $group_user_model) {
					//print_r($group_user_model);die;
		            if($group_user_model->user_id==\Yii::$app->user->identity->id){
					
                    }
               else{
				    $notfication_service = new NotificationService($group_user_model->user_id, Notification::NOTIFICATION_TYPE_GROUP,Notification::NOTIFICATION_SUB_TYPE_GROUP_EXIT_OTHER);
                    $notfication_service->sendGroupNotification($this,\Yii::$app->user->identity->id);
                 } 
                }
                $notfication_service = new NotificationService($this->model_group_original->created_by, Notification::NOTIFICATION_TYPE_GROUP,Notification::NOTIFICATION_SUB_TYPE_GROUP_EXIT_SELF);
                 $notfication_service->sendGroupNotification($this,$this->model_group_original->created_by);
            }
        }
       
	} 
    public function genrateNotification() {
         \app\models\Notification::updateAll(['notification_status' => 0], "detail_id =" . $this->model_group->id);
        
        $this->model_user_created_by = \app\modules\api\v1\models\User::findOne($this->model_group->created_by);
        $this->model_user_updated_by = \app\modules\api\v1\models\User::findOne($this->model_group->updated_by);
           if (isset($this->model_group_original) && isset($this->model_group_original->id)) {
            $selected_users_ids = \app\modules\api\v1\models\User::find()->where(['IN', 'id',explode(',',$this->model_group->users )])->all();
            if($this->model_group->status==0){
                $status=false;
                foreach ($selected_users_ids as $group_user_model) {
                    if($group_user_model->id==\Yii::$app->user->identity->id){
                    $notfication_service = new NotificationService($group_user_model->id, Notification::NOTIFICATION_TYPE_GROUP,Notification::NOTIFICATION_SUB_TYPE_GROUP_REMOVED_SELF);
                    $notfication_service->sendGroupNotification($this,$this->model_group_original->created_by);
                    $status=true;
                    }
               else{
                    $notfication_service = new NotificationService($group_user_model->id, Notification::NOTIFICATION_TYPE_GROUP,Notification::NOTIFICATION_SUB_TYPE_GROUP_REMOVED_OTHER);
                    $notfication_service->sendGroupNotification($this,$this->model_group_original->created_by);
                 } 
                }
                if($status==0){
                    $notfication_service = new NotificationService(\Yii::$app->user->identity->id, Notification::NOTIFICATION_TYPE_GROUP,Notification::NOTIFICATION_SUB_TYPE_GROUP_REMOVED_SELF);
                    $notfication_service->sendGroupNotification($this,$this->model_group_original->created_by);
                 }
            }else{
                $status=false;
                foreach ($selected_users_ids as $group_user_model) {
                    if($group_user_model->id==\Yii::$app->user->identity->id){
                         $notfication_service = new NotificationService($group_user_model->id, Notification::NOTIFICATION_TYPE_GROUP,Notification::NOTIFICATION_SUB_TYPE_GROUP_UPDATED_SELF);
                         $notfication_service->sendGroupNotification($this,$this->model_group_original->created_by);
                         $status=true;
                         }
                    else{
                         $notfication_service = new NotificationService($group_user_model->id, Notification::NOTIFICATION_TYPE_GROUP,Notification::NOTIFICATION_SUB_TYPE_GROUP_UPDATED_OTHER);
                         $notfication_service->sendGroupNotification($this,$this->model_group_original->created_by);
                      } 
                     }
                     if($status==0){
                         $notfication_service = new NotificationService(\Yii::$app->user->identity->id, Notification::NOTIFICATION_TYPE_GROUP,Notification::NOTIFICATION_SUB_TYPE_GROUP_UPDATED_SELF);
                         $notfication_service->sendGroupNotification($this,$this->model_group_original->created_by);

                     }
                 }
        }
        else{  
            $selected_users_ids = explode(",", $this->form_model_group->users);
            $status=false;
            foreach ($selected_users_ids as $group_user_model) {
                if($group_user_model==\Yii::$app->user->identity->id){
                    $notfication_service = new NotificationService($group_user_model, Notification::NOTIFICATION_TYPE_GROUP,Notification::NOTIFICATION_SUB_TYPE_GROUP_CREATED_SELF);
                    $notfication_service->sendGroupNotification($this,$this->model_user_created_by->id);
                    $status=true;
                }else{
                    $notfication_service = new NotificationService($group_user_model, Notification::NOTIFICATION_TYPE_GROUP,Notification::NOTIFICATION_SUB_TYPE_GROUP_CREATED_OTHER);
                    $notfication_service->sendGroupNotification($this,$this->model_user_created_by->id);
                }
                }
                if($status==0){
                     $notfication_service = new NotificationService(\Yii::$app->user->identity->id, Notification::NOTIFICATION_TYPE_GROUP,Notification::NOTIFICATION_SUB_TYPE_GROUP_CREATED_SELF);
                    $notfication_service->sendGroupNotification($this,$this->model_user_created_by->id);
                }  
        }
}
     public function validate() {
        return $this->model_group->validate();
    }

    
}
