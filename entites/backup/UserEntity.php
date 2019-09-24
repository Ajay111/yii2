<?php

namespace app\entites;

use Yii;
use app\models\form\WbsForm;
use app\models\Wbs;

class UserEntity{
    public $model_user;

    public function __construct($user_id = null) {
        $this->model_user = \app\models\User::findIdentity($user_id);
    }

    public function getMeetinglist($last_update_time = 000000000, $start_date=null,$end_date=null, $full_detail=FALSE) {
        return \app\models\Meeting::find()->joinWith(['meetingusers'])->where(['=', 'meeting_user.user_id', \Yii::$app->user->identity->id])->all();
//return \app\models\Wbs::find()->select(['id', 'owner_id', 'wbs_title', 'start_date', 'end_date', 'status'])->where(['=', 'owner_id', \Yii::$app->user->identity->id])->andWhere(['>', 'updated_at', $last_update_time])->orderBy('updated_at asc')->all();    
    }
    
    public function getMeetingDetail($meeting_id) {
        return \app\models\Meeting::find()->joinWith(['meetingusers'])->where(['=', 'meeting_user.user_id', \Yii::$app->user->identity->id])->all();
        //return \app\models\Wbs::find()->select(['id', 'owner_id', 'wbs_title', 'start_date', 'end_date', 'status'])->where(['=', 'owner_id', \Yii::$app->user->identity->id])->andWhere(['>', 'updated_at', $last_update_time])->orderBy('updated_at asc')->all();    
    }
    
   public static function genrateNotification($user_addded,$added=1) {

        $all_user = \app\models\UserModel::find()->where(['=', 'org_id', \Yii::$app->user->identity->org_id])->andWhere(['=', 'role', '13'])->andWhere(['=', 'status', '1'])->all();

        foreach ($all_user as $user) {
            $notification = new \app\models\Notification();
            $notification->notification_type = \app\models\Notification::NOTIFICATION_TYPE_USER;
            $notification->notification_sub_type = \app\models\Notification::NOTIFICATION_SUB_TYPE_USER_ADD_EDIT;
            $notification->detail_id = 0;
            $notification->user_id = $user->id;
            $notification->app_id = $user->activeapp != NULL ? $user->activeapp->id : 0;
            $notification->visible = 0;
            $notification->content = "";
            $notification->message_title = "User";
            if($added)
                $notification->message = "A new user ".$user_addded->name." added.";
            else
                $notification->message = "User ".$user_addded->name." is updated.";

            if ($notification->save()) {
                UserEntity::SendNotification($notification);
            } else {
                echo "<pre>";
                print_r($notification->getErrors());
                exit;
            }
        }
    }

    public static function SendNotification($notification_model){
        try {

            $firbase_tocken = $notification_model->user->activeapp != NULL ? $notification_model->user->activeapp->firebase_token : '';
            if ($firbase_tocken != "") {
                $firebase = new \app\components\GoogleFirebase($notification_model, '1', '1');

                $response = $firebase->send($firbase_tocken);
                $response_result = json_decode($response);
            } else {
                $response_result = null;
            }
            $notification_model->cron_status = '1';
            $notification_model->send_count = ($notification_model->send_count + 1);
            $notification_model_detail = new \app\models\NotificationFirebaseDetail();
            $notification_model_detail->notification_id = $notification_model->id;
            if ($response_result == null) {
                $notification_model->status = 0;
                $notification_model_detail->firebase_message = "Not Registrated";
            } else {
                if ($response_result->success) {
                    $notification_model->status = 1;
                    $notification_model_detail->firebase_id = isset($response_result->results[0]->message_id) ? $response_result->results[0]->message_id : '';
                } else {
                    $notification_model->status = 0;
                    $notification_model_detail->firebase_message = isset($response_result->results[0]->error) ? $response_result->results[0]->error : '';
                }
            }
            if ($notification_model->update()) {
                
            } else {
                print_r($notification_model->getErrors());
            }
            if ($notification_model_detail->save()) {
                
            } else {
                print_r($notification_model_detail->getErrors());
            }
            return TRUE;
        } catch (\Exception $e) {

            \app\components\Techteammail::send($e->getMessage(), ' Send Notification To Member');
        }
    }
}