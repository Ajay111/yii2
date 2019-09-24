<?php

namespace app\modules\api\v1\controllers;

use app\modules\user\Finder;
use app\modules\user\models\Account;
use app\models\LoginForm;
use yii\web\Controller;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use app\models\AppDetail;
use yii\helpers\Json;
use Yii;
use app\entites\MeetingEntity;
use app\models\Meeting;
use app\models\MeetingUser;
use app\models\MeetingSearch;
use app\models\form\MeetingForm;

/**
 * Member controller for the `api` module
 */
class MeetingController extends Controller {

    protected $finder;
//public $response = [];

    private $response = [];
    private $post_json;
    private $data_json;
    public $app_id;
    public $imei_no;

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
        return parent::beforeAction($event);
    }

    public function actionList() {
        //$last_update_time = $this->data_json['last_wbs_sync_time'];

        $this->response['last_meeting_sync_time'] = $this->getLastWbsUpdateTime();
        $this->response['meeting_list'] = $this->getMeetinglist();

        return $this->response;
    }

    public function actionDetail() {
        if (isset($this->data_json['meeting_id']) && $this->data_json['meeting_id'] != '') {
           // $meeting_model = \app\models\Meeting::getModel($this->data_json['meeting_id'], \Yii::$app->user->identity->id);
         //   $meeting_model = \app\models\Meeting::find()->joinWith(['meetingusers'])->where(['=', 'meeting_user.meeting_id', $this->data_json['meeting_id']])->andWhere(['=', 'meeting_user.user_id', \Yii::$app->user->identity->id])->one();
         $meeting_model = \app\models\Meeting::find()->joinWith(['meetingusers'])->where(['=', 'meeting_user.meeting_id', $this->data_json['meeting_id']])->one();
              
     if ($meeting_model == null) {
                throw new \yii\web\ForbiddenHttpException("Forbidden. Meeting not found.");
            } else {
                $meetingentity = new MeetingEntity(null, $meeting_model);
                $this->response['meeting_detail'] = $meetingentity->getDetail();

                Yii::$app->db->createCommand()
                        ->update('notification', ['acknowledge_status' => 1, 'acknowledge_date' => date('Y-m-d H:i:s ')], 'notification_type = 3 and acknowledge_status = 0 and detail_id=' . $this->data_json['meeting_id'])
                        ->execute();
            }
        } else {
            throw new \yii\web\BadRequestHttpException("Bad Request, Meeting not found"); // HTTP Code 400
        }

        return $this->response;
    }

    public function actionSave() {
         if (isset($this->data_json['meeting_id']) && $this->data_json['meeting_id'] != '') { // Edit/update existing meeting
		 
            $meeting_model = \app\models\Meeting::getModel($this->data_json['meeting_id'], \Yii::$app->user->identity->id);
            if ($meeting_model == null) {
                throw new \yii\web\ForbiddenHttpException("Forbidden. Meeting Id not found.");
            } else {
				if($this->data_json['status']==0){
					 //$meeting_reaccur_model = \app\models\Meeting::findOne($this->data_json['meeting_id']);
					 if($meeting_model->meeting_reoccurring_id){
						  \app\models\Meeting::updateAll(['status' => 0,'meeting_reoccurring_status'=>0], "meeting_reoccurring_id =" . $meeting_model->meeting_reoccurring_id);
							$form_model=null;
							$meetingentity = new MeetingEntity($form_model, $meeting_model);
							$this->response['meeting_detail'] = $meetingentity->getDetail();
					 }else{
						  \app\models\Meeting::updateAll(['status' => 0], "id =" . $meeting_model->id);
							$form_model=null;
							$meetingentity = new MeetingEntity($form_model, $meeting_model);
							$this->response['meeting_detail'] = $meetingentity->getDetail();
					 }
					 
				}
				
                $form_model = new \app\models\form\MeetingForm($meeting_model->origin_source, $meeting_model);
                
                if ($meeting_model->origin_source == Meeting::SOURCE_CALENDAR) {
                    $form_model->scenario = MeetingForm::SCENARIO_CALENDER_MEETING;
                }if ($meeting_model->origin_source == Meeting::SOURCE_WBS) {
                    $form_model->scenario = MeetingForm::SCENARIO_WBS_MEETING;
                }
                if ($meeting_model->origin_source == Meeting::SOURCE_GENERAL_MEETING) {
                    $form_model->scenario = MeetingForm::SCENARIO_DIRECT_MEETING;
                }
            }
        } else { // new meeting
            
            if (isset($this->data_json['origin_source']) && $this->data_json['origin_source'] != '') {
                
                $meeting_model = null;
                $form_model = new \app\models\form\MeetingForm($this->data_json['origin_source']);
                  $array1 = array();
                    if(!empty($this->data_json['mgroup'])) {
                             $selected_groups_ids = explode(",", $this->data_json['mgroup']);
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
                      if(!empty($this->data_json['muser'])){  
                          $meetingusers = explode(",", $this->data_json['muser']);
                          $array1 = array_merge($meetingusers,$array1);
                          $array1 = array_unique($array1);
                          }
                           $form_model->muser=$array1;
               if (isset($this->data_json['status']) && $this->data_json['status'] != '') { $form_model->status=$this->data_json['status'];}
                if (isset($this->data_json['mgroup']) && $this->data_json['mgroup'] != '') { $form_model->meeting_group_id=$this->data_json['mgroup'];}
                if (isset($this->data_json['muser']) && $this->data_json['muser'] != '') {$form_model->meeting_user_id=$this->data_json['muser'];}
                 if ($this->data_json['origin_source'] == Meeting::SOURCE_CALENDAR) {
                    $form_model->scenario = MeetingForm::SCENARIO_CALENDER_MEETING;
                }

                if ($this->data_json['origin_source'] == Meeting::SOURCE_WBS) {
                    $form_model->scenario = MeetingForm::SCENARIO_WBS_MEETING;
                }

                if ($this->data_json['origin_source'] == Meeting::SOURCE_GENERAL_MEETING) {
                    $form_model->scenario = MeetingForm::SCENARIO_DIRECT_MEETING;
                }
              
              //  $form_model->responsible_user_id = \Yii::$app->user->identity->id;
            } else {
                throw new \yii\web\ForbiddenHttpException("Forbidden");
            }
        }
        if ($form_model->load(['MeetingForm' => $this->data_json], null)) {
                        
            if ($form_model->validate()) {
						$array1 = array();
                            if(!empty($this->data_json['mgroup'])) {
                            $selected_groups_ids = explode(",", $this->data_json['mgroup']);
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
                      if(!empty($this->data_json['muser'])){    
                          $meetingusers = explode(",", $this->data_json['muser']);
                          $array1 = array_merge($meetingusers,$array1);
                          $array1 = array_unique($array1);
                          }
                          $form_model->muser=$array1;
                          $form_model->meeting_group_id=$this->data_json['mgroup'];
                          $form_model->meeting_user_id=$this->data_json['muser'];
							if (isset($this->data_json['updated_by_behalf_of']) && $this->data_json['updated_by_behalf_of'] != '') {
                                $form_model->updated_by_behalf_of=$this->data_json['updated_by_behalf_of'];
                            }
                            if (isset($this->data_json['responsible_user_id']) && $this->data_json['responsible_user_id'] != '') {
                                $form_model->responsible_user_id=$this->data_json['responsible_user_id'];
                            } 
                            if (isset($this->data_json['responsible_user_id']) && $this->data_json['responsible_user_id'] != '') {
                                $form_model->responsible_user_id=$this->data_json['responsible_user_id'];
                            } else{
                                 $form_model->responsible_user_id=\Yii::$app->user->identity->id;
                                }
                                $meetingentity = new MeetingEntity($form_model, $meeting_model);
                            $meetingentity->save();
							$this->response['meeting_detail'] = $meetingentity->getDetail();
                    } else {
                        $this->response['status'] = "0";
                        $this->response['message'] = "" . \app\helpers\Utility::convertModelErrorToString($form_model) . "";
                    }
        } else {
            throw new \yii\web\BadRequestHttpException("Bad Request, Meeting form model not loaded or didn't validate"); // HTTP Code 400
        }
        return $this->response;
    }

    public function actionCancel() {
        if (isset($this->data_json['meeting_id']) && $this->data_json['meeting_id'] != '') {
            $meeting_model = \app\models\Meeting::getModel($this->data_json['meeting_id'], \Yii::$app->user->identity->id);
            if ($meeting_model == null) {
                throw new \yii\web\ForbiddenHttpException("Forbidden. Meeting Id not found.");
            } else {
                $form_model = new \app\models\form\MeetingForm($meeting_model->origin_source, $meeting_model);
                $form_model->scenario = MeetingForm::SCENARIO_CANCEL;

                if ($form_model->load(['MeetingForm' => $this->data_json], null)) {
                    if ($form_model->validate()) {
                        $meetingentity = new MeetingEntity($form_model, $meeting_model);
                        $meetingentity->cancel();
                        $this->response['meeting_detail'] = $meetingentity->getDetail();
                    } else {
                        $this->response['status'] = "0";
                        $this->response['message'] = "Error(s): {" . \app\helpers\Utility::convertModelErrorToString($form_model) . "}";
                    }
                } else {
                    throw new \yii\web\BadRequestHttpException("Bad Request, Meeting form model not loaded or didn't validate"); // HTTP Code 400
                }
            }
        } else {
            throw new \yii\web\BadRequestHttpException("Bad Request, Meeting Not Found"); // HTTP Code 400
        }
        return $this->response;
    }

    public function actionSnooze() {
        if (isset($this->data_json['meeting_id']) && $this->data_json['meeting_id'] != '') {
            $meeting_model = \app\models\Meeting::getModel($this->data_json['meeting_id'], null);
            if ($meeting_model == null) {
                throw new \yii\web\ForbiddenHttpException("Forbidden. Meeting Id not found.");
            } else {
                $form_model = new \app\models\form\MeetingForm($meeting_model->origin_source, $meeting_model);
                $form_model->scenario = MeetingForm::SCENARIO_SNOOZE;
                $meeting_user_model = \app\models\MeetingUser::findOne(['meeting_id' => $this->data_json['meeting_id'], 'status' => 1]);
                if ($meeting_user_model == null) {
                    throw new \yii\web\ForbiddenHttpException("Forbidden. Meeting User not found.");
                } else {
                    if ($form_model->load(['MeetingForm' => $this->data_json], null)) {
                        if ($form_model->validate()) {
                            $meetingentity = new MeetingEntity($form_model, $meeting_model);
                            $meetingentity->snooze($meeting_user_model);
                            $this->response['meeting_detail'] = $meetingentity->getDetail();
                        } else {
                            $this->response['status'] = "0";
                            $this->response['message'] = "Error(s): {" . \app\helpers\Utility::convertModelErrorToString($form_model) . "}";
                        }
                    } else {
                        throw new \yii\web\BadRequestHttpException("Bad Request, Meeting form model not loaded or didn't validate"); // HTTP Code 400
                    }
                }
            }
        } else {
            throw new \yii\web\BadRequestHttpException("Bad Request, Meeting Not Found"); // HTTP Code 400
        }
        return $this->response;
    }

    public function actionOrder() {
        if (isset($this->data_json['meeting_id']) && $this->data_json['meeting_id'] != '') {
            $meeting_model = \app\models\Meeting::getModel($this->data_json['meeting_id'], \Yii::$app->user->identity->id);
            if ($meeting_model == null) {
                throw new \yii\web\ForbiddenHttpException("Forbidden. Meeting Id not found.");
            } else {
                $form_model_order = new \app\models\form\MeetingOrderForm();
                $form_model_order->ordermodel = Yii::createObject([
                            'class' => \app\models\MeetingOrder::className(),
                ]);

                if ($form_model_order->load(['MeetingOrderForm' => $this->data_json], null)) {
                    if ($form_model_order->validate()) {
                        $meetingentity = new MeetingEntity(null, $meeting_model);
                        $meetingentity->saveOrder($form_model_order);
                        $this->response['meeting_detail'] = $meetingentity->getDetail();
                    } else {
                        $this->response['status'] = "0";
                        $this->response['message'] = "Error(s): {" . \app\helpers\Utility::convertModelErrorToString($form_model) . "}";
                    }
                } else {
                    throw new \yii\web\BadRequestHttpException("Bad Request, Meeting form model not loaded or didn't validate"); // HTTP Code 400
                }
            }
        } else {
            throw new \yii\web\BadRequestHttpException("Bad Request, Meeting Not Found"); // HTTP Code 400
        }
        return $this->response;
    }

    public function actionComplaint() {
        if (isset($this->data_json['meeting_id']) && $this->data_json['meeting_id'] != '') {
            $meeting_model = \app\models\Meeting::getModel($this->data_json['meeting_id'], \Yii::$app->user->identity->id);
            if ($meeting_model == null) {
                throw new \yii\web\ForbiddenHttpException("Forbidden. Meeting Id not found.");
            } else {
                $form_model_complaint = new \app\models\form\MeetingComplaintForm();
                $form_model_complaint->complaintmodel = Yii::createObject([
                            'class' => \app\models\MeetingComplaint::className(),
                ]);

                if ($form_model_complaint->load(['MeetingComplaintForm' => $this->data_json], null)) {
                    if ($form_model_complaint->validate()) {
                        $meetingentity = new MeetingEntity(null, $meeting_model);
                        $meetingentity->saveComplaint($form_model_complaint);
                        $this->response['meeting_detail'] = $meetingentity->getDetail();
                    } else {
                        $this->response['status'] = "0";
                        $this->response['message'] = "Error(s): {" . \app\helpers\Utility::convertModelErrorToString($form_model) . "}";
                    }
                } else {
                    throw new \yii\web\BadRequestHttpException("Bad Request, Meeting form model not loaded or didn't validate"); // HTTP Code 400
                }
            }
        } else {
            throw new \yii\web\BadRequestHttpException("Bad Request, Meeting Not Found"); // HTTP Code 400
        }
        return $this->response;
    }
	
	public function actionExitfrommeeting() {
        if (isset($this->data_json['meeting_id']) && $this->data_json['meeting_id'] != '') {
            $meeting_model = \app\models\Meeting::getModel($this->data_json['meeting_id'], null);
			if ($meeting_model == null) {
                throw new \yii\web\ForbiddenHttpException("Forbidden. Meeting Id not found.");
            } else {
                $meeting_user_model = \app\models\MeetingUser::findOne(['meeting_id' => $this->data_json['meeting_id'], 'user_id' => $this->data_json['meeting_user_id']]);
				
                if ($meeting_user_model == null) {
                    throw new \yii\web\ForbiddenHttpException("Forbidden. Meeting User not found.");
                } else {
                    $form_accept_decline = new \app\models\form\MeetingAcceptDeclineForm($meeting_user_model, $meeting_model);
                    $form_accept_decline->meeting_id = $this->data_json['meeting_id'];
					if ($form_accept_decline->load(['MeetingAcceptDeclineForm' => $this->data_json], null)) {
                        if ($form_accept_decline->validate()) {
							
							$meetingentity = new MeetingEntity(null, $meeting_model);
							$meetingentity->exitFromMeeting($form_accept_decline);
							$this->response['delete_status'] = "1";
                            $this->response['meeting_detail'] = $meetingentity->getDetailAll();
                        } else {
                            $this->response['status'] = "0";
                          //  $this->response['message'] = "Error(s): {" . \app\helpers\Utility::convertModelErrorToString($form_model) . "}";
                        }
                    } else {
                        throw new \yii\web\BadRequestHttpException("Bad Request, Meeting form model not loaded or didn't validate"); // HTTP Code 400
                    }
                }
            }
        } else {
            throw new \yii\web\BadRequestHttpException("Bad Request, Meeting Not Found"); // HTTP Code 400
        }
        return $this->response;
    }
    public function actionAcceptdecline() {
        if (isset($this->data_json['meeting_id']) && $this->data_json['meeting_id'] != '') {
            $meeting_model = \app\models\Meeting::getModel($this->data_json['meeting_id'], null);
            if ($meeting_model == null) {
                throw new \yii\web\ForbiddenHttpException("Forbidden. Meeting Id not found.");
            } else {
              //  $meeting_user_model = \app\models\MeetingUser::findOne($this->data_json['meeting_user_id']);
				$meeting_user_model = \app\models\MeetingUser::findOne(['meeting_id' => $this->data_json['meeting_id'], 'user_id' => $this->data_json['meeting_user_id']]);
			
                if ($meeting_user_model == null) {
                    throw new \yii\web\ForbiddenHttpException("Forbidden. Meeting User not found.");
                } else {
                    $form_accept_decline = new \app\models\form\MeetingAcceptDeclineForm($meeting_user_model, $meeting_model);
                    $form_accept_decline->meeting_id = $this->data_json['meeting_id'];

                    if ($form_accept_decline->load(['MeetingAcceptDeclineForm' => $this->data_json], null)) {
                        if ($form_accept_decline->validate()) {
                            $meetingentity = new MeetingEntity(null, $meeting_model);
                            $meetingentity->saveAcceptdecline($form_accept_decline);
                            $this->response['meeting_detail'] = $meetingentity->getDetail();
                        } else {
                            $this->response['status'] = "0";
                            $this->response['message'] = "Error(s): {" . \app\helpers\Utility::convertModelErrorToString($form_model) . "}";
                        }
                    } else {
                        throw new \yii\web\BadRequestHttpException("Bad Request, Meeting form model not loaded or didn't validate"); // HTTP Code 400
                    }
                }
            }
        } else {
            throw new \yii\web\BadRequestHttpException("Bad Request, Meeting Not Found"); // HTTP Code 400
        }
        return $this->response;
    }
    public function actionListall(){
       $this->response['meeting_detail_all'] = array(); // = $this->getMeetinglist();
            foreach ($this->getMeetinglistall() as $m) {
                $meetingentity = new \app\entites\MeetingEntity(null, $m);
                array_push($this->response['meeting_detail_all'], $meetingentity->getDetail());
            }
             return $this->response;
    }
     private function getMeetinglistall($last_update_time = 000000) {
          return \app\models\Meeting::find()
                ->joinWith(['meetingusers'])
                ->where(['or', 'meeting_user.user_id='.\Yii::$app->user->identity->id,'meeting.responsible_user_id=' . \Yii::$app->user->identity->id])
				->andWhere(['=', 'meeting.status', 1])
                ->orderBy('meeting.id DESC')
                ->all();
     }

    private function getMeetinglist($last_update_time = 000000) {
        return \app\models\Meeting::find()->joinWith(['meetingusers'])->where(['=', 'meeting_user.user_id', \Yii::$app->user->identity->id])->all();
//return \app\models\Wbs::find()->select(['id', 'owner_id', 'wbs_title', 'start_date', 'end_date', 'status'])->where(['=', 'owner_id', \Yii::$app->user->identity->id])->andWhere(['>', 'updated_at', $last_update_time])->orderBy('updated_at asc')->all();    
    }

    private function getLastMeetingUpdateTime($last_update_time = 000000) {
        return \app\models\MeetingUser::find()->where(['=', 'user_id', \Yii::$app->user->identity->id])->max('updated_at');
    }
	  public function actionReminder()
    {
        $searchModel = new  \app\models\NotificationSearch();
        $searchModel->user_id = \Yii::$app->user->identity->id;
        $searchModel->daterange = $searchModel->GetMinDate() . ' to ' . $searchModel->GetMaxDate();
        if (isset($this->data_json['start_date']) && isset($this->data_json['end_date']) ) 
        $searchModel->daterange = $this->data_json['start_date'] . ' to ' . $this->data_json['end_date'];
        $searchModel->SetDateRange($searchModel->daterange);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $models = $dataProvider->getModels();
        $this->response['reminder_detail_all'] = array(); 
        $resultSets=[];
        foreach($models as $key=>$data):
                 $resultSets[$key]['id'] = $data->id;
                $resultSets[$key]['notification_type'] = $data->notification_type;
                $resultSets[$key]['notification_sub_type'] = $data->notification_sub_type;
                $resultSets[$key]['detail_id'] = $data->detail_id;
                $resultSets[$key]['user_id'] = $data->user_id;
                $resultSets[$key]['user_name'] = $data->user_name;
                $resultSets[$key]['org_id'] = $data->org_id;
                $resultSets[$key]['app_id'] = $data->app_id;
                $resultSets[$key]['message_title'] = $data->message_title;
                $resultSets[$key]['message'] = $data->message;
                $resultSets[$key]['content'] = $data->content;
                $resultSets[$key]['created_at'] = $data->created_at;
                $resultSets[$key]['status'] = $data->status;
                $resultSets[$key]['notification_status'] = $data->notification_status;
               
         //  $this->response['reminder_detail_all']=$m;
        endforeach;
         $this->response['reminder_detail_all']=$resultSets;
        return $this->response;
       }
      
      
	  public function actionReminderall()
    {
        $this->response['reminder_detail_all'] = array(); 
         $this->response['reminder_detail_all']= \app\models\Notification::find()->where(['=', 'user_id', \Yii::$app->user->identity->id])->orderBy(['created_at' => SORT_DESC,])->all();
        
          return $this->response;
    }
	 public function actionSearchmeetingCopy(){
        $searchModel = new \app\models\MeetingUserSearch();
        $searchModel->user_id = \Yii::$app->user->identity->id;
        $searchModel->daterange = $searchModel->GetMinDate() . ' to ' . $searchModel->GetMaxDate();
       if (isset($this->data_json['start_date']) && isset($this->data_json['end_date']) ) 
        $searchModel->daterange = $this->data_json['start_date'] . ' to ' . $this->data_json['end_date'];
        $searchModel->SetDateRange($searchModel->daterange);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $allmodels = $dataProvider->getModels();
        $this->response['meeting_detail_all'] = array(); 
        $resultSets=[];
         foreach($allmodels as $key=>$data):
                $resultSets[$key]['id'] = $data->meeting->id;
                $resultSets[$key]['org_id'] = $data->meeting->org_id;
                $resultSets[$key]['origin_source'] = $data->meeting->origin_source;
                $resultSets[$key]['reoccurring_type'] = $data->meeting->reoccurring_type;
                $resultSets[$key]['reoccurring_weekday'] = $data->meeting->reoccurring_weekday;
                $resultSets[$key]['reoccurring_day'] = $data->meeting->reoccurring_day;
                $resultSets[$key]['meeting_reoccurring_id'] = $data->meeting->meeting_reoccurring_id;
                $resultSets[$key]['meeting_reoccurring_sno'] = $data->meeting->meeting_reoccurring_sno;
                $resultSets[$key]['parent_id'] = $data->meeting->parent_id;
                $resultSets[$key]['wbs_id'] = $data->meeting->wbs_id;
                $resultSets[$key]['meeting_type'] = $data->meeting->meeting_type;
                $resultSets[$key]['client_name'] = $data->meeting->client_name;
                $resultSets[$key]['meeting_name'] = $data->meeting->meeting_name;
                $resultSets[$key]['responsible_user_id'] = $data->meeting->responsible_user_id;
                $resultSets[$key]['agenda'] = $data->meeting->agenda;
                $resultSets[$key]['check_availability'] = $data->meeting->check_availability;
                $resultSets[$key]['start_datetime'] = $data->meeting->start_datetime;
                $resultSets[$key]['end_datetime'] = $data->meeting->end_datetime;
                $resultSets[$key]['snooze'] = $data->meeting->snooze;
                $resultSets[$key]['snooze_time'] = $data->meeting->snooze_time;
                $resultSets[$key]['snooze_reason'] = $data->meeting->snooze_reason;
                $resultSets[$key]['cancel_reason'] = $data->meeting->cancel_reason;
                $resultSets[$key]['comments'] = $data->meeting->comments;
                $resultSets[$key]['created_at'] = $data->meeting->created_at;
                $resultSets[$key]['created_by'] = $data->meeting->created_by;
                $resultSets[$key]['updated_at'] = $data->meeting->updated_at;
                $resultSets[$key]['updated_by'] = $data->meeting->updated_by;
                $resultSets[$key]['status'] = $data->meeting->status;
                $resultSets[$key]['reminder_status'] = $data->meeting->reminder_status;
                $resultSets[$key]['next_meeting_id'] = $data->meeting->next_meeting_id;
                $resultSets[$key]['meeting_reoccurring_status'] = $data->meeting->meeting_reoccurring_status;
                $resultSets[$key]['meeting_assigned_for'] = $data->meeting->meeting_assigned_for;
                $resultSets[$key]['meeting_group_id'] = $data->meeting->meeting_group_id;
                $resultSets[$key]['meeting_user_id'] = $data->meeting->meeting_user_id;
                $resultSets[$key]['users'] = \app\models\MeetingUser::findAll(["meeting_id" => $data->meeting->id,"status"=>1]);
            endforeach;
            $this->response['meeting_detail_all']=$resultSets;
            return $this->response;
      }

	  public function actionSearchmeeting(){
        $searchModel = new MeetingSearch();
        $searchModel->daterange = $searchModel->GetMinDate() . ' to ' . $searchModel->GetMaxDate();
       if (isset($this->data_json['start_date']) && isset($this->data_json['end_date']) ) 
        $searchModel->daterange = $this->data_json['start_date'] . ' to ' . $this->data_json['end_date'];
        $searchModel->SetDateRange($searchModel->daterange);
        $dataProvider = $searchModel->searchall(Yii::$app->request->queryParams);
        $allmodels = $dataProvider->getModels();
        $this->response['meeting_detail_all'] = array(); 
        $resultSets=[];
         foreach($allmodels as $key=>$data):
                $resultSets[$key]['id'] = $data->id;
                $resultSets[$key]['org_id'] = $data->org_id;
                $resultSets[$key]['origin_source'] = $data->origin_source;
                $resultSets[$key]['reoccurring_type'] = $data->reoccurring_type;
                $resultSets[$key]['reoccurring_weekday'] = $data->reoccurring_weekday;
                $resultSets[$key]['reoccurring_day'] = $data->reoccurring_day;
                $resultSets[$key]['meeting_reoccurring_id'] = $data->meeting_reoccurring_id;
                $resultSets[$key]['meeting_reoccurring_sno'] = $data->meeting_reoccurring_sno;
                $resultSets[$key]['parent_id'] = $data->parent_id;
                $resultSets[$key]['wbs_id'] = $data->wbs_id;
                $resultSets[$key]['meeting_type'] = $data->meeting_type;
                $resultSets[$key]['client_name'] = $data->client_name;
                $resultSets[$key]['meeting_name'] = $data->meeting_name;
                $resultSets[$key]['responsible_user_id'] = $data->responsible_user_id;
                $resultSets[$key]['agenda'] = $data->agenda;
                $resultSets[$key]['check_availability'] = $data->check_availability;
                $resultSets[$key]['start_datetime'] = $data->start_datetime;
                $resultSets[$key]['end_datetime'] = $data->end_datetime;
                $resultSets[$key]['snooze'] = $data->snooze;
                $resultSets[$key]['snooze_time'] = $data->snooze_time;
                $resultSets[$key]['snooze_reason'] = $data->snooze_reason;
                $resultSets[$key]['cancel_reason'] = $data->cancel_reason;
                $resultSets[$key]['comments'] = $data->comments;
                $resultSets[$key]['created_at'] = $data->created_at;
                $resultSets[$key]['created_by'] = $data->created_by;
                $resultSets[$key]['updated_at'] = $data->updated_at;
                $resultSets[$key]['updated_by'] = $data->updated_by;
                $resultSets[$key]['status'] = $data->status;
                $resultSets[$key]['reminder_status'] = $data->reminder_status;
                $resultSets[$key]['next_meeting_id'] = $data->next_meeting_id;
                $resultSets[$key]['meeting_reoccurring_status'] = $data->meeting_reoccurring_status;
                $resultSets[$key]['meeting_assigned_for'] = $data->meeting_assigned_for;
                $resultSets[$key]['meeting_group_id'] = $data->meeting_group_id;
                $resultSets[$key]['meeting_user_id'] = $data->meeting_user_id;
				$resultSets[$key]['created_by_behalf_of'] = $data->created_by_behalf_of;
                $resultSets[$key]['updated_by_behalf_of'] = $data->updated_by_behalf_of;
                $resultSets[$key]['behalf_of_status'] = $data->behalf_of_status;
				$resultSets[$key]['users'] = \app\models\MeetingUser::findAll(["meeting_id" => $data->id,"status"=>1]);
            endforeach;
            $this->response['meeting_detail_all']=$resultSets;
            return $this->response;
      }
}
