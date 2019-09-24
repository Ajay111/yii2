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
use app\models\ActionPoint;
use app\models\form\ActionForm;
use app\entites\ActionPointEntity;
use app\models\ActionHistory;

/**
 * Member controller for the `api` module
 */
class ActionController extends Controller {

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

   public function actionListall() {
          $this->response['action_detail_all'] = array(); // = $this->getMeetinglist();
       foreach ($this->getActionall() as $ac) {
                    $actionentity = new \app\entites\ActionPointEntity(null, $ac);
                    array_push($this->response['action_detail_all'], $actionentity->getDetail());
                }
        return $this->response;
    }

    private function getActionall($last_update_time = 000000) {
         return  \app\models\ActionPoint::find()
                   ->joinWith(['actionusers'])
                ->where(['or', 'action_user.user_id='.\Yii::$app->user->identity->id,'action_point.action_assigned_by=' . \Yii::$app->user->identity->id])
                ->andWhere(['=', 'meeting_id', '0'])
                ->andWhere("action_point.status > 0")
                ->orderBy('action_point.id DESC')
                ->all();
               // ->createCommand();
         //print_r($aa->sql);
        
    }
    public function actionList() {
        //$last_update_time = $this->data_json['last_wbs_sync_time'];

        $this->response['last_meeting_sync_time'] = $this->getLastWbsUpdateTime();
        $this->response['meeting_list'] = $this->getActionlist();

        return $this->response;
    }
	 public function actionUsername(){
           if (isset($this->data_json['user_id']) && $this->data_json['user_id'] != '') {
               $user =  \app\modules\api\v1\models\User::find()->where(['id'=>$this->data_json['user_id']])->one();
               if($user){
                    $this->response['user_detail']=$user;
                    return $this->response;
               }
               else{
                   throw new \yii\web\BadRequestHttpException("Bad Request, user id not found"); // HTTP COde 400
               }
            }
           else{
                   throw new \yii\web\BadRequestHttpException("Bad Request, user id required"); // HTTP COde 400
           }
      }
    public function actionDetail() {
        if (isset($this->data_json['action_id']) && $this->data_json['action_id'] != '') {
            $action_model = \app\models\ActionPoint::getModel($this->data_json['action_id']);
            if ($action_model == null) {
                throw new \yii\web\ForbiddenHttpException("Forbidden. Action Id not found.");
            } else {
                $actionentity = new ActionPointEntity(null, $action_model);
                $this->response['action_detail'] = $actionentity->getDetail($this->data_json['action_id']); 
                
                //work on createornotification.....
                Yii::$app->db->createCommand()
                        ->update('notification', ['acknowledge_status' => 1,'acknowledge_date'=>date('Y-m-d H:i:s ')], 'notification_type = 4 and acknowledge_status = 0 and detail_id='.$this->data_json['action_id'])
                        ->execute();
            }
        } else {
            throw new \yii\web\BadRequestHttpException("Bad Request, action id not found"); // HTTP Code 400
        }
        return $this->response;
    }

    public function actionSave(){
         if(isset($this->data_json['status']) && $this->data_json['status'] != ''){
           
            if(isset($this->data_json['action_id']) && $this->data_json['action_id'] != ''){
                $action_model = \app\models\ActionPoint::findOne(['id' => $this->data_json['action_id']]);
                if($action_model != null){
                    if($action_model->status == '0'){
                      // throw new \yii\web\ForbiddenHttpException("Sorry, This action is already Complete / Differ.");
                        $this->response['status'] = "0";
                        $this->response['message'] = " Sorry, This action is Inactive";
                        return $this->response;
                    }
                }
            }
            /* $action_model = \app\models\ActionPoint::findOne(['id' => $this->data_json['action_id'], 'action_assigned_to' => \Yii::$app->user->identity->id]);
            if ($action_model == null){
                throw new \yii\web\ForbiddenHttpException("You are not allowed to perform this action.");
            }*/
        }                
        if (isset($this->data_json['action_id']) && $this->data_json['action_id'] != ''){
            $action_model = \app\models\ActionPoint::getModel($this->data_json['action_id']);
            if ($action_model == null && $this->data_json['status'] != "0") { 
                // means assigned to user is coming to mark complete
                $action_model = \app\models\ActionPoint::findOne(['id' => $this->data_json['action_id']]);
            }else if ($action_model == null && $this->data_json['status'] == "0"){
                $this->response['status'] = "0";
                $this->response['message'] = "No Change";
                return $this->response;
            }
	    if ($action_model == null){
                throw new \yii\web\ForbiddenHttpException("You are not allowed to perform this action." . $this->data_json['action_id']);
            } else {
                $form_model = new \app\models\form\ActionForm($action_model->origin_source, $action_model);
                if($action_model->origin_source == ActionPoint::SOURCE_MEETING){
                    $form_model->reoccur = ActionPoint::ACTION_REOCCUR_ONETIME;
                }
                if($action_model->origin_source == ActionPoint::SOURCE_MEETING_WBS) {
                    $form_model->reoccur = ActionPoint::ACTION_REOCCUR_ONETIME;
                }
                if($action_model->origin_source == ActionPoint::SOURCE_WBS_DIRECT) {
                    $form_model->scenario = \app\models\form\ActionForm::SCENARIO_WBSACTION;
                }
                if($action_model->origin_source == ActionPoint::SOURCE_DIRECT_ACTIONPOINT) {
                    $form_model->reoccur = ActionPoint::ACTION_REOCCUR_ONETIME;
                }
            }
        }else{
            if (isset($this->data_json['origin_source']) && $this->data_json['origin_source'] != '') {
                $action_model = null;
                if($this->data_json['origin_source'] == ActionPoint::SOURCE_WBS_DIRECT) {
                    if(isset($this->data_json['wbs_id']) && $this->data_json['wbs_id'] != '') {
                        $wbs = \app\models\Wbs::findOne($this->data_json['wbs_id']);
                        if($wbs != null){
                            $form_model = new \app\models\form\ActionForm(ActionPoint::SOURCE_WBS_DIRECT, null, null, $wbs);
                            $form_model->action_assigned_by = \Yii::$app->user->identity->id;
                            $form_model->scenario = \app\models\form\ActionForm::SCENARIO_WBSACTION;
                        }else
                            throw new \yii\web\ForbiddenHttpException("Forbidden. WBS Id not found.");
                    }else
                        throw new \yii\web\ForbiddenHttpException("Forbidden. WBS Id not found.");
                }else{
                    $form_model = new \app\models\form\ActionForm($this->data_json['origin_source']);
                   // $form_model->action_assigned_by = \Yii::$app->user->identity->id;
                    $form_model->reoccur = ActionPoint::ACTION_REOCCUR_ONETIME;
                }
            }else{
                throw new \yii\web\ForbiddenHttpException("Forbidden. orgin source not found.");
            }
        }
       
        if($form_model->load(['ActionForm' => $this->data_json], null)){
          if($form_model->validate()){
                $array1 = array();
                     if(!empty($this->data_json['mgroup'])) {
                        $selected_groups_ids = explode(",", $this->data_json['mgroup']);
                        $wbsuser[0]['group_id'] = $selected_groups_ids;
                        $groups_member_id = array();
                        $array1 = array();
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
                          $wbsusers = explode(",",$this->data_json['muser']);
                          $array1 = array_merge($wbsusers,$array1);
                          $array1 = array_unique($array1);
                          }
                            $form_model->muser=$array1;
                          //  $form_model->action_assigned_by=\Yii::$app->user->identity->id;
                            $form_model->action_assigned_for=\Yii::$app->user->identity->id;
                            $form_model->action_group_id=$this->data_json['mgroup'];
                            $form_model->action_user_id=$this->data_json['muser'];
                            $form_model->status=$this->data_json['status'];
							 if (isset($this->data_json['approved_status']) && $this->data_json['approved_status'] != '') {
                                $form_model->approved_status=$this->data_json['approved_status'];
                            }
                            if (isset($this->data_json['reason']) && $this->data_json['reason'] != '') {
                                $form_model->reason=$this->data_json['reason'];
                            } 
                            //print_r($form_model->attributes);die;
                            $actionpointentity = new ActionPointEntity($form_model, $action_model);
                $last_insert_update_actionid = $actionpointentity->save();	
		$this->response['action_detail'] = $actionpointentity->getDetail($last_insert_update_actionid);
            }else{
                $this->response['status'] = "0";
                $this->response['message'] = "Error(s): {" . \app\helpers\Utility::convertModelErrorToString($form_model) . "}";
            }
        }else{
            throw new \yii\web\BadRequestHttpException("Bad Request, WBS form model not loaded or didn't validate"); // HTTP Code 400
        }    
        return $this->response;
    }

    private function getActionlist($last_update_time = 000000) {
        return \app\models\ActionPoint::find()->where(['=', 'meeting_user.user_id', \Yii::$app->user->identity->id])->all();
        //return \app\models\Wbs::find()->select(['id', 'owner_id', 'wbs_title', 'start_date', 'end_date', 'status'])->where(['=', 'owner_id', \Yii::$app->user->identity->id])->andWhere(['>', 'updated_at', $last_update_time])->orderBy('updated_at asc')->all();    
    }

    private function getLastMeetingUpdateTime($last_update_time = 000000) {
        return \app\models\MeetingUser::find()->where(['=', 'user_id', \Yii::$app->user->identity->id])->max('updated_at');
    }
	
	
      public function actionSearchaction() {
        $searchModel = new \app\models\ActionPointSearch();
        //$searchModel->action_assigned_by = \Yii::$app->user->identity->id;
        $searchModel->daterange = $searchModel->GetMinDate() . ' to ' . $searchModel->GetMaxDate();
        if (isset($this->data_json['start_date']) && isset($this->data_json['end_date']) ) 
        $searchModel->daterange = $this->data_json['start_date'] . ' to ' . $this->data_json['end_date'];
        $searchModel->SetDateRange($searchModel->daterange);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $allmodels = $dataProvider->getModels();
        $this->response['action_detail_all'] = array(); 
        $resultSets=[];
         foreach($allmodels as $key=>$data):
                $resultSets[$key]['id'] = $data->id;
                $resultSets[$key]['org_id'] = $data->org_id;
                $resultSets[$key]['origin_source'] = $data->origin_source;
                $resultSets[$key]['meeting_id'] = $data->meeting_id;
                $resultSets[$key]['wbs_id'] = $data->wbs_id;
                $resultSets[$key]['reoccurring'] = $data->reoccurring;
                $resultSets[$key]['parent_id'] = $data->parent_id;
                $resultSets[$key]['action'] = $data->action;
                $resultSets[$key]['action_group_id'] = $data->action_group_id;
                $resultSets[$key]['action_user_id'] = $data->action_user_id;
                $resultSets[$key]['action_assigned_to'] = $data->action_assigned_to;
                $resultSets[$key]['deadline'] = $data->deadline;
                $resultSets[$key]['remark'] = $data->remark;
                $resultSets[$key]['action_assigned_by'] = $data->action_assigned_by;
                $resultSets[$key]['created_at'] = $data->created_at;
                $resultSets[$key]['created_by'] = $data->created_by;
                $resultSets[$key]['updated_at'] = $data->updated_at;
                $resultSets[$key]['remark'] = $data->remark;
                $resultSets[$key]['status'] = $data->status;
                $resultSets[$key]['reminder_status_assign_to'] = $data->reminder_status_assign_to;
                $resultSets[$key]['reminder_status_assign_by'] = $data->reminder_status_assign_by;
				$resultSets[$key]['created_by_behalf_of'] = $data->created_by_behalf_of;
                $resultSets[$key]['updated_by_behalf_of'] = $data->updated_by_behalf_of;
				$resultSets[$key]['behalf_of_status'] = $data->behalf_of_status;
                $resultSets[$key]['ad_completion'] = $data->ad_completion;
				$resultSets[$key]['status_for_all'] = $data->status_for_all;
				$resultSets[$key]['user'] = $action['user'] = \app\models\ActionUser::findAll(['action_id' => $data->id,'status'=>1]); 
                $resultSets[$key]['history'] =\app\models\ActionHistory::findAll(['action_id' => $data->id]); 
            endforeach;
            $this->response['action_detail_all']=$resultSets;
            return $this->response;
        
    }
}

