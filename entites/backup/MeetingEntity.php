<?php

namespace app\entites;
use Yii;
use app\models\form\WbsForm;
use app\models\Wbs;
use app\models\Meeting;
use app\models\MeetingUser;
use app\models\MeetingReoccurring;
use app\models\MeetingReoccurringUser;
use app\models\Notification;
use app\services\NotificationService;
use app\entites\MeetingReoccurringEntity;

class MeetingEntity {

    /**
     * @var \app\models\form\MeetingForm
     */
    public $form_model_meeting;

    /**
     * @var \app\models\Meeting
     */
    public $model_meeting_original;

    /**
     * @var \app\models\Meeting
     */
    public $model_meeting;
    public $model_user_created_by;
    public $model_user_responsible;
    public $model_user_updated_by;
    public $model_user_created_by_behalf_of;

    public function __construct($form_model_meeting = null, $model_meeting = null) {
        $this->form_model_meeting = $form_model_meeting;
        $this->model_meeting = new Meeting();

        if ($model_meeting != null) {
            $this->model_meeting = $model_meeting;
        }

        if ($model_meeting != null)
            $this->model_meeting_original = Meeting::findOne($model_meeting->id);
    }

    public function auto_genrate_reoccur() {
        $this->model_meeting = new Meeting();
        $meeting_reoccur = $this->model_meeting_original->meetingreoccurring;
        $this->model_meeting->setAttributes([
            'org_id' => $this->model_meeting_original->org_id,
            'origin_source' => $this->model_meeting_original->origin_source,
            'reoccurring_type' => $this->model_meeting_original->reoccurring_type,
            'reoccurring_weekday' => $this->model_meeting_original->reoccurring_weekday,
            'reoccurring_day' => $this->model_meeting_original->reoccurring_day,
            'meeting_reoccurring_id' => $this->model_meeting_original->meeting_reoccurring_id,
            'responsible_user_id' => $this->model_meeting_original->responsible_user_id!='' ? $this->model_meeting_original->responsible_user_id:0,
            'meeting_reoccurring_sno' => $this->model_meeting_original->meeting_reoccurring_sno + 1,
            'parent_id' => $this->model_meeting_original->id,
            'meeting_name' => $this->model_meeting_original->meeting_name,
            'client_name' => $this->model_meeting_original->client_name,
            'wbs_id' => $this->model_meeting_original->wbs_id,
            'meeting_type' => $this->model_meeting_original->meeting_type,
            'agenda' => $this->model_meeting_original->agenda,
            //    'start_datetime' => $this->form_model_meeting->startdatetime(),
            //    'end_datetime' => $this->form_model_meeting->enddatetime(),
            'check_availability' => $this->model_meeting_original->check_availability,
            'meeting_group_id' => $this->model_meeting_original->meeting_group_id != '' ?$this->model_meeting_original->meeting_group_id : '0',
            'meeting_user_id' => $this->model_meeting_original->meeting_user_id != '' ? $this->model_meeting_original->meeting_user_id : '0',
          'status' => $this->model_meeting_original->status != '' ? $this->model_meeting_original->status : 0,
        ]);
         switch ($this->model_meeting_original->reoccurring_type) {
            case Meeting::MEETING_REOCCUR_DAILY:
                $start_datetime_stamp = strtotime("+ 1 days", strtotime($this->model_meeting_original->start_datetime));
                $end_datetime_stamp = strtotime("+ 1 days", strtotime($this->model_meeting_original->end_datetime));
                break;
            case Meeting::MEETING_REOCCUR_WEEKLY:
                $start_datetime_stamp = strtotime("+ 1 Week", strtotime($this->model_meeting_original->start_datetime));
                $end_datetime_stamp = strtotime("+ 1 Week", strtotime($this->model_meeting_original->end_datetime));
                break;
            case Meeting::MEETING_REOCCUR_MONTHLY:
                $start_datetime_stamp = strtotime("+ 1 Month", strtotime($this->model_meeting_original->start_datetime));
                $end_datetime_stamp = strtotime("+ 1 Month", strtotime($this->model_meeting_original->end_datetime));
                break;
            default:
                $end_datetime_stamp = $this->model_meeting_original->end_datetime;
                $start_datetime_stamp =$this->model_meeting_original->start_datetime;
                break;
        }
        $this->model_meeting->start_datetime = date("Y-m-d H:i:s", $start_datetime_stamp);
        $this->model_meeting->end_datetime = date("Y-m-d H:i:s", $end_datetime_stamp);
        if ($this->validate()) {
            $this->model_meeting->updated_by = 0;
            if ($this->model_meeting->save()) {
                $this->model_meeting->org_id = $this->model_meeting_original->org_id;
                $this->model_meeting->created_by = $this->model_meeting_original->created_by;
                $this->model_meeting->meeting_reoccurring_status = $this->model_meeting_original->meeting_reoccurring_status;
                $this->model_meeting->responsible_user_id = $this->model_meeting_original->created_by;
                $this->model_meeting->update();
                $this->model_meeting_original->next_meeting_id = $this->model_meeting->id;
                $this->model_meeting_original->update();
                $this->form_model_meeting = new \app\models\form\MeetingForm();
                $meeting_user_model_array = MeetingUser::find()->where(['meeting_id' => $this->model_meeting_original->id, 'status' => 1])->all();
                $this->form_model_meeting->muser = \yii\helpers\ArrayHelper::getColumn($meeting_user_model_array, 'user_id');
                $this->model_meeting_original = null;
               // print_r($this->form_model_meeting->getAttributes());
                $this->assign_user_to_meeting();
                $this->genrateNotificationReaccur();
                return true;
            } else {
                echo "<pre/>";
                print_r($this->model_meeting->getErrors());
                exit;
            }
        } else {
            print_r($this->model_meeting->errors);
            exit;
            // throw new \yii\web\BadRequestHttpException("Bad Request, MeetingEntity didn't not validate"); // HTTP Code 400
            print_r($meeting_reoccur);
        }
        return FALSE;
    }

    public function save() {
        $save_type = "";
        $this->model_meeting = $this->form_model_meeting->meeting;
        $this->model_meeting->setAttributes([
            'origin_source' => $this->form_model_meeting->origin_source,
            'meeting_name' => $this->form_model_meeting->meeting_name,
            'client_name' => $this->form_model_meeting->client_name,
            'wbs_id' => $this->form_model_meeting->wbs_id != '' ? $this->form_model_meeting->wbs_id : '0',
            'responsible_user_id' => $this->form_model_meeting->responsible_user_id!='' ? $this->form_model_meeting->responsible_user_id:'',
            'meeting_type' => $this->form_model_meeting->wbs_type != '' ? $this->form_model_meeting->wbs_type : '0',
            'agenda' => $this->form_model_meeting->agenda,
            'start_datetime' => $this->form_model_meeting->startdatetime(),
            'end_datetime' => $this->form_model_meeting->enddatetime(),
            'check_availability' => $this->form_model_meeting->check_availability,
            'meeting_group_id' => $this->form_model_meeting->meeting_group_id != '' ?$this->form_model_meeting->meeting_group_id : '0',
            'meeting_user_id' => $this->form_model_meeting->meeting_user_id != '' ? $this->form_model_meeting->meeting_user_id : '0',
            'status' =>$this->form_model_meeting->status != '' ? $this->form_model_meeting->status : 0,
            
            'created_by_behalf_of' => $this->form_model_meeting->created_by_behalf_of != '' ?$this->form_model_meeting->created_by_behalf_of : 0,
            'updated_by_behalf_of' => $this->form_model_meeting->updated_by_behalf_of != '' ? $this->form_model_meeting->updated_by_behalf_of : 0,
            'behalf_of_status' =>$this->form_model_meeting->behalf_of_status != '' ? $this->form_model_meeting->behalf_of_status : 0,
         
        ]);
    //   print_r($this->form_model_meeting->created_by_behalf_of);die;
        if ($this->validate()) {

            if (isset($this->model_meeting_original) && isset($this->model_meeting_original->id)) {}
            else{
                    $meeting_clash_id = $this->clashCheckForCreator();
                    if ($meeting_clash_id != "") {
                    if (($this->model_meeting_original == null) or ( $this->model_meeting_original->id != $meeting_clash_id)) {
                        $meeting_model_temp = Meeting::findOne($meeting_clash_id);
                        throw new \yii\web\BadRequestHttpException($meeting_model_temp->meeting_name . " is scheduled at chosen time. Please select a different time.");
                    }
                }
                $meeting_clash_created = $this->clashCheckForCreatored();
                if ($meeting_clash_created != "") {
                  if (($this->model_meeting_original == null) or ( $this->model_meeting_original->id != $meeting_clash_id)) {
                      $meeting_model_temp = Meeting::findOne($meeting_clash_created);
                      throw new \yii\web\BadRequestHttpException($meeting_model_temp->meeting_name . " is scheduled at chosen time. Please select a different time.");
                  }
              }
             }
            if ($this->model_meeting_original == null) {
                if ($this->form_model_meeting->reoccur == Meeting::MEETING_REOCCUR_ONETIME || $this->form_model_meeting->reoccur == 0) {
                    // Do Nothing
                    $this->model_meeting->reoccurring_type = $this->form_model_meeting->reoccur;
                } else {
                    $meeting_reoccuringentity = new MeetingReoccurringEntity($this->form_model_meeting, $this->model_meeting);
                    $meeting_reoccuringentity->save();

                    $this->model_meeting->meeting_reoccurring_id = $meeting_reoccuringentity->model_meeting_reoccurring->id;
                    $this->model_meeting->meeting_reoccurring_sno = 1;
                    $this->model_meeting->meeting_reoccurring_status = 1;

                    $this->model_meeting->reoccurring_type = $meeting_reoccuringentity->model_meeting_reoccurring->reoccurring_type;
                    $this->model_meeting->reoccurring_weekday = $meeting_reoccuringentity->model_meeting_reoccurring->reoccurring_weekday;
                    $this->model_meeting->reoccurring_day = $meeting_reoccuringentity->model_meeting_reoccurring->reoccurring_day;
                }
            } else {
                if ($this->form_model_meeting->reoccur == Meeting::MEETING_REOCCUR_STOPPED) {
                    $meeting_reoccuringentity = new MeetingReoccurringEntity($this->form_model_meeting, $this->model_meeting);
                    $meeting_reoccuringentity->save();
                    $meeting_reoccuringentity->cancel();
                    $this->model_meeting->meeting_reoccurring_status = 0;
                    $save_type = "reoccurstop";
                } else if ($this->form_model_meeting->reoccur == Meeting::MEETING_REOCCUR_ONETIME) {
                    if ($this->model_meeting_original->reoccurring_type == Meeting::MEETING_REOCCUR_ONETIME) {
                        // Do Nothing
                    } else {
                        // converted from re-occur to onetime
                        $meeting_reoccuringentity = new MeetingReoccurringEntity($this->form_model_meeting, $this->model_meeting);
                        $meeting_reoccuringentity->save();
                        $meeting_reoccuringentity->cancel();
                        $this->model_meeting->meeting_reoccurring_status = 0;
                    }
                    $this->model_meeting->reoccurring_type = $this->form_model_meeting->reoccur;
                    $this->model_meeting->reoccurring_weekday = 0;
                    $this->model_meeting->reoccurring_day = 0;
                    $this->model_meeting->meeting_reoccurring_status = 1;
                } else {

                    $meeting_reoccuringentity = new MeetingReoccurringEntity($this->form_model_meeting, $this->model_meeting);
                    $meeting_reoccuringentity->status = 1;
                    $meeting_reoccuringentity->save();

                    $this->model_meeting->reoccurring_type = $meeting_reoccuringentity->model_meeting_reoccurring->reoccurring_type;
                    $this->model_meeting->reoccurring_weekday = $meeting_reoccuringentity->model_meeting_reoccurring->reoccurring_weekday;
                    $this->model_meeting->reoccurring_day = $meeting_reoccuringentity->model_meeting_reoccurring->reoccurring_day;
                    $this->model_meeting->meeting_reoccurring_status = 1;
                }
            }
            
            if ($this->model_meeting->save()) {
                $this->assign_user_to_meeting();
                $this->genrateNotification($save_type);
                return true;
            } else {
                echo "<pre/>";
                print_r($this->model_meeting->getErrors());
                exit;
            }
            return false;
        } else {
            print_r($this->model_meeting->errors);
            throw new \yii\web\BadRequestHttpException("Bad Request, MeetingEntity didn't not validate"); // HTTP Code 400
        }
        return false;
    }

    private function assign_user_to_meeting() {
        MeetingUser::updateAll(['status' => 0], "meeting_id =" . $this->model_meeting->id);
        //print_r($this->form_model_meeting->muser);
        if (!empty($this->form_model_meeting->muser)) {
            foreach ($this->form_model_meeting->muser as $user_id) {
                $meeting_user_model = MeetingUser::find()->where(['user_id' => $user_id, 'meeting_id' => $this->model_meeting->id])->one();
                if($meeting_user_model){
		    
                    $meeting_user_model_availibility = $meeting_user_model->availability;
                } else {
			$meeting_user_model = new \app\models\MeetingUser();
                    $meeting_user_model_availibility =  MeetingUser::MEETING_AVAILABILITY_STATUS_WAITING;
                }
               
                $meeting_user_model->meeting_id = $this->model_meeting->id;
                $meeting_user_model->user_id = $user_id;
		 $meeting_user_model->availability = $meeting_user_model_availibility;
                $meeting_user_model->status = Meeting::STATUS_MEETING_USER_ACTIVE;
                if ($meeting_user_model->save()) {
                    
                } else {
                    
                }
            }
        }
       }

    public function cancel() {
        $this->model_meeting->cancel_reason = $this->form_model_meeting->cancel_reason;
        $this->model_meeting->status = Meeting::MEETING_PROGRESS_STATUS_CANNCELED;
        if ($this->model_meeting->save()) {
            $this->genrateNotification("cancel");
            return true;
        }
        return false;
    }

    public function snooze($meeting_user_model) {
        //$this->model_meeting->cancel_reason = $this->form_model_meeting->cancel_reason;
        //$this->model_meeting->status = Meeting::MEETING_PROGRESS_STATUS_CANNCELED;
       // if ($this->model_meeting->responsible_user_id == $meeting_user_model->user_id || $this->model_meeting->created_by == $meeting_user_model->user_id) {
    if ($this->model_meeting->created_by == \Yii::$app->user->identity->id) {
        print_r('hello');die;
            $this->model_meeting->snooze = $this->model_meeting->snooze + 1;
            $this->model_meeting->snooze_time = $this->model_meeting->snooze_time + $this->form_model_meeting->snooze_time;
            $this->model_meeting->snooze_reason = $this->model_meeting->snooze_reason . " " . $this->model_meeting->snooze . ": " . $this->form_model_meeting->snooze_reason;

            $start_datetime = new \DateTime($this->model_meeting->start_datetime);
            $start_datetime->modify("+" . $this->form_model_meeting->snooze_time . " minutes");
            $start_datetime = ((array) $start_datetime);
            $this->model_meeting->start_datetime = \Yii::$app->formatter->asDatetime($start_datetime['date'], "php:Y-m-d H:i:s ");

            $end_datetime = new \DateTime($this->model_meeting->end_datetime);
            $end_datetime->modify("+" . $this->form_model_meeting->snooze_time . " minutes");
            $end_datetime = ((array) $end_datetime);
            $this->model_meeting->end_datetime = \Yii::$app->formatter->asDatetime($end_datetime['date'], "php:Y-m-d H:i:s ");

            if ($this->model_meeting->save()) {
                $this->genrateNotification("snooze");
                return true;
            }
        } else {
          print_r($meeting_user_model->user_id);die;
            $meeting_user_model = \app\models\MeetingUser::findOne(['user_id' => $user_id, 'status' => 1]);
            $meeting_user_model->snooze = $meeting_user_model->snooze + 1;
            $meeting_user_model->snooze_time = $this->form_model_meeting->snooze_time;
            $meeting_user_model->snooze_reason = $this->form_model_meeting->snooze_reason;
            if ($meeting_user_model->save()) {
                $this->genrateNotification("snooze_other", $meeting_user_model->user_id);
                return true;
            }
        }
        return false;
    }

    public function saveComplaint($form_model_complaint) {

        $complaint = new \app\models\MeetingComplaint();

        $complaint->meeting_id = $this->model_meeting->id;
        $complaint->wbs_id = $this->model_meeting->wbs_id;
        $complaint->reason = $form_model_complaint->reason;
        $complaint->root_cause = $form_model_complaint->root_cause;
        $complaint->complaint = $form_model_complaint->complaint;
        $complaint->corrective_action = $form_model_complaint->corrective_action;

        if ($complaint->save()) {
            $this->genrateNotification("complaint");
            return true;
        }
        return false;
    }

    public function saveOrder($form_model_order) {

        $order = new \app\models\MeetingOrder();

        $order->meeting_id = $this->model_meeting->id;
        $order->wbs_id = $this->model_meeting->wbs_id;
        $order->order_name = $form_model_order->order_name;
        $order->price = $form_model_order->price;
        $order->quantity = $form_model_order->quantity;
        $order->quality_expectation = $form_model_order->quality_expectation;
        $order->service_level_expectation = $form_model_order->service_level_expectation;

        if ($order->save()) {
            $this->genrateNotification("order");
            return true;
        }
        return false;
    }

    public function saveAcceptdecline($form_accept_decline) {
        
        $meeting_user = MeetingUser::findOne($form_accept_decline->meeting_user_model->id);
         //$meeting_user = MeetingUser::find()->where(['meeting_id' => $form_accept_decline->meeting_id, 'user_id' =>$form_accept_decline->meeting_user_model->id])->one();
        $meeting_user->availability = $form_accept_decline->availability;
      
        if ($meeting_user->availability == MeetingUser::MEETING_AVAILABILITY_STATUS__DECLINE) {
            $meeting_user->decline_reason = $form_accept_decline->decline_reason;
            if ($meeting_user->save()) {
                $this->genrateNotification("decline", $meeting_user->user_id);
                return true;
            }
        } else {
            $meeting_user->decline_reason = "";
            //  print_r($meeting_user->attributes);die;
            if ($meeting_user->save()) {
                $this->genrateNotification("accept", $meeting_user->user_id);
                return true;
            }
        }
        return false;
    }

    public function getDetail() {
        $meeting = \app\helpers\Utility::object_to_array($this->model_meeting);
        $meeting['users'] = \app\models\MeetingUser::findAll(["meeting_id" => $this->model_meeting->id,"status"=>1]);
        $meeting['action_points'] = array();
        $actions_temp = \app\models\ActionPoint::findAll(["meeting_id" => $this->model_meeting->id]);
         foreach ($actions_temp as $t) {
             $actionentity = new \app\entites\ActionPointEntity(null, $t);
             array_push($meeting['action_points'], $actionentity->getDetail());
         }
         $meeting['complaints'] = \app\models\MeetingComplaint::findAll(["meeting_id" => $this->model_meeting->id]);
         $meeting['orders'] = \app\models\MeetingOrder::findAll(["meeting_id" => $this->model_meeting->id]);
         
           $meeting['notification']= \app\models\Notification::find()->select(['notification.id','notification.message','notification.detail_id','notification.user_id','notification.user_name','notification.mail_status','notification.status'])
                ->rightJoin('meeting_user', 'meeting_user.user_id = notification.user_id and meeting_user.meeting_id=notification.detail_id')
               ->where(['notification.detail_id' => $this->model_meeting->id])
               ->andWhere(['notification.notification_status'=>1])
               ->andWhere(['meeting_user.status'=>1])
              //   ->createCommand();
               ->all();
         if ($this->model_meeting->wbs_id != 0)
             $meeting['wbs_detail'] = Wbs::findOne($this->model_meeting->wbs_id);
         if ($this->model_meeting->meeting_reoccurring_id != 0)
             $meeting['reoccurring_detail'] = MeetingReoccurring::findOne($this->model_meeting->meeting_reoccurring_id);
//         $meeting['group'] = \app\models\Group::find()->where(['IN', 'id',explode(',',$this->model_meeting->meeting_group_id )])->all();
//          $meeting['user'] = \app\modules\api\v1\models\User::find()->where(['IN', 'id',explode(',',$this->model_meeting->meeting_user_id )])->all();
         return $meeting;
    }

    public function validate() {
        return $this->model_meeting->validate();
    }
    public function clashCheckForCreatored() {
        $array1=['0'=>\Yii::$app->user->identity->id];
        $array1 = array_merge($array1,$this->form_model_meeting->muser);
        $array1 = array_unique($array1);
        $user= implode(",", $array1);
        $user = trim($user, ',');
        $connection = Yii::$app->getDb();
        $sql = $sql = "SELECT * FROM `meeting`"
                 . "WHERE created_by IN ($user) and ( ('" . $this->model_meeting->start_datetime . "' between `meeting`.`start_datetime` and `meeting`.`end_datetime`) or "
                . "('" . $this->model_meeting->end_datetime . "' between `meeting`.`start_datetime` and `meeting`.`end_datetime`) or  "
                . "('" . $this->model_meeting->start_datetime . "' <= `meeting`.`start_datetime` and  '" . $this->model_meeting->end_datetime . "' >= `meeting`.`end_datetime`) )"
                . "and `meeting`.status='1' ";
     //   echo $sql;
       // die;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        //throw new \yii\web\BadRequestHttpException($sql);
        foreach ($result as $r) {
            return $r['id'];
        }
        return "";
    }
	
    public function clashCheckForCreator() {
        $connection = Yii::$app->getDb();
        $sql = "SELECT  `meeting`.*,`meeting_user`.* FROM `meeting` inner join `meeting_user` on `meeting`.id = `meeting_user`.meeting_id "
                . "WHERE  `meeting_user`.`user_id` = '" . \Yii::$app->user->identity->id . "' and "
                . "( ('" . $this->model_meeting->start_datetime . "' between `meeting`.`start_datetime` and `meeting`.`end_datetime`) or "
                . "('" . $this->model_meeting->end_datetime . "' between `meeting`.`start_datetime` and `meeting`.`end_datetime`) or  "
                . "('" . $this->model_meeting->start_datetime . "' <= `meeting`.`start_datetime` and  '" . $this->model_meeting->end_datetime . "' >= `meeting`.`end_datetime`) )"
                . "and `meeting`.`status`='1' and `meeting_user`.`status` = '1' and `meeting_user`.`availability` != '3' ";
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        //throw new \yii\web\BadRequestHttpException($sql);
        foreach ($result as $r) {
            return $r['meeting_id'];
        }
        return "";
    }

    public function genrateNotificationReaccur($type = "", $activity_user_id = 0){
        \app\models\Notification::updateAll(['notification_status' => 0], "detail_id =" . $this->model_meeting->id);
        $this->model_user_created_by = \app\modules\api\v1\models\User::findOne($this->model_meeting->created_by);
        $this->model_user_updated_by = \app\modules\api\v1\models\User::findOne($this->model_meeting->updated_by);
        $this->model_user_responsible = \app\modules\api\v1\models\User::findOne($this->model_meeting->responsible_user_id);
        
        if(!empty($this->form_model_meeting->muser)){
            foreach ($this->form_model_meeting->muser as $meeting_user_model) {
                
                if($meeting_user_model==$this->model_meeting->created_by){
                    $notfication_service = new NotificationService($meeting_user_model, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_CREATED_SELF);
                    $notfication_service->sendMeetingNotification($this);
                }
                else {
                     $notfication_service = new NotificationService($meeting_user_model, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_CREATED_OTHER);
                    $notfication_service->sendMeetingNotification($this);
                }
               }
                 $notfication_service = new NotificationService($this->model_meeting->created_by, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_CREATED_SELF);
                 $notfication_service->sendMeetingNotification($this);
        }
    }
    public function genrateNotification($type = "", $activity_user_id = 0) {
       \app\models\Notification::updateAll(['notification_status' => 0], "detail_id =" . $this->model_meeting->id);
        $this->model_user_created_by = \app\modules\api\v1\models\User::findOne($this->model_meeting->created_by);
        
         $this->model_user_created_by_behalf_of = \app\modules\api\v1\models\User::findOne($this->model_meeting->created_by_behalf_of);
         
        $this->model_user_updated_by = \app\modules\api\v1\models\User::findOne($this->model_meeting->updated_by);
        $this->model_user_responsible = \app\modules\api\v1\models\User::findOne($this->model_meeting->responsible_user_id);
        if (isset($this->model_meeting_original) && isset($this->model_meeting_original->id)) {
            if ($type == "reoccurstop") {
                foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                    // reminder
                    $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_REOCCUR_STOPPED);
                    $notfication_service->sendMeetingNotification($this);

                }
            }
            if ($type == "reminder") {
                $status=false;
                foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                   if($meeting_user_model->user_id==$this->model_meeting->created_by){
                        $status=true;
                        $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_REMINDER_START);
                        $notfication_service->sendMeetingNotification($this,$this->model_meeting->created_by);
                     }
                     else{
                         $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_REMINDER_START);
                        $notfication_service->sendMeetingNotification($this,$this->model_meeting->created_by);
                     
                     }
                }
                    if($status==0){
                    $notfication_service = new NotificationService($this->model_user_created_by->id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_REMINDER_START);
                    $notfication_service->sendMeetingNotification($this,$this->model_meeting->created_by);
                    }
                
            } else if ($type == "cancel") {
                 $status=false;
                foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                    // cancel
                    if($meeting_user_model->user_id==Yii::$app->user->identity->id){
                    $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_CANCLED_SELF);
                    $notfication_service->sendMeetingNotification($this);
                     $status=true;
                    }
                    else{
                        $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_CANCLED_OTHER);
                        $notfication_service->sendMeetingNotification($this);
                   }
                   }
                   if($status==0){
                        $notfication_service = new NotificationService($this->model_user_created_by->id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_CANCLED_SELF);
                        $notfication_service->sendMeetingNotification($this);
                   }
                  
            }  else if ($type == "order") {
                foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                    // order
                    if($meeting_user_model->user_id!=\Yii::$app->user->identity->id){
                    $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_ORDER);
                    $notfication_service->sendMeetingNotification($this);
                    }
                }
                    $notfication_service = new NotificationService($this->model_user_created_by->id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_ORDER);
                    $notfication_service->sendMeetingNotification($this);
            } else if ($type == "complaint") {
                foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                    // complaint
                    if($meeting_user_model->user_id!=\Yii::$app->user->identity->id){
                    $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_COMPLAINT);
                    $notfication_service->sendMeetingNotification($this);
                    }
                }
                    $notfication_service = new NotificationService($this->model_user_created_by->id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_COMPLAINT);
                    $notfication_service->sendMeetingNotification($this);
            } else if ($type == "accept") { // accept_OTHER
                 if($this->model_meeting_original->updated_by != $this->model_meeting->created_by){
                           $status=false;
                            foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                             if($meeting_user_model->user_id==\Yii::$app->user->identity->id){
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_ACCEPTED_SELF);
                                $notfication_service->sendMeetingNotification($this, $activity_user_id);
                                $status=true;
                             } else{
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_ACCEPTED_OTHER);
                                $notfication_service->sendMeetingNotification($this, $activity_user_id);
                                }
                           }
                           if($status==0){
                               $notfication_service = new NotificationService($this->model_user_updated_by->id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_ACCEPTED_SELF);
                               $notfication_service->sendMeetingNotification($this, $activity_user_id);
                               }
                     }
                    else{   $status=false;
                            foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                             if($meeting_user_model->user_id==\Yii::$app->user->identity->id){
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_ACCEPTED_SELF);
                                $notfication_service->sendMeetingNotification($this, $activity_user_id);
                                $status=true;
                             } else{
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_ACCEPTED_OTHER);
                                $notfication_service->sendMeetingNotification($this, $activity_user_id);
                                }
                           } if($status==0){
                               $notfication_service = new NotificationService($activity_user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_ACCEPTED_SELF);
                               $notfication_service->sendMeetingNotification($this, $activity_user_id);
                               }
                                $notfication_service = new NotificationService($this->model_user_created_by->id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_ACCEPTED_OTHER);
                                $notfication_service->sendMeetingNotification($this, $activity_user_id);
                               
                    }
               } else if ($type == "decline") {
                    if($this->model_meeting_original->updated_by != $this->model_meeting->created_by){
                           $status=false;
                            foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                             if($meeting_user_model->user_id==\Yii::$app->user->identity->id){
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_DECLINED_SELF);
                                $notfication_service->sendMeetingNotification($this, $activity_user_id);
                                $status=true;
                             } else{
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_DECLINED_OTHER);
                                $notfication_service->sendMeetingNotification($this, $activity_user_id);
                                }
                           }
                           if($status==0){
                               $notfication_service = new NotificationService(\Yii::$app->user->identity->id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_DECLINED_SELF);
                               $notfication_service->sendMeetingNotification($this, $activity_user_id);
                               }
                               $notfication_service = new NotificationService($this->model_meeting->created_by, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_DECLINED_OTHER);
                               $notfication_service->sendMeetingNotification($this, $activity_user_id);
                     }
                    else{   $status=false;
                    //  print_r($this->model_meeting->created_by);die;
                         
                        foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                             if($meeting_user_model->user_id==\Yii::$app->user->identity->id){
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_DECLINED_SELF);
                                $notfication_service->sendMeetingNotification($this, $activity_user_id);
                                $status=true;
                             } else{
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_DECLINED_OTHER);
                                $notfication_service->sendMeetingNotification($this, $activity_user_id);
                                }
                           } if($status==0){
                               $notfication_service = new NotificationService(\Yii::$app->user->identity->id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_DECLINED_SELF);
                               $notfication_service->sendMeetingNotification($this, $activity_user_id);
                               }
                                $notfication_service = new NotificationService($this->model_meeting->created_by, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_DECLINED_OTHER);
                               $notfication_service->sendMeetingNotification($this, $activity_user_id);
                        }
                   
            } 
            else if ($type == "snooze") {
                     $status=false;
                            foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                             if($meeting_user_model->user_id==\Yii::$app->user->identity->id){
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_SNOOZED_SELF);
                                $notfication_service->sendMeetingNotification($this, $activity_user_id);
                                $status=true;
                             } else{
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_SNOOZED_OTHER);
                                $notfication_service->sendMeetingNotification($this, $activity_user_id);
                                }
                           }  
                           if($status==0){
                                $notfication_service = new NotificationService($this->model_user_created_by->id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_SNOOZED_SELF);
                                $notfication_service->sendMeetingNotification($this, $activity_user_id);
                           }
                          }
            else if ($type == "snooze_other") {
                            $status=false;
                            foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                             if($meeting_user_model->user_id==\Yii::$app->user->identity->id){
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_SNOOZED_SELF);
                                $notfication_service->sendMeetingNotification($this,\Yii::$app->user->identity->id);
                                $status=true;
                             } else{
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_SNOOZED_OTHER);
                                $notfication_service->sendMeetingNotification($this,\Yii::$app->user->identity->id);
                                }
                           } if($status==0){
                               $notfication_service = new NotificationService($this->model_user_created_by->id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_SNOOZED_SELF);
                               $notfication_service->sendMeetingNotification($this);
                               }
                              $notfication_service = new NotificationService($this->model_user_created_by->id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_USER_SNOOZED_OTHER);
                              $notfication_service->sendMeetingNotification($this,\Yii::$app->user->identity->id);
                }
                else{
                    if ($this->model_meeting_original->responsible_user_id != $this->model_meeting->responsible_user_id) {
                        $status=false;
                      //  print_r($this->model_meeting->meetingusers);die;
                    foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                        if ($meeting_user_model->user_id == \Yii::$app->user->identity->id ) {
                            $notfication_service = new NotificationService($this->model_meeting->created_by, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_UPDATED_ASSIGNED_RESPONSIBILITY_SELF);
                            $notfication_service->sendMeetingNotification($this,$this->model_meeting->responsible_user_id);
                            $status=true;
                        } else {
                            $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_UPDATED_ASSIGNED_RESPONSIBILITY_OTHER);
                            $notfication_service->sendMeetingNotification($this,$this->model_meeting->created_by);
                        }
                        }
                        if($status==0){
                            $notfication_service = new NotificationService($this->model_meeting->created_by, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_UPDATED_ASSIGNED_RESPONSIBILITY_SELF);
                            $notfication_service->sendMeetingNotification($this,$activity_user_id);
                        }
                        
                }
                     else if (($this->model_meeting_original->start_datetime != $this->model_meeting->start_datetime) or ( $this->model_meeting_original->end_datetime != $this->model_meeting->end_datetime)) {
                    foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                        if($meeting_user_model->user_id==\Yii::$app->user->identity->id){
                        $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_UPDATED_TIME_SELF);
                        $notfication_service->sendMeetingNotification($this);
                        }
                        else{
                            $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_UPDATED_TIME_OTHER);
                            $notfication_service->sendMeetingNotification($this);
                      
                        }
                    }
                        $notfication_service = new NotificationService($this->model_user_created_by->id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_UPDATED_TIME_SELF);
                        $notfication_service->sendMeetingNotification($this);
                        
                } else if ($this->model_meeting_original->agenda != $this->model_meeting->agenda) {
                    foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                        // update  agenda
                         if($meeting_user_model->user_id==\Yii::$app->user->identity->id){
                        $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_UPDATED_AGENDA_SELF);
                        $notfication_service->sendMeetingNotification($this);
                         }
                         else{
                             $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_UPDATED_AGENDA_OTHER);
                             $notfication_service->sendMeetingNotification($this);
                        
                         }
                    }
                        $notfication_service = new NotificationService($this->model_user_created_by->id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_UPDATED_AGENDA_SELF);
                        $notfication_service->sendMeetingNotification($this);
                        
                }
               // Delete  Meeting
                else if ($this->model_meeting->status==0) {
                      if($this->model_meeting_original->updated_by != $this->model_meeting->created_by){
                            $status=false;
                            foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                             if($meeting_user_model->user_id==\Yii::$app->user->identity->id){
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_REMOVED_SELF);
                                $notfication_service->sendMeetingNotification($this,$this->model_meeting->created_by);
                                $status=true;
                             } else{
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_REMOVED_OTHER);
                                $notfication_service->sendMeetingNotification($this,$this->model_meeting->created_by);
                                }
                           }
                           if($status==0){
                               $notfication_service = new NotificationService($this->model_user_updated_by->id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_REMOVED_SELF);
                               $notfication_service->sendMeetingNotification($this,$this->model_meeting->created_by);
                               }
                      }
                      else{
                          $status=false;
                            foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                             if($meeting_user_model->user_id==\Yii::$app->user->identity->id){
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_REMOVED_SELF);
                                $notfication_service->sendMeetingNotification($this,$this->model_meeting->created_by);
                                $status=true;
                             } else{
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_REMOVED_OTHER);
                                $notfication_service->sendMeetingNotification($this,$this->model_meeting->created_by);
                                }
                           }
                           if($status==0){
                               $notfication_service = new NotificationService($this->model_meeting->created_by, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_REMOVED_SELF);
                               $notfication_service->sendMeetingNotification($this,$this->model_meeting->created_by);
                               }
                      }
                     }
                else {// updated by other user
                        if($this->model_meeting_original->updated_by != $this->model_meeting->created_by){
                            $status=false;
                           foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                             if($meeting_user_model->user_id==\Yii::$app->user->identity->id){
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_UPDATED_SELF);
                                $notfication_service->sendMeetingNotification($this);
                                $status=true;
                             } else{
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_UPDATED_OTHER);
                                $notfication_service->sendMeetingNotification($this);
                                }
                           }
                           if($status==0){
                               $notfication_service = new NotificationService($this->model_user_updated_by->id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_UPDATED_SELF);
                               $notfication_service->sendMeetingNotification($this);
                               }
                         
                      }
                      else{ // updated by creater
                             $status=false;
                           foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                             if($meeting_user_model->user_id==\Yii::$app->user->identity->id){
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_UPDATED_SELF);
                                $notfication_service->sendMeetingNotification($this);
                                $status=true;
                             } else{
                                $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_UPDATED_OTHER);
                                $notfication_service->sendMeetingNotification($this,$this->model_meeting->created_by);
                                }
                           }
                           if($status==0){
                               $notfication_service = new NotificationService($this->model_user_created_by->id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_UPDATED_SELF);
                               $notfication_service->sendMeetingNotification($this);
                               }
                      }
                }
                }
                
               
        } else {
            //print_r($this->model_meeting->created_by_behalf_of);die;
            if(!empty($this->model_meeting->created_by_behalf_of)){
                foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                if($meeting_user_model->user_id!=\Yii::$app->user->identity->id){
                    $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_CREATED_BEHALF_OF);
                    $notfication_service->sendMeetingNotification($this);
                }
               }
                 $notfication_service = new NotificationService($this->model_meeting->created_by, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_CREATED_SELF);
                 $notfication_service->sendMeetingNotification($this);
            }else{
                foreach ($this->model_meeting->meetingusers as $meeting_user_model) {
                if($meeting_user_model->user_id!=\Yii::$app->user->identity->id){
                    $notfication_service = new NotificationService($meeting_user_model->user_id, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_CREATED_OTHER);
                    $notfication_service->sendMeetingNotification($this);
                }
               }
                 $notfication_service = new NotificationService($this->model_meeting->created_by, Notification::NOTIFICATION_TYPE_MEETING, Notification::NOTIFICATION_SUB_TYPE_MEETING_CREATED_SELF);
                 $notfication_service->sendMeetingNotification($this);
            }
            
        }
    }

}

