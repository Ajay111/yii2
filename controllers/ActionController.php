<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\widgets\ActiveForm;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\Response;
use yii\data\ArrayDataProvider;
use app\models\Wbs;
use app\models\WbsSearch;
use app\models\ActionPoint;
use app\entites\ActionPointEntity;
use app\models\ActionPointSearch;
use app\models\form\ActionCompleteForm;
use app\models\ActionHistory;

use app\models\Group;

/**
 * ActionController 
*/
class ActionController extends Controller{    
    public function behaviors() {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['index', 'add', 'update', 'summary', 'complete', 'runquery'],
                'rules' => [

                    [
                        'allow' => true,
                        'actions' => ['index', 'add', 'update', 'summary', 'complete', 'runquery',],
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex() {
        $searchModel = new ActionPointSearch();
        $searchModel->action_assigned_by = \Yii::$app->user->identity->id;
        $searchModel->daterange = $searchModel->GetMinDate() . ' to ' . $searchModel->GetMaxDate();
        
        if (isset($_REQUEST['_date']))
            $searchModel->daterange = $_REQUEST['_date'];

        $searchModel->SetDateRange($searchModel->daterange);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $allmodels = $dataProvider->getModels();
        
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    public function actionSummary() {
        $searchModelBy = new ActionPointSearch();
        $searchModelBy->action_assigned_by = \Yii::$app->user->identity->id;
        $BydataProvider = $searchModelBy->search(Yii::$app->request->queryParams);
        $BydataProvider->sort = ['defaultOrder' => ['deadline' => SORT_DESC]];
        $searchModelTo = new ActionPointSearch();
        $searchModelTo->action_assigned_to = \Yii::$app->user->identity->id;
        $TodataProvider = $searchModelTo->search(Yii::$app->request->queryParams);
        $TodataProvider->sort = ['defaultOrder' => ['deadline' => SORT_DESC]];

        return $this->render('summary', [
                    'BydataProvider' => $BydataProvider,
                    'searchModelBy' => $searchModelBy,
                    'TodataProvider' => $TodataProvider,
                    'searchModelTo' => $searchModelTo,
        ]);
    }

    public function actionAdd() {
        $this->view->title = 'Add Action';
        $this->view->params['icon'] = 'fa fa-plus';
        $action_model = NULL;
        $form_model = new \app\models\form\ActionForm(ActionPoint::SOURCE_DIRECT_ACTIONPOINT, $action_model);
        $form_model->action_assigned_by = \Yii::$app->user->identity->id;
        $form_model->reoccur = ActionPoint::ACTION_REOCCUR_ONETIME;
		$this->performAjaxValidation($form_model);
        if ($form_model->load(\Yii::$app->request->post()) && $form_model->validate()) {
             $actionuser = $form_model->muser;
            $actionuser_userId = array();
            if($actionuser[0]['user_id']){
            $actionuser_userId = $actionuser[0]['user_id'];
            }
            $form_model->action_user_id = implode(',', $actionuser_userId);
               
           // print_r($form_model->action_user_id);die;
            $actionuser_groupId = array();
            if(isset($actionuser[0]['group_id'])){
            $actionuser_groupId = $actionuser[0]['group_id'];
            }
            if(!empty($actionuser_groupId)) {
                //$form_model->muser ='';
                $form_model->action_group_id = '';
                $form_model->action_group_id = implode(',', $actionuser_groupId);
                //$selected_groups_ids = explode(",", $selected_groups_ids);
                $selected_groups_ids = $actionuser_groupId;
                //$group_member = Group::find()->where(['id' => $form_model->meeting_group_id ])->one();
                $groups_member_id = array();
                $array1 = array();
                foreach($selected_groups_ids as $row => $grp_id): 
                    $selected_member_group = '';
                    $group_member = \app\models\Group::findOne($grp_id);
                    $selected_member_group = $group_member->users;
                    $groups_member_id = explode(",", $selected_member_group);
                
                    $array1 = array_merge($array1,$groups_member_id);
                    
                endforeach;
               
                $array1 = array_unique($array1);
                $strUserId = implode(",",$array1);
               } 
            
            $selected_users_ids = $actionuser_userId;
            if(!empty($selected_users_ids)) {
               // $selected_users_ids = explode(",", $selected_users_ids);
                if(!empty($strUserId)){
                    $groups_member_id = explode(",", $strUserId);
                    $array1 = array_merge($selected_users_ids,$groups_member_id);
                    $array1 = array_unique($array1);
                    $strUserId = implode(",",$array1);
                }else{
                    //$strUserId = $form_model->meeting_user_id;
                    $strUserId = implode(",",$selected_users_ids);
                }
            }
            
           // print_r($form_model->muser);die;
            $form_model->muser = explode(",", $strUserId);
            $actionpointentity = new ActionPointEntity($form_model, $action_model);
                              
            if ($actionpointentity->save()) {
                \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Action have been added successfully'));
                return $this->redirect(['/action']);
            }
			
        }
        if (\Yii::$app->request->isAjax) {
            return $this->renderAjax('/action/add', [
                        'model' => $form_model,
            ]);
        } else {
            return $this->render('/action/add', [
                        'model' => $form_model,
            ]);
        }
    }

    public function actionView($action_id){
        $model = $this->findModelActionPoint($action_id);
        return $this->render('view', ['model' => $model,]);
    }

    public function actionUpdate($action_id) {
        $this->view->title = 'Update Action';
        $this->view->params['icon'] = 'fa fa-edit';
        $action_point = $this->findModelActionPoint($action_id);
        $action_model = \app\models\ActionPoint::getModel($action_id, \Yii::$app->user->identity->id);
        
        if ($action_model == null) {
            throw new \yii\web\ForbiddenHttpException("You are not allowed to perform this action." . $action_id);
        }
        
        if ($action_model->status == '1'){
            \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Can not update action mark as complete by ' . $action_model->assignto->name));
            return $this->redirect(['/action']);
        }
        
        $form_model = new \app\models\form\ActionForm($action_point->origin_source, $action_model);
        $form_model->action_assigned_by = \Yii::$app->user->identity->id;


        if ($action_model->origin_source == ActionPoint::SOURCE_MEETING) {
            $this->view->params['breadcrumbs'][] = ['label' => Yii::t('user', 'Schedule Action'), 'url' => ['/calendar/']];
            $form_model->reoccur = ActionPoint::ACTION_REOCCUR_ONETIME;
        }
        
        if ($action_model->origin_source == ActionPoint::SOURCE_MEETING_WBS) {
            $this->view->params['breadcrumbs'][] = ['label' => Yii::t('user', 'Schedule Action'), 'url' => ['/meeting']];
            $form_model->reoccur = ActionPoint::ACTION_REOCCUR_ONETIME;
        }
        
        if ($action_model->origin_source == ActionPoint::SOURCE_WBS_DIRECT) {
            $this->view->params['breadcrumbs'][] = ['label' => Yii::t('user', 'Schedule Action'), 'url' => ['/meeting/']];
            $form_model->scenario = \app\models\form\ActionForm::SCENARIO_WBSACTION;
        }
        
        if ($action_model->origin_source == ActionPoint::SOURCE_DIRECT_ACTIONPOINT) {
            $this->view->params['breadcrumbs'][] = ['label' => Yii::t('user', 'Schedule Action'), 'url' => ['/meeting/']];
            $form_model->reoccur = ActionPoint::ACTION_REOCCUR_ONETIME;
        }

        $this->view->params['breadcrumbs'][] = $this->view->title;

        $this->performAjaxValidation($form_model);
        if ($form_model->load(\Yii::$app->request->post())){
            if ($form_model->validate()) {
                
            $actionuser = $form_model->muser;
            $actionuser_userId = array();
            $actionuser_userId = $actionuser[0]['user_id'];
            $actionuser_groupId = array();
            $actionuser_groupId = $actionuser[0]['group_id'];
            $form_model->action_user_id = implode(',', $actionuser_userId);
            if(!empty($actionuser_groupId)) {
                //$form_model->muser ='';
                $form_model->action_group_id = '';
                $form_model->action_group_id = implode(',', $actionuser_groupId);
                $selected_groups_ids = $actionuser_groupId;
                $groups_member_id = array();
                $array1 = array();
                foreach($selected_groups_ids as $row => $grp_id): 
                    $selected_member_group = '';
                    $group_member = \app\models\Group::findOne($grp_id);
                    $selected_member_group = $group_member->users;
                    $groups_member_id = explode(",", $selected_member_group);
                
                    $array1 = array_merge($array1,$groups_member_id);
                    
                endforeach;
                
                $array1 = array_unique($array1);
                $strUserId = implode(",",$array1);
                //print $strUserId;
                //exit;
               
            } 
            
            $selected_users_ids = $actionuser_userId;
            if(!empty($selected_users_ids)) {
               // $selected_users_ids = explode(",", $selected_users_ids);
                if(!empty($strUserId)){
                    $groups_member_id = explode(",", $strUserId);
                    $array1 = array_merge($selected_users_ids,$groups_member_id);
                    $array1 = array_unique($array1);
                    $strUserId = implode(",",$array1);
                }else{
                    //$strUserId = $form_model->meeting_user_id;
                    $strUserId = implode(",",$selected_users_ids);
                }
            }
            
            $form_model->muser = explode(",", $strUserId);
                
                $actionpointentity = new ActionPointEntity($form_model, $action_point);
                if ($actionpointentity->save()) {
                    \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Action have been update successfully'));
                    return $this->redirect(['/action']);
                } else {
                    print_r($actionpointentity->model_action_point->errors);
                    exit;
                }
            } else {
                // print_r($form_model->errors);
                //exit;
            }
        }
        
        if (\Yii::$app->request->isAjax) {
            return $this->renderAjax('/action/add', ['model' => $form_model,]);
        } else {
            return $this->render('/action/add', ['model' => $form_model,]);
        }
    }
    
    public function actionComplete($action_id){
        //$actionhistory = ActionHistory::findAll(['action_id' => $action_id]);
        //print_r($actionhistory);
        
        $action_history_list = new ActionHistory();
        $history = $action_history_list->getHistory($action_id);
        
        $action_point = $this->findModelActionPoint($action_id);
        $action_model = \app\models\ActionPoint::findOne(['id' => $action_id, 'action_assigned_to' => \Yii::$app->user->identity->id]);
        
        if ($action_model == null) {
            throw new \yii\web\ForbiddenHttpException("You are not allowed to perform this action.");
        }
        
        if ($action_model->status){
            //comment by sonu shokeen, now on each statas user can view it action
           // \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Action mark as complete by' . $action_model->assignto->name));
            //return $this->redirect(['/action/summary']);
        }
        
        $form_model = new \app\models\form\ActionForm($action_model->origin_source, $action_model);
        if($form_model->load(\Yii::$app->request->post())){
            $form_model->status = $form_model->status;
            if ($form_model->status){
                $actionpointentity = new ActionPointEntity($form_model, $action_model);
                $actionpointentity -> save();
                
                \Yii::$app->session->setFlash('success', 'Action complete successfully');
            }else{
                \Yii::$app->session->setFlash('success', 'No change');
            }
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $this->redirect(['/action/summary']);
        }
        
        if (\Yii::$app->request->isAjax){
            return $this->renderAjax('_completeform', ['model' => $form_model, 'history' => $history]);
        } else {
            return $this->render('/action/add', ['model' => $form_model,]);
        }
    }
    
    protected function performAjaxValidation($models) {
        if (\Yii::$app->request->isAjax) {
            if (is_array($models)) {
                $result = [];
                foreach ($models as $model) {
                    if ($model->load(\Yii::$app->request->post())) {
                        \Yii::$app->response->format = Response::FORMAT_JSON;
                        $result = array_merge($result, ActiveForm::validate($model));
                    }
                }
                echo json_encode($result);
                \Yii::$app->end();
            } else {
                if ($models->load(\Yii::$app->request->post())) {
                    \Yii::$app->response->format = Response::FORMAT_JSON;
                    echo json_encode(ActiveForm::validate($models));
                    \Yii::$app->end();
                }
            }
        }
    }
    
    protected function findModelActionPoint($id) {
        $action_point = ActionPoint::findOne($id);
        if ($action_point === null) {
            throw new NotFoundHttpException('The requested action point page does not exist');
        }
        if ($action_point->action_assigned_to == \Yii::$app->user->identity->id || $action_point->action_assigned_by == \Yii::$app->user->identity->id) {
            return $action_point;
        } else {
            throw new \yii\web\ForbiddenHttpException("You are not allowed to perform this action." . $id);
        }
        return $action_point;
    }
    
    public function action1Runquery(){
        echo "<pre>";
        
        //$actions = ActionPoint::find()->limit(5)->all();
        $actions = ActionPoint::find()->all();
        $tableName = 'action_history';
        $bulkInsertArray = array();
        
        $ctime = time();
        foreach($actions as $act){
            //create default history of action while it create
            $bulkInsertArray[] = [
                'user_id' => \Yii::$app->user->identity->id,
                'action_id' => $act->id,
                'status' => 0,
                'remark' => '',
                'action' => $act->action,
                'action_assigned_to' => $act->action_assigned_to,
                'deadline' => $act->deadline,
                'action_assigned_by' => $act->action_assigned_by,
                'created_by' => $act->created_by,
                'created_at' => $ctime    
            ];
            
            //if action is updated after action is create
            if($act->status != 0){
                $bulkInsertArray[] = [
                    'user_id' => \Yii::$app->user->identity->id,
                    'action_id' => $act->id,
                    'status' => $act->status,
                    'remark' => '',
                    'action' => $act->action,
                    'action_assigned_to' => $act->action_assigned_to,
                    'deadline' => $act->deadline,
                    'action_assigned_by' => $act->action_assigned_by,
                    'created_by' => $act->action_assigned_by,
                    'created_at' => $ctime
                ];
            }
        }
        
        if(count($bulkInsertArray)>0){
            $columnNameArray=[
                'user_id',
                'action_id',
                'status',
                'remark',
                'action',
                'action_assigned_to',
                'deadline',
                'action_assigned_by',
                'created_by',
                'created_at'
            ];
            echo $insertCount = Yii::$app->db->createCommand()->batchInsert(
                $tableName, 
                $columnNameArray, 
                $bulkInsertArray
            )->execute();
        }
        echo "</pre>";
    }
}
