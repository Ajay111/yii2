<?php

namespace app\modules\api\v1\controllers;
use yii\web\Controller;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use Yii;
use app\modules\api\v1\models\Wbs;
use app\modules\api\v1\models\WbsUser;
class WbsController extends \yii\web\Controller
{
    protected $finder;
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
	public function actionSearchwbs(){
            $searchModel = new \app\models\WbsSearch();
            $searchModel->owner_id = \Yii::$app->user->identity->id;
            $searchModel->status = 1;
            $searchModel->daterange = $searchModel->GetMinDate() . ' to ' . $searchModel->GetMaxDate();
            if (isset($this->data_json['start_date']) && isset($this->data_json['end_date']) ) 
            $searchModel->daterange = $this->data_json['start_date'] . ' to ' . $this->data_json['end_date'];
            $searchModel->SetDateRange($searchModel->daterange);
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
            $allmodels = $dataProvider->getModels();
            $resultSets=[];
            $this->response['wbs_detail_all'] = array(); 
             foreach($allmodels as $key=>$data):
                $resultSets[$key]['id'] = $data->id;
                $resultSets[$key]['wbs_title'] = $data->wbs_title;
                $resultSets[$key]['owner_id'] = $data->owner_id;
                $resultSets[$key]['start_date'] = $data->start_date;
                $resultSets[$key]['end_date'] = $data->end_date;
				$resultSets[$key]['wbs_user_id'] = $data->wbs_user_id;
				$resultSets[$key]['wbs_group_id'] = $data->wbs_group_id;
                $resultSets[$key]['created_at'] = $data->created_at;
                $resultSets[$key]['created_by'] = $data->created_by;
                $resultSets[$key]['updated_at'] = $data->updated_at;
                $resultSets[$key]['updated_by'] = $data->updated_by;
                $resultSets[$key]['status'] = $data->status;
                $resultSets[$key]['users'] = \app\models\WbsUser::findAll(["wbs_id" => $data->id,"status"=>1]);

            endforeach;
            $this->response['wbs_detail_all']=$resultSets;
            return $this->response;
           
    }
     public function actionSavewbsaction() {
         if (isset($this->data_json['wbs_id']) && $this->data_json['wbs_id'] != ''){
            
            $action=new \app\models\ActionPoint();
            $action->wbs_id=$this->data_json['wbs_id'];
            $action->action=$this->data_json['action_name'];
            $action->origin_source=$this->data_json['origin_source'];
            $action->deadline=$this->data_json['deadline'];
            $action->reoccurring=$this->data_json['reoccurring_type'];
            $action->action_assigned_by=$this->data_json['action_assigned_by'];
            $action->action_assigned_to=$this->data_json['action_assigned_to'];
            $action->action_assigned_by=$this->data_json['action_assigned_for'];
            $action->action_group_id=$this->data_json['mgroup'];
            $action->action_user_id=$this->data_json['muser'];
            $grp=$this->data_json['mgroup'];
            $user=$this->data_json['muser'];
                if($action->save() && $action->validate()){
                $action_point= $this->insertIntoActionUser($grp,$user,$id);
                $actionPoint=array();
                $actionPoint['status'] = "1";
                $actionPoint['message'] = "Success";
                $actionPoint['data']= \app\models\ActionPoint::find()->where(['id' => $id])->one();
                $actionPoint['group'] = \app\models\Group::find()->where(['IN', 'id',explode(',',$grp )])->all();
                $actionPoint['users'] = \app\models\ActionUser::findAll(["action_id" => $id]);
                   
               return $actionPoint;
             }
            }
           
         
    }
    public function insertIntoActionUser($group,$user,$id){
    $action_user_all = \app\models\ActionUser::find()->where("action_id= $id")->all();
         if(!empty($group)){
            if($action_user_all){
                foreach( $action_user_all as $actionUser):
                $actionUser->delete();
               endforeach;
            }
             $group = \app\models\Group::find()->where(['IN', 'id',explode(',',$group )])->all();
                foreach($group as $grp){
                                 $grpUser = explode(',', $grp->users);
                                   foreach($grpUser as $gu):
                                     $action_user = \app\models\ActionUser::find()->where("user_id = $gu  and action_id= $id")->all();
                                      if($action_user) {
                                          } else {//doesn't exist so create record
                                                    $model = \app\models\ActionUser::find()->where(['id' => $id])->one();
                                                     $action_user = new \app\models\ActionUser();
                                                     $action_user->id = NULL; //primary key(auto increment id) id
                                                     $action_user->isNewRecord = true;
                                                     $action_user->action=$model->action;
                                                     $action_user->deadline=$model->deadline;
                                                     $action_user->action_assigned_by=$model->action_assigned_by;
                                                     $action_user->user_id=$gu;
                                                     $action_user->action_assigned_to=$gu;
                                                     $action_user->action_id=$id;
                                                     $action_user->save();
                                                     }
                                     endforeach;
                              }
         }
}
 public function actionListall(){

	    }
    public function actionView($id)
        {
        }
    public function actionDelete()
        {   
        $wbs=new Wbs();
       // $group_member=new WbsUser();
         if (isset($this->data_json['id']) && $this->data_json['id'] != ''){
            $id=$this->data_json['id'];
            $model = Wbs::findOne($id);
             $wbsUser = WbsUser::find()->where([wbs_id => $id])->all();
             print_r($wbsUser);
//             if($model === null){ 
//                throw new \yii\web\ForbiddenHttpException("Not Valid data.");
//                   } 
//                   else{
//                        $model->delete();  
//                        return $this->response;
//                   }
                   
            
            }
            else{
            throw new \yii\web\ForbiddenHttpException("Not Valid data.");
        }
      }  
     public function actionUpdate()
        {   
     }  
    public function actionIndex()
    {
        return $this->render('index');
    }

}

