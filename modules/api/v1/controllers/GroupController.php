<?php

namespace app\modules\api\v1\controllers;
use yii\web\Controller;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use Yii;
use app\modules\api\v1\models\UserGroup;
use app\modules\api\v1\models\User;

class GroupController extends \yii\web\Controller
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
   public function actionExitgroup(){
		 if (isset($this->data_json['group_id']) && $this->data_json['group_id'] != '') {
			$group_id=$this->data_json['group_id'];
			$group_model = \app\models\Group::findOne(['id' => $this->data_json['group_id'],'status'=>1]);
			if($group_model == null) {
				$this->response['status'] = "0";
                $this->response['message'] = "Group not found";
            }else {
				if($group_model->created_by==\Yii::$app->user->identity->id){
					$this->response['status'] = "0";
					$this->response['message'] = "You are a group admin";
					return $this->response;
				}
				$form_model = new \app\models\form\GroupForm($this->data_json['group_id']);
			}
		}
		if ($form_model->load(['GroupForm' => $this->data_json])) {
                if($form_model->validate()) {
                    $groupentity = new \app\entites\GroupEntity($form_model, $group_model); 
					$groupentity->exitFromGroup($form_model);
					$this->response['group_list'] = $groupentity->getDetail();
            } else {
                $this->response['status'] = "0";
                $this->response['message'] = "Error test";
            }
        } else {
            throw new \yii\web\BadRequestHttpException("Bad Request, group not loaded or didn't validate"); // HTTP Code 400
        }
        return $this->response;
    }
    public function actionGroupdetails(){
        if (isset($this->data_json['group_id']) && $this->data_json['group_id'] != '') {
            $group_id=$this->data_json['group_id'];
            $group=array();
            $connection = Yii::$app->getDb();
            $command = $connection->createCommand("SELECT * FROM `user_group` WHERE `id`='".$group_id."'");
            $resultSets=[];       	
         if (count($command->queryAll()) == 0) {
              throw new \yii\web\BadRequestHttpException("Bad Request, Group model not found or didn't validate"); 
            } 
            else {
               foreach($command->queryAll() as $key => $data):
                   $group_user = \app\modules\api\v1\models\User::findAll(["id" => explode(",", $data['users'])]);
                    $id=$data['id'];
                     $resultSets[$key]['id']=$data['id'];
                     $resultSets[$key]['users']=$data['users'];
                     $resultSets[$key]['group_name']=$data['group_name'];
                     $resultSets[$key]['org_id']=$data['org_id'];
                     $resultSets[$key]['created_at']=$data['created_at'];
                     $resultSets[$key]['created_by']=$data['created_by'];
                     $resultSets[$key]['updated_at']=$data['updated_at'];
                     $resultSets[$key]['updated_by']=$data['updated_by'];
                     $resultSets[$key]['status']=$data['status'];
                     $resultSets[$key]['group_users']=$group_user;
                     endforeach;
                      $this->response['data']=$resultSets;
                   return $this->response;
              }
           
        }
       
    }
    public function actionCreate() {
        if (isset($this->data_json['group_id']) && $this->data_json['group_id'] != '') {

         //   $group_model = \app\models\Group::getModel($this->data_json['group_id'],null);
 		$group_model = \app\models\Group::findOne(['id' => $this->data_json['group_id']]);
            if ($group_model == null) {
                throw new \yii\web\ForbiddenHttpException("Forbidden");
            } else {
                $form_model = new \app\models\form\GroupForm($this->data_json['group_id']);
              }
        } else {
            $group_model = Yii::createObject([
                        'class' => \app\models\Group::className(),
            ]);
            $form_model = new \app\models\form\GroupForm();
          }
        if ($form_model->load(['GroupForm' => $this->data_json])) {
              
                if($form_model->validate()) {
                    $groupentity = new \app\entites\GroupEntity($form_model, $group_model); 
                            $groupentity->save();
                             $this->response['group_list'] = $groupentity->getDetail();
                         
            } else {
                $this->response['status'] = "0";
                $this->response['message'] = "Error(s): {" . \app\helpers\Utility::convertModelErrorToString($form_model) . "}";
            }
        } else {
            throw new \yii\web\BadRequestHttpException("Bad Request, WBS form model not loaded or didn't validate"); // HTTP Code 400
        }
        return $this->response;
    }
   
   public function actionCreate11() {
            $group=new UserGroup();
            if (isset($this->data_json['users']) && $this->data_json['users'] != ''){
            $group->users=$this->data_json['users'];
            }
            if (isset($this->data_json['group_name']) && $this->data_json['group_name'] != ''){
            $group->group_name=$this->data_json['group_name'];
            }
            if (isset($this->data_json['org_id']) && $this->data_json['org_id'] != ''){
            $group->org_id=$this->data_json['org_id'];
             }
            if (isset($this->data_json['created_at']) && $this->data_json['created_at'] != ''){
            $group->created_at=$this->data_json['created_at'];
             }
             if (isset($this->data_json['created_by']) && $this->data_json['created_by'] != ''){
            $group->created_by=$this->data_json['created_by'];
             }
             if (isset($this->data_json['updated_at']) && $this->data_json['updated_at'] != ''){
            $group->updated_at=$this->data_json['updated_at'];
             }
             if (isset($this->data_json['updated_by']) && $this->data_json['updated_by'] != ''){
            $group->updated_by=$this->data_json['updated_by'];
             }
             if (isset($this->data_json['status']) && $this->data_json['status'] != ''){
            $group->status=$this->data_json['status'];
            }
             $group->attributes= \Yii::$app->request->post();
            	 	if($group->validate()){
                           $group->save();
                          return $this->response;
                        }
                        else{
                            throw new \yii\web\ForbiddenHttpException("Sorry, Invalid Data.");
				
			}
    }
    public function actionListall()
	    {
        $group=array();
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("SELECT `user_group`.* FROM `user_group` INNER JOIN `user` ON FIND_IN_SET(user.id,user_group.users) OR FIND_IN_SET(user.id,user_group.created_by) where user.id='".\Yii::$app->user->identity->id."' and user_group.status=1 GROUP BY `user_group`.`id` ORDER BY `user_group`.`id` DESC");
        $resultSets = [];
        if(count($command->queryAll()) > 0)
	    	{
		foreach($command->queryAll() as $key => $data):
                    //$wbs_user = User::findAll(["id" => explode(",", $data['users'])]);
                     $resultSets[$key]['id']=$data['id'];
                     $resultSets[$key]['users']=$data['users'];
                     $resultSets[$key]['group_name']=$data['group_name'];
                     $resultSets[$key]['org_id']=$data['org_id'];
                     $resultSets[$key]['created_at']=$data['created_at'];
                     $resultSets[$key]['created_by']=$data['created_by'];
                     $resultSets[$key]['updated_at']=$data['updated_at'];
                     $resultSets[$key]['updated_by']=$data['updated_by'];
                     $resultSets[$key]['status']=$data['status'];
                    // $resultSets[$key]['group_users']=$wbs_user;
                     $resultSets[$key]['group_users']=\app\modules\api\v1\models\GroupUser::findAll(['group_id' => $data['id'],'status'=>1]);
//                     $resultSets[$key]['notification'] = \app\models\Notification::find()->select(['detail_id','user_id','user_name','mail_status','status'])
//                       ->where(['detail_id' => $data['id'],'notification_status'=>1])
//                       ->all();
                     endforeach;
                     $group['data'] = $resultSets;
                     return $group;
		}
                else
            {
                     throw new \yii\web\ForbiddenHttpException("You have no any group yet.");
            }
	    }
    public function actionView($id)
        {
                $group = UserGroup::find()
                ->andWhere(['id' => (int) $id]);
                  if (!$group->exists()) 
                            throw new NotFoundHttpException();
                            return $group->one();
        }
    public function actionDelete()
        {   
		if (isset($this->data_json['id']) && $this->data_json['id'] != ''){
            $id=$this->data_json['id'];
            $model = UserGroup::findOne($id);
            if($model === null){ 
                throw new \yii\web\ForbiddenHttpException("Not Valid data.");
                   } 
                   else{
                        $model->delete();  
                        return $this->response;
                   }
        }
        else{
            throw new \yii\web\ForbiddenHttpException("Not Valid data.");
        }
      }  

     public function actionUpdate()
        {   
         if (isset($this->data_json['id']) && $this->data_json['id'] != ''){
            $id=$this->data_json['id'];
            $model = UserGroup::findOne($id);
            $model->users=$this->data_json['users'];
            $model->group_name=$this->data_json['group_name'];
            $model->updated_at=$this->data_json['updated_at'];
            $model->updated_by=$this->data_json['updated_by'];
            $model->status=$this->data_json['status'];
            $model->attributes= \Yii::$app->request->post();
           
            if($model->validate()){ 
                 $model->update();
                 return $this->response;
                   } 
            }
            else {
                 throw new \yii\web\ForbiddenHttpException("Not Valid data.");
                   }  
     }  
    public function actionIndex()
    {
        return $this->render('index');
    }

}

