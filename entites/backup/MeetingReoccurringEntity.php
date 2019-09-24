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

class MeetingReoccurringEntity {

    /**
     * @var \app\models\form\MeetingForm
     */
    public $form_model_meeting;

    /**
     * @var \app\models\MeetingReoccurring
     */
    public $model_meeting_reoccurring;
    public $model_user_created_by;
    public $model_user_responsible;
    public $model_user_updated_by;

    public function __construct($form_model_meeting = null, $model_meeting = null) {
        $this->form_model_meeting = $form_model_meeting;
        $this->model_meeting_reoccurring = new MeetingReoccurring();

        if ($model_meeting != null) {
            $this->model_meeting = $model_meeting;
            if (isset($model_meeting->meetingreoccurring))
                $this->model_meeting_reoccurring = $model_meeting->meetingreoccurring;
        }
        //print_r($this->model_meeting_reoccurring);exit;
    }

    public function save() {

//        echo "</pre>";
//        if (isset($this->form_model_meeting->meeting->meetingreoccurring)) {
//            print_r($this->form_model_meeting->meeting->meetingreoccurring);
//            exit;
//            $this->model_meeting_reoccurring = $this->form_model_meeting->meeting->meetingreoccurring;
//        } else {
//            $this->model_meeting_reoccurring = new MeetingReoccurring();
//        }
        //$this->model_meeting_reoccurring = $this->form_model_meeting->meeting->meetingreoccurring;
                //print_r($this->form_model_meeting->meeting->meetingreoccurring);
                //exit;

        $this->model_meeting_reoccurring->setAttributes([
            'origin_source' => $this->form_model_meeting->origin_source,
            'meeting_name' => $this->form_model_meeting->meeting_name,
            'client_name' => $this->form_model_meeting->client_name,
            'wbs_id' => $this->form_model_meeting->wbs_id != '' ? $this->form_model_meeting->wbs_id : '0',
            'responsible_user_id' => $this->form_model_meeting->responsible_user_id,
            'meeting_type' => $this->form_model_meeting->wbs_type != '' ? $this->form_model_meeting->wbs_type : '0',
            'agenda' => $this->form_model_meeting->agenda,
            'start_datetime' => $this->form_model_meeting->startdatetime(),
            'end_datetime' => $this->form_model_meeting->enddatetime(),
            'check_availability' => $this->form_model_meeting->check_availability,
            'reoccurring_type' => $this->form_model_meeting->reoccur,
            'meeting_group_id' => $this->form_model_meeting->meeting_group_id,
           // 'meeting_assigned_for' => $this->form_model_meeting->meeting_assigned_for,
        ]);
        $this->genrateReoccuringDetail();


        if ($this->validate()) {
           $this->model_meeting_reoccurring->status = 1;

                if($this->model_meeting_reoccurring->save()){
                    
                }else{
                  //  exit;
                }
        } else {
            print_r($this->model_meeting_reoccurring->errors);exit;
            throw new \yii\web\BadRequestHttpException("Bad Request, MeetingreoccurringEntity didn't not validate"); // HTTP Code 400
        }
        return false;
    }

    private function genrateReoccuringDetail() {
        switch ($this->model_meeting_reoccurring->reoccurring_type) {
            case Meeting::MEETING_REOCCUR_DAILY:
                break;
            case Meeting::MEETING_REOCCUR_WEEKLY:
                $this->model_meeting_reoccurring->reoccurring_weekday = date("N", strtotime($this->model_meeting_reoccurring->start_datetime));
                break;
            case Meeting::MEETING_REOCCUR_MONTHLY:
                $this->model_meeting_reoccurring->reoccurring_day = date("j", strtotime($this->model_meeting_reoccurring->start_datetime));
                break;
        }
    }

    public function cancel(){
        $this->model_meeting_reoccurring->status = 0;
        if ($this->model_meeting_reoccurring->save()) {
            return true;
        }
        return false;
    }

    public function validate() {
        return $this->model_meeting_reoccurring->validate();
    }
}
