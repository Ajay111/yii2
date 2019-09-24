<?php

namespace app\modules\api\v1\controllers;
use yii\web\Controller;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use app\models\AppDetail;

use Yii;
use app\modules\api\v1\models\UserGroup;

class CalendarController extends \yii\web\Controller
{
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

    public function beforeAction($event){
        $this->current_module = \Yii::$app->controller->module;
        $this->post_json = $this->current_module->post_json;
        $this->data_json = $this->current_module->data_json;
        $this->response['status'] = "1";
        $this->response['message'] = "Success";
        return parent::beforeAction($event);
    }
    
     public function actionList() {
        //$last_update_time = $this->data_json['last_wbs_sync_time'];
        $org_id=\Yii::$app->user->identity->org_id;
        $meetingList = \app\models\Meeting::find()
        ->where("org_id=$org_id and status=1 and origin_source=1 and meeting_user_id!=0 ")
        ->all();
        if(count($meetingList) > 0){
        foreach($meetingList as $meeting):
                $group=array();
                $group['group'] = \app\models\Group::find()->where(['IN', 'id',explode(',',$meeting->meeting_group_id )])->all();
               
        endforeach;
        $this->response['meeting'] = $meetingList;
       
        return $this->response;
        }
        else{
             throw new \yii\web\BadRequestHttpException("Bad Request, Meeting not Available"); // HTTP Code 400
             $this->response['status'] = 'false';  
        }
          
    }

    public function actionDetail() {
		if (isset($this->data_json['meeting_id']) && $this->data_json['meeting_id'] != '') {
	    $id=$this->data_json['meeting_id'];
		$meeting= \app\models\Meeting::find()->where(['id'=>$id])->one();
		$groupList=$meeting->meeting_group_id;
		$meeting=array();
                $meeting['status'] = "1";
                $meeting['message'] = "Success";
                $meeting['data']= \app\models\Meeting::find()->select(['id','origin_source','reoccurring_type','reoccurring_weekday','reoccurring_day','meeting_reoccurring_id','meeting_reoccurring_sno','parent_id','wbs_id','meeting_type','client_name','meeting_name','responsible_user_id','agenda',
                    'check_availability','start_datetime','end_datetime','snooze','snooze_time','snooze_reason','cancel_reason','comments','created_at','created_by','updated_at','updated_by','status','reminder_status','next_meeting_id','meeting_reoccurring_status','meeting_assigned_for','meeting_group_id','meeting_user_id'
                    ])->where(['id' => $id])->one();
                $meeting['group'] = \app\models\Group::find()->select(['id','group_name','users','org_id','created_at','created_by','updated_at','updated_by','status'])->where(['IN', 'id',explode(',',$groupList )])->all();
                $meeting['users'] = \app\models\MeetingUser::findAll(["meeting_id" => $id]);
                   
               return $meeting;


           } else {
            throw new \yii\web\BadRequestHttpException("Bad Request, Meeting not found"); // HTTP Code 400
        }

        return $this->response;
    }

   public function actionSave() {
        if (isset($this->data_json['meeting_name']) && $this->data_json['meeting_name'] != '') { 
            $meeting_model =new \app\models\Meeting();
            $meeting_model->origin_source=$this->data_json['origin_source'];
            $meeting_model->meeting_name=$this->data_json['meeting_name'];
            $meeting_model->reoccurring_type=$this->data_json['reoccur'];
            $date=$this->data_json['date'];
            $meeting_model->meeting_type=$this->data_json['meeting_type'];
            $start_date=$this->startdatetime($date,$this->data_json['time_start']);
            $end_date=$this->startdatetime($date,$this->data_json['time_end']);
            $meeting_model->start_datetime=$start_date;
            $meeting_model->end_datetime=$end_date;
            $meeting_model->agenda=$this->data_json['agenda'];
            $meeting_model->check_availability=$this->data_json['check_availability'];
            $meeting_model->meeting_group_id=$this->data_json['mgroup'];
            $meeting_model->meeting_user_id=$this->data_json['muser'];
            $meeting_model->responsible_user_id=isset($this->data_json['responsible_user_id']) ? $this->data_json['responsible_user_id'] : 0;
            $meeting_model->attributes= \Yii::$app->request->post();
            $groupList=$this->data_json['mgroup'];
            $userList=$this->data_json['muser'];
            if($meeting_model->validate() && $meeting_model->save()){
            $meeting_saved_id=$meeting_model->id;
            $id=$meeting_model->id;
            $group= $this->updateInToMeetingUser($groupList,$userList,$meeting_saved_id); //saved into meeting User
                
                $meeting=array();
                $meeting['status'] = "1";
                $meeting['message'] = "Success";
                $meeting['data']= \app\models\Meeting::find()->select(['id','origin_source','reoccurring_type','reoccurring_weekday','reoccurring_day','meeting_reoccurring_id','meeting_reoccurring_sno','parent_id','wbs_id','meeting_type','client_name','meeting_name','responsible_user_id','agenda',
                    'check_availability','start_datetime','end_datetime','snooze','snooze_time','snooze_reason','cancel_reason','comments','created_at','created_by','updated_at','updated_by','status','reminder_status','next_meeting_id','meeting_reoccurring_status','meeting_assigned_for','meeting_group_id','meeting_user_id'
                    ])->where(['id' => $id])->one();
                $meeting['group'] = \app\models\Group::find()->select(['id','group_name','users','org_id','created_at','created_by','updated_at','updated_by','status'])->where(['IN', 'id',explode(',',$groupList )])->all();
                $meeting['users'] = \app\models\MeetingUser::findAll(["meeting_id" => $id]);
                   
               return $meeting;
            }
            
        } else {
            throw new \yii\web\BadRequestHttpException("Bad Request, Meeting form model not loaded or didn't validate"); // HTTP Code 400
        }
        return $this->response;
    }
    public function updateInToMeetingUser($groupList,$userList,$id){
	$group_member = \app\models\MeetingUser::find()->where("meeting_id= $id")->all();
        if(!empty($groupList)){
            if($group_member){
                foreach( $group_member as $g):
                $g->delete();
               endforeach;
            }
		$group = \app\models\Group::find()->where(['IN', 'id',explode(',',$groupList )])->all();
		if($group){
                        foreach($group as $grp):
                                 $grpUser = explode(',', $grp->users);
				    foreach($grpUser as $gu):
                                        $meetingUserModel = new \app\models\MeetingUser();
                                        $meetingUserModel->id = NULL; //primary key(auto increment id) id
                                        $meetingUserModel->isNewRecord = true;
                                        $meetingUserModel->user_id=$gu;
                                        $meetingUserModel->group_id=$grp->id;
                                        $meetingUserModel->meeting_id=$id;
                                        $meetingUserModel->save();
                                     endforeach;
                        endforeach;	
                }      
        }
    }
   
    public function actionUpdate(){
        if (isset($this->data_json['meeting_id']) && $this->data_json['meeting_id'] != '') { // Edit/update existing meeting
            $meeting_model = \app\models\Meeting::getModel($this->data_json['meeting_id'], \Yii::$app->user->identity->id);
             $meeting_model->origin_source=$this->data_json['origin_source'];
            $meeting_model->meeting_name=$this->data_json['meeting_name'];
            $meeting_model->reoccurring_type=$this->data_json['reoccur'];
            $date=$this->data_json['date'];
            $meeting_model->meeting_type=$this->data_json['meeting_type'];
            $meeting_model->agenda=$this->data_json['agenda'];
            $meeting_model->check_availability=$this->data_json['check_availability'];
            $meeting_model->meeting_group_id=$this->data_json['mgroup'];
            $meeting_model->meeting_user_id=$this->data_json['muser'];
            $meeting_model->responsible_user_id=isset($this->data_json['responsible_user_id']) ? $this->data_json['responsible_user_id'] : 0;
            $meeting_model->reoccurring_type=isset($this->data_json['reoccurring_type']) ? $this->data_json['reoccurring_type'] : 0;
             $date=$this->data_json['date'];
            $start_date=$this->startdatetime($date,$this->data_json['time_start']);
            $end_date=$this->startdatetime($date,$this->data_json['time_end']);
            $meeting_model->start_datetime=$start_date;
            $meeting_model->end_datetime=$end_date;
            $meeting_model->attributes= \Yii::$app->request->post();
            $groupList=$this->data_json['mgroup'];
            $userList=$this->data_json['muser'];
            $meeting_saved_id=$this->data_json['meeting_id'];
            $id=$this->data_json['meeting_id'];
          
        if ($meeting_model->validate()) {

//            $meeting_clash_id = $this->clashCheckForCreator($meeting_model->start_datetime,$meeting_model->end_datetime);
//            $model_meeting_original = Meeting::findOne($this->data_json['meeting_id']);
//                if ($meeting_clash_id != "") {
//                        if (( $model_meeting_original == null) or ( $this->data_json['meeting_id'] != $meeting_clash_id)) {
//                            $meeting_model_temp = Meeting::findOne($meeting_clash_id);
//                             throw new \yii\web\BadRequestHttpException($meeting_model_temp->meeting_name . " is scheduled at chosen time. Please select a different time.");
//                        }
//                       }
                if ($meeting_model->save()) {
               $group= $this->updateInToMeetingUser($groupList,$userList,$meeting_saved_id); //saved into meeting User
                $meeting=array();
                $meeting['status'] = "1";
                $meeting['message'] = "Success";
                $meeting['data']= \app\models\Meeting::find()->select(['id','origin_source','reoccurring_type','reoccurring_weekday','reoccurring_day','meeting_reoccurring_id','meeting_reoccurring_sno','parent_id','wbs_id','meeting_type','client_name','meeting_name','responsible_user_id','agenda',
                    'check_availability','start_datetime','end_datetime','snooze','snooze_time','snooze_reason','cancel_reason','comments','created_at','created_by','updated_at','updated_by','status','reminder_status','next_meeting_id','meeting_reoccurring_status','meeting_assigned_for','meeting_group_id','meeting_user_id'
                    ])->where(['id' => $id])->one();
                $meeting['group'] = \app\models\Group::find()->select(['id','group_name','users','org_id','created_at','created_by','updated_at','updated_by','status'])->where(['IN', 'id',explode(',',$groupList )])->all();
                $meeting['users'] = \app\models\MeetingUser::findAll(["meeting_id" => $id]);
                   
               return $meeting;
                // $this->assign_user_to_meeting();
               // $this->genrateNotification($save_type);
               // return $this->response['meeting_detail'] = $this->getDetails($this->data_json['meeting_id']);
               
            } else {
                echo "<pre/>";
                print_r($this->model_meeting->getErrors());
                exit;
                 }
            } 
          
            if ($meeting_model == null) {
                throw new \yii\web\ForbiddenHttpException("Forbidden. Meeting Id not found.");
            }
        }
    }
           
     public function startdatetime($date,$time_start) {
        $strtdate = $date . ' ' . $time_start;
        return \Yii::$app->formatter->asDatetime($strtdate, "php:Y-m-d H:i:s");
    }

    public function enddatetime($date,$time_end) {
        $enddate = $date . ' ' . $time_end;
        //\Yii::$app->formatter->timeZone = 'Asia/Kolkata';
        return \Yii::$app->formatter->asDatetime($enddate, "php:Y-m-d H:i:s");
    }
    public function actionSearchcalandar() {
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

}
