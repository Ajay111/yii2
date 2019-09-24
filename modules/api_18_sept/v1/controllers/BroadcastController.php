<?php

namespace app\modules\api\v1\controllers;

use yii\web\Controller;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use app\models\AppDetail;
use yii\helpers\Json;
use yii\data\ArrayDataProvider;
use app\models\Broadcast;
use app\models\form\BroadcastForm;
use app\models\BroadcastSearch;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use Yii;
use yii\filters\AccessControl;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\web\Response;


/**
 * Member controller for the `api` module
 */
class BroadcastController extends Controller {

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
	
	
    public function actionListall(){
        $broadcast=array();
        $broadcast['status'] = "1";
        $broadcast['message'] = "Success";
        $org_id=\Yii::$app->user->identity->org_id;
        $owner=\Yii::$app->user->identity->id;
		$broadcast_list= \app\models\Broadcast::find()->where("status =1 and org_id =$org_id")->orderBy('id DESC')
                ->all();
       $resultSets = [];
        foreach($broadcast_list as $key => $data):
            
                $broadcast_user= \app\models\BroadcastUser::findAll(['brodecast_id' => $data->id,'status'=>1]);  
                $notification = \app\models\Notification::find()->select(['notification.id','notification.detail_id','notification.user_id','notification.user_name','notification.mail_status','notification.status'])
                    ->rightJoin('broadcast_user', 'broadcast_user.user_id = notification.user_id and broadcast_user.brodecast_id=notification.detail_id')
                    ->where(['notification.detail_id' => $data->id])
                    ->andWhere(['notification.notification_status'=>1])
                    ->andWhere(['broadcast_user.status'=>1])
                    ->all();
                $resultSets[$key]['id'] = $data->id;
                $resultSets[$key]['broadcast_message_title'] = $data->broadcast_message_title;
                $resultSets[$key]['broadcast_message'] = $data->broadcast_message;
                $resultSets[$key]['notification_type'] = $data->notification_type;
                $resultSets[$key]['notification_sub_type'] = $data->notification_sub_type;
                $resultSets[$key]['created_at'] = $data->created_at;
                $resultSets[$key]['created_by'] = $data->created_by;
                $resultSets[$key]['updated_at'] = $data->updated_at;
                $resultSets[$key]['status'] = $data->status;
                $resultSets[$key]['visible'] = $data->visible;
                $resultSets[$key]['users'] = array_values($broadcast_user);
               $resultSets[$key]['notification']=array_values($notification);
                
       endforeach;
                $broadcast['broadcast'] = $resultSets;
                return $broadcast;   
        
    }
	
    public function actionCreate(){
                     
        if (isset($this->data_json['broadcast_id']) && $this->data_json['broadcast_id'] != ''){
			$broadcast_model = \app\models\Broadcast::findOne(['id' => $this->data_json['broadcast_id']]);
            if ($broadcast_model == null && $this->data_json['status'] != "0") { 
                // means assigned to user is coming to mark complete
               // $action_model = \app\models\ActionPoint::findOne(['id' => $this->data_json['action_id']]);
            }
        }else {
            $broadcast_model = Yii::createObject([
                        'class' => \app\models\Broadcast::className(),
            ]);
            $form_model = new \app\models\form\BroadcastForm();
            if (isset($this->data_json['notification_type']) && $this->data_json['notification_type'] != ''){
                if($this->data_json['notification_type']==1){
                    $form_model->notification_sub_type=602;
                }
                else if($this->data_json['notification_type']==2){
                    $form_model->notification_sub_type=601;
                }
                else
                    $form_model->notification_sub_type=603;
            }
          }
        
       if($form_model->load(['BroadcastForm' => $this->data_json], null)){
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
                     }else{
					 $array1=[];
					 }
                      if(!empty($this->data_json['muser'])){  
                          $wbsusers = explode(",",$this->data_json['muser']);
                          $array1 = array_merge($wbsusers,$array1);
                          $array1 = array_unique($array1);
                          }
                            $form_model->muser=$array1;
                            $form_model->broadcast_group_id=$this->data_json['mgroup'];
                            $form_model->broadcast_user_id=$this->data_json['muser'];
                            // print_r( $form_model->attributes);die;
                            $broadcastentity = new \app\entites\BroadcastEntity($form_model, $broadcast_model);
                            $broad_cast_id = $broadcastentity->save();	
                            $this->response['broadcast_detail'] = $broadcastentity->getDetail($broad_cast_id);
            }else{
                $this->response['status'] = "0";
                $this->response['message'] = "Error(s): {" . \app\helpers\Utility::convertModelErrorToString($form_model) . "}";
            }
        }else{
            throw new \yii\web\BadRequestHttpException("Bad Request, WBS form model not loaded or didn't validate"); // HTTP Code 400
        }    
        return $this->response;
    }

}
