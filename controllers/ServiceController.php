<?php

namespace app\controllers;

use app\modules\api\v1\models\UserGroup;
use app\modules\api\v1\models\User;
use app\modules\user\models\Account;
use yii\web\Controller;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use app\models\ActionHistory;
use app\models\WbsUser;
use app\models\Wbs;
use app\models\AppDetail;
use yii\helpers\Json;
use dektrium\user\filters\AccessRule;
use dektrium\user\Finder;
use dektrium\user\models\Profile;
use app\models\UserModel;
use dektrium\user\helpers\Password;
use dektrium\user\Module;
use dektrium\user\traits\EventTrait;
use yii;
use yii\base\ExitException;
use yii\base\Model;
use yii\base\Module as Module2;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;
use app\models\UserSearch;
use app\models\LoginForm;
use app\models\NotificationSearch;

class ServiceController extends \yii\web\Controller
{
    use EventTrait;
    public $enableCsrfValidation = false;
    const EVENT_BEFORE_CREATE = 'beforeCreate';
    public function init() {
         $this->php_input = file_get_contents("php://input");
        $this->post_json = json_decode(base64_decode($this->php_input), true);
        $this->post_json = json_decode($this->php_input, true);
        $this->data_json = $this->post_json['data'];
        $response = Yii::$app->response;
        $response->format = \yii\web\Response::FORMAT_JSON;
        parent::init();
    }
    /**
     * Event is triggered after creating new user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_AFTER_CREATE = 'afterCreate';
    private $response = [];
    public $data_json;
    public $php_input;
    public $post_json;
    public function actionIndex()
    {
        return $this->render('index');
    }
   
     public function actionReminder()
    {
        // print_r(Yii::$app->request->queryParams);die;
       $searchModel = new NotificationSearch();
        $searchModel->user_id = \Yii::$app->user->identity->id;
        $searchModel->daterange = $searchModel->GetMinDate() . ' to ' . $searchModel->GetMaxDate();
        if (isset($this->data_json['from']) && isset($this->data_json['to']) ) 
        $searchModel->daterange = $this->data_json['from'] . ' to ' . $this->data_json['to'];
       // $searchModel->SetDateRange($searchModel->daterange);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $models = $dataProvider->getModels();
        foreach($models as $m):
            print_r($m->message);
           echo $m->message;
        endforeach;
       die;
        Reminder::updateAll(['is_read' => 1], 'reminder_user_id ='. \Yii::$app->user->identity->id.' AND is_read = 0');
        return $this->render('index', [
                    'dataProvider' => $dataProvider,
                    'searchModel' => $searchModel,
        ]);
    }
   
    public function actionLogin(){
       if (isset($this->data_json['email']) and isset($this->data_json['password'])) {
            $model = \Yii::createObject(LoginForm::className());
            $model->login = $this->data_json['username'];
           // $model->email = $this->data_json['email'];
            $model->password = $this->data_json['password'];
             if ($model->login()) {
                $this->response['status'] = "1";
                $this->response['message'] = "success";
                $this->response['data']=\app\modules\api\v1\models\User::find()
                ->where(["or","username="."'$model->login'"])->one();
                $this->response['user_list'] = $this->getUserlist();

            $this->response['wbs_list'] = $this->getWbslistall();
	    $this->response['group_list'] = $this->getGrouplist();
            $this->response['meeting_detail_all'] = array(); // = $this->getMeetinglist();
            foreach ($this->getMeetinglist() as $m) {
                $meetingentity = new \app\entites\MeetingEntity(null, $m);
                array_push($this->response['meeting_detail_all'], $meetingentity->getDetail());
            }
            $this->response['action_detail_all'] = array(); // = $this->getMeetinglist();
	foreach ($this->getActionlist() as $ac) {
                    $actionentity = new \app\entites\ActionPointEntity(null, $ac);
                    array_push($this->response['action_detail_all'], $actionentity->getDetail());
                }
            return $this->response;
         }
          throw new \yii\web\UnauthorizedHttpException("Invalid username or password"); // 
       }
      
        
    }
     private function getUserlist($last_update_time = 000000) {
        return \app\models\UserModel::find()->select(['id', 'name', 'email', 'status', 'profile_image'])->where(['=', 'org_id', \Yii::$app->user->identity->org_id])->andWhere(['>', 'updated_at', $last_update_time])->orderBy('updated_at asc')->all();
    }
    public function actionUserimage($id) {
        print_r('Hello');
       // return \app\models\UserModel::find()->select(['id', 'name', 'email', 'status', 'profile_image'])->where(['=', 'org_id', \Yii::$app->user->identity->org_id])->andWhere(['>', 'updated_at', $last_update_time])->orderBy('updated_at asc')->all();
    }
    private function getWbslistall($last_update_time = 000000) {
	$wbs=array();
        $org_id=\Yii::$app->user->identity->org_id;
                 $owner=\Yii::$app->user->identity->id;
         $wbs_list= \app\models\Wbs::find()
 	 ->joinWith(['wbsusers'])->where(['=', 'wbs_user.created_by', \Yii::$app->user->identity->id])
                ->andWhere("wbs.status=1 and wbs.org_id ='".$org_id."'")               
	->orderBy('wbs.id DESC')
                ->all();
       $resultSets = [];
        foreach($wbs_list as $key => $data):
                $wbs_user = \app\models\WbsUser::findAll(["wbs_id" => $data->id]);
                $resultSets[$key]['id'] = $data->id;
                $resultSets[$key]['owner_id'] = $data->owner_id;
                $resultSets[$key]['wbs_title'] = $data->wbs_title;
                $resultSets[$key]['start_date'] = $data->start_date;
                $resultSets[$key]['end_date'] = $data->end_date;
                $resultSets[$key]['wbs_group_id'] = $data->wbs_group_id;
                $resultSets[$key]['wbs_user_id'] = $data->wbs_user_id;
                $resultSets[$key]['status'] = $data->status;
                $resultSets[$key]['users'] = array_values($wbs_user);
               $resultSets[$key]['notification'] = \app\models\Notification::find()->select(['id','detail_id','user_id','user_name','mail_status','status'])
                       ->where(['detail_id' => $data->id])
                       ->andWhere(['notification_status'=>1])
                       ->andWhere(['not in', 'user_id', \Yii::$app->user->identity->id])
                       ->all();
       endforeach;
              // $wbs['wbs_list'] = $resultSets;
               return $resultSets;
    }

    private function getGrouplist($last_update_time = 000000) {
	
 $group=array();
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("SELECT `user_group`.* FROM `user_group` INNER JOIN `user` ON FIND_IN_SET(user.id,user_group.users) > '0' AND user.id='".\Yii::$app->user->identity->id."' AND user_group.status=1 GROUP BY `user_group`.`id` ORDER BY `user_group`.`id` DESC");
$resultSets=[];       	
foreach($command->queryAll() as $key => $data):
                   // $wbs_user = \app\modules\api\v1\models\GroupUser::findAll(["group_id" => explode(",", $data['users'])]);
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
                    $resultSets[$key]['group_users']=\app\modules\api\v1\models\GroupUser::findAll(['group_id' => $data['id'],'status'=>1]);
                    $resultSets[$key]['notification'] = \app\models\Notification::find()->select(['detail_id','user_id','user_name','mail_status','status'])
                       ->where(['detail_id' => $data['id']])
                       ->all();
                     endforeach;
                    
        return $resultSets;
	
       // return \app\modules\api\v1\models\UserGroup::find()->select(['id', 'users', 'group_name', 'org_id', 'created_at', 'created_by', 'updated_at', 'updated_by','status'])->where(['=', 'org_id', \Yii::$app->user->identity->org_id])->andWhere(['>', 'updated_at', $last_update_time])->orderBy('updated_at asc')->all();
    }
     private function getMeetinglist($last_update_time = 000000) {
          return \app\models\Meeting::find()
                ->joinWith(['meetingusers'])
                ->where(['or', 'meeting_user.user_id='.\Yii::$app->user->identity->id,'meeting.responsible_user_id=' . \Yii::$app->user->identity->id])
                ->orderBy('meeting.id DESC')
                ->all();
     }

    private function getLastMeetingUpdateTime($last_update_time = 000000) {
        return \app\models\MeetingUser::find()->where(['=', 'user_id', \Yii::$app->user->identity->id])->max('updated_at');
    }

    private function getActionlist($last_update_time = 000000) {
        return  \app\models\ActionPoint::find()
                ->joinWith(['actionusers'])
                ->where(['or', 'action_user.user_id='.\Yii::$app->user->identity->id,'action_point.action_assigned_by=' . \Yii::$app->user->identity->id])
                //->andWhere(['=', 'meeting_id', '0'])
                ->orderBy('action_point.id DESC')
                ->all();
        }
        
        public function actionActionlist($last_update_time = 000000) {
        return  \app\models\ActionPoint::find()
                ->joinWith(['actionusers'])
                ->where(['or', 'action_user.user_id='.\Yii::$app->user->identity->id,'action_point.action_assigned_by=' . \Yii::$app->user->identity->id])
                //->andWhere(['=', 'meeting_id', '0'])
                ->orderBy('action_point.id DESC')
                ->all();
        }

    public function actionAdduser() {
        /** @var User $user */
         $this->php_input = file_get_contents("php://input");
        $this->post_json = json_decode(base64_decode($this->php_input), true);
        $this->post_json = json_decode($this->php_input, true);
        $this->data_json = $this->post_json['data'];
        $response = Yii::$app->response;
        
        $response->format = \yii\web\Response::FORMAT_JSON;
        $user = \Yii::createObject([
                    'class' => UserModel::className(),
                    'scenario' => 'create',
        ]);
        $user->role = UserModel::ROLE_ORG_USER;
        $user->org_id = \Yii::$app->user->identity->org_id;
        $user->status = 1;
        $user->name=$this->data_json['name'];
        $user->email=$this->data_json['email'];
        $user->username=$this->data_json['username'];
        $user->phone_no=$this->data_json['phone_no'];
        $user->password=$this->data_json['password'];
        $username=$user->username;
        $email=$user->email;
//        if(isset($this->data_json['username'])){
//             $response->data['message']='This username and email Already exits';
//        }
        $user_model = \app\modules\api\v1\models\User::find()
                ->where(["or","username="."'$username'","email="."'$email'"])->one();
        if($user_model){
            $response->data['status']='0';
            $response->data['message']='This username and email Already exits';
         return $response;
        }
        $event = $this->getUserEvent($user);
        $this->trigger(self::EVENT_BEFORE_CREATE, $event);
        if ($user->create()) {
            $response->data['status']='success';
            $response->data['data'] = \app\modules\api\v1\models\User::find()
                ->where(['id'=>$user->id])->one();
            $this->trigger(self::EVENT_AFTER_CREATE, $event);
         //  \app\entites\UserEntity::genrateNotification($user,1);
           return $response;
           }
        $response->data['status']='fail';
         return $response;
    }

     public function actionListall()
	    {
        $this->php_input = file_get_contents("php://input");
        $this->post_json = json_decode(base64_decode($this->php_input), true);
        $this->post_json = json_decode($this->php_input, true);
        $this->data_json = $this->post_json['data'];
        $response = Yii::$app->response;
        $response->format = \yii\web\Response::FORMAT_JSON;
        $response->data['status']='success';
       // $response->data['data'] = \app\modules\api\v1\models\User::find()->all();
        
 $group=array();
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("SELECT `user_group`.* FROM `user_group` INNER JOIN `user` ON FIND_IN_SET(user.id,user_group.users) > '0' AND user.id='".\Yii::$app->user->identity->id."' AND user_group.status=1 GROUP BY `user_group`.`id` ORDER BY `user_group`.`id` DESC");
$resultSets=[];       	
foreach($command->queryAll() as $key => $data):
                   // $wbs_user = \app\modules\api\v1\models\GroupUser::findAll(["group_id" => explode(",", $data['users'])]);
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
                    $resultSets[$key]['group_users']=\app\modules\api\v1\models\GroupUser::findAll(['group_id' => $data['id'],'status'=>1]);
                    $resultSets[$key]['notification'] = \app\models\Notification::find()->select(['detail_id','user_id','user_name','mail_status','status'])
                       ->where(['detail_id' => $data['id']])
                       ->all();
                     endforeach;
                    $response->data['data']=$resultSets;
        return $response;
            // return $response;
	    }
    protected function performAjaxValidation($model) {
        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax) {
            if ($model->load(\Yii::$app->request->post())) {
                \Yii::$app->response->format = Response::FORMAT_JSON;
                echo json_encode(ActiveForm::validate($model));
                \Yii::$app->end();
            }
        }
    }

}
