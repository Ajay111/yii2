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
use app\models\Meeting;
use app\models\MeetingSearch;
use app\models\MeetingUser;
use app\models\MeetingUserSearch;
use app\models\form\MeetingForm;
use app\entites\MeetingEntity;
use app\models\ActionPoint;
use app\entites\ActionPointEntity;
use app\models\ActionPointSearch;
use app\models\Group;
use app\models\WbsUser;
use app\entites\WbsEntity;

/**
 * CalendarController 
 */
class CalendarController extends Controller {

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
                'only' => ['index', 'add', 'wbs', 'addwbs', 'updatewbs', 'wbsview'],
                'rules' => [

                    [
                        'allow' => true,
                        'actions' => ['index', 'add', 'wbs', 'addwbs', 'updatewbs', 'wbsview'],
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex() {
        $searchModel = new MeetingUserSearch();
        $searchModel->user_id = \Yii::$app->user->identity->id;
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

    public function actionWbs() {
        $searchModel = new WbsSearch();
        $searchModel->owner_id = \Yii::$app->user->identity->id;
        $searchModel->id = ArrayHelper::getColumn($this->getMeetingwbs(), 'wbs_id');
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('wbs', ['dataProvider' => $dataProvider, 'model' => $searchModel]);
    }

    public function actionWbsview($wbs_id) {
        $model = $this->findModelWbs($wbs_id);
        $searchModel = new MeetingUserSearch();
        $searchModel->wbs_id = $model->id;
        $searchModel->user_id = \Yii::$app->user->identity->id;
        $searchModel->daterange = $searchModel->GetMinDate() . ' to ' . $searchModel->GetMaxDate();
        if (isset($_REQUEST['_date']))
            $searchModel->daterange = $_REQUEST['_date'];

        $searchModel->SetDateRange($searchModel->daterange);

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $searchModela = new ActionPointSearch();
        $searchModela->action_assigned_by = \Yii::$app->user->identity->id;
        $searchModela->wbs_id = $model->id;
        $searchModela->daterange = $searchModela->GetMinDate() . ' to ' . $searchModela->GetMaxDate();
        if (isset($_REQUEST['_date']))
            $searchModela->daterange = $_REQUEST['_date'];

        $searchModela->SetDateRange($searchModela->daterange);
        $AdataProvider = $searchModela->search(Yii::$app->request->queryParams);
        return $this->render('wbsview', ['model' => $model,
                    'dataProvider' => $dataProvider,
                    'searchModel' => $searchModel,
                    'AdataProvider' => $AdataProvider,
                    'searchModela' => $searchModela,
        ]);
    }

    public function actionAdd(){
        $this->view->title = 'Add Calendar';
        $this->view->params['icon'] = 'fa fa-plus';
        $meeting_model = null;
        $form_model = new \app\models\form\MeetingForm(Meeting::SOURCE_CALENDAR, $meeting_model);
        $form_model->responsible_user_id = \Yii::$app->user->identity->id;
        $form_model->scenario = MeetingForm::SCENARIO_CALENDER_MEETING;
        $this->performAjaxValidation($form_model);
        if ($form_model->load(\Yii::$app->request->post()) && $form_model->validate()) {
            $muser = $form_model->muser;
            $muser_userId = array();
            $muser_userId = $muser[0]['user_id'];
	    $form_model->meeting_user_id = '';
            $form_model->meeting_user_id = implode(',', $muser_userId);
            
            $muser_groupId = array();
            $muser_groupId = $muser[0]['group_id'];
           
            if(!empty($muser_groupId)) {
                $form_model->muser ='';
                $form_model->meeting_group_id = '';
                $form_model->meeting_group_id = implode(',', $muser_groupId);
                $selected_groups_ids = $muser_groupId;
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
            
            $selected_users_ids = $muser_userId;
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
            
         // $form_model->muser = explode(",", \Yii::$app->user->identity->id.",".implode(',', $strUserId));
            $form_model->muser = explode(",", \Yii::$app->user->identity->id.",".$strUserId);
            
            $meetingentity = new MeetingEntity($form_model, $meeting_model);

            if ($meetingentity->save()) {
                \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Calendar meeting have been added successfully'));
                return $this->redirect(['/calendar']);
            } else {
                //  print_r($meetingentity->model_meeting->errors); exit;
            }
        }
        if (\Yii::$app->request->isAjax) {
            return $this->renderAjax('/calendar/add', [
                'model' => $form_model,
            ]);
        } else {
            return $this->render('/calendar/add', [
                'model' => $form_model,
            ]);
        }
    }

    public function actionAddwbsmeeting($wbs_id) {
        $this->view->title = 'Add WBS Meeting';
        $this->view->params['icon'] = 'fa fa-plus';
        $meeting_model = null;
        $wbs = $this->findModelWbs($wbs_id);
        $form_model = new \app\models\form\MeetingForm(Meeting::SOURCE_WBS, $meeting_model);
        $form_model->responsible_user_id = \Yii::$app->user->identity->id;
        $form_model->wbs_id = $wbs->id;
        
        $wbsuser = $this->findModelWbsUser($wbs_id);
        
        $muser_groupId = $wbs->wbs_group_id;
        if(!empty($muser_groupId)) {
              
                
                $selected_groups_ids = explode(",", $muser_groupId);
                
                 $muser[0]['group_id'] = $selected_groups_ids;
                 
                 
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
            // print_r($array1);
            // exit;
            //$muser[0]['user_id'] = ArrayHelper::getColumn($this->meeting->meetingusers, 'user_id');
                
            // For Check user is exist in group or not
            if(!empty($array1)){    
                $user_member_id = array();
                
                //$selected_users_ids = ArrayHelper::getColumn($this->meeting->meetingusers, 'user_id');
                
                $selected_users_ids = $wbsuser;
                foreach($selected_users_ids as $row => $usr_id): 
                    if (!in_array($usr_id, $array1))
                    {
                        $user_member_id[] = $usr_id;
                    }
                endforeach;
                $muser[0]['user_id'] = $user_member_id;
            }else{
                
                $muser[0]['user_id'] = ArrayHelper::getColumn($this->meeting->meetingusers, 'user_id');
            }
        
        $form_model->muser = $muser;
        //print_r($form_model);
        //exit;
        
        $form_model->scenario = MeetingForm::SCENARIO_WBS_MEETING;
        $this->performAjaxValidation($form_model);
        if ($form_model->load(\Yii::$app->request->post()) && $form_model->validate()) {
            //$form_model->muser = explode(",", \Yii::$app->user->identity->id . "," . implode(',', $form_model->muser));
            //$meetingentity = new MeetingEntity($form_model, $meeting_model);
            
            $muser = $form_model->muser;
            $muser_userId = array();
            $muser_userId = $muser[0]['user_id'];
            $muser_groupId = array();
            $muser_groupId = $muser[0]['group_id'];
           
            if(!empty($muser_groupId)) {
                $form_model->muser ='';
                $form_model->meeting_group_id = '';
                $form_model->meeting_group_id = implode(',', $muser_groupId);
               
                //$selected_groups_ids = explode(",", $selected_groups_ids);
                $selected_groups_ids = $muser_groupId;
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
                //print $strUserId;
                //exit;
               
            } 
            
            $selected_users_ids = $muser_userId;
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
            $form_model->meeting_group_id=$wbs->wbs_group_id;
             $form_model->meeting_user_id=$wbs->wbs_user_id;
             //$form_model->muser = explode(",", \Yii::$app->user->identity->id.",".implode(',', $strUserId));
            $form_model->muser = explode(",", \Yii::$app->user->identity->id.",".$strUserId);
            
            //$form_model->muser = explode(",", \Yii::$app->user->identity->id.",".$strUserId);
            //print_r($form_model);
            //exit;
            $meetingentity = new MeetingEntity($form_model, $meeting_model);
            
            if ($meetingentity->save()) {
                \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'WBS meeting have been added successfully'));
                return $this->redirect(['calendar/wbsview?wbs_id=' . $wbs->id]);
            }
        }
        if (\Yii::$app->request->isAjax) {
            return $this->renderAjax('/calendar/addwbsmeeting', [
                        'model' => $form_model,
            ]);
        } else {
            return $this->render('/calendar/addwbsmeeting', [
                        'model' => $form_model,
            ]);
        }
    }

    public function actionAddwbs() {
        $this->view->title = 'Add WBS Category';
        $this->view->params['icon'] = 'fa fa-plus';
        $wbs_model = Yii::createObject([
                    'class' => \app\models\Wbs::className(),
        ]);
        $form_model = new \app\models\form\WbsForm();

        $this->performAjaxValidation($form_model);
        if ($form_model->load(\Yii::$app->request->post()) && $form_model->validate()) {
           
            $wbsuser = $form_model->wbsuser;
            $wbsuser_userId = array();
            $wbsuser_userId = $wbsuser[0]['user_id'];
            $wbsuser_groupId = array();
            $wbsuser_groupId = $wbsuser[0]['group_id'];
           
            $form_model->wbs_user_id ='';
            $form_model->wbs_user_id = implode(',', $wbsuser_userId);
            
            $form_model->wbs_group_id = '';
            $form_model->wbs_group_id = implode(',', $wbsuser_groupId);
                
            if(!empty($wbsuser_groupId)) {
                $form_model->wbsuser ='';
                $selected_groups_ids = $wbsuser_groupId;
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
            
            $selected_users_ids = $wbsuser_userId;
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
            
            $form_model->wbsuser = explode(",", \Yii::$app->user->identity->id.",".$strUserId);
            // print_r(\Yii::$app->request->post());
            // exit;
            $wbsentity = new \app\entites\WbsEntity($form_model);
            if ($wbsentity->save()) {

                \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Wbs Category have been added successfully'));

                return $this->redirect(['calendar/wbs']);
            }
        }
        if (\Yii::$app->request->isAjax) {
            //    return $this->renderAjax('/calendar/_wbsform', [
            //                'model' => $form_model,
            //    ]);
        } else {
            return $this->render('/calendar/_wbsform', [
                        'model' => $form_model,
            ]);
        }
    }

    public function actionUpdatewbs($wbs_id) {
        $this->view->title = 'Update WBS Category';
        $this->view->params['icon'] = 'fa fa-edit';
        $wbs_model = \app\models\Wbs::getModel($wbs_id, \Yii::$app->user->identity->id);
        if ($wbs_model == null) {
            throw new \yii\web\ForbiddenHttpException("You are not authorized to perform this action");
        }
        $form_model = new \app\models\form\WbsForm($wbs_id);

       // print_r($form_model);
       // exit;
        
        $this->performAjaxValidation($form_model);
        if ($form_model->load(\Yii::$app->request->post()) && $form_model->validate()) {
            
            // print_r(\Yii::$app->request->post());
            // exit;
            
            $wbsuser = $form_model->wbsuser;
            $wbsuser_userId = array();
            $wbsuser_userId = $wbsuser[0]['user_id'];
            $wbsuser_groupId = array();
            $wbsuser_groupId = $wbsuser[0]['group_id'];
           $form_model->wbs_user_id ='';
            $form_model->wbs_user_id = implode(',', $wbsuser_userId);
            
            if(!empty($wbsuser_groupId)) {
                $form_model->wbsuser ='';
                $form_model->wbs_group_id = '';
                $form_model->wbs_group_id = implode(',', $wbsuser_groupId);
               
                //$selected_groups_ids = explode(",", $selected_groups_ids);
                $selected_groups_ids = $wbsuser_groupId;
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
                //print $strUserId;
                //exit;
               
            } 
            
            $selected_users_ids = $wbsuser_userId;
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
            
            $form_model->wbsuser = explode(",", \Yii::$app->user->identity->id.",".$strUserId);
            
           // print_r($form_model->wbsuser);
           // exit;
            
            $wbsentity = new \app\entites\WbsEntity($form_model);
            if ($wbsentity->save()) {
                \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Wbs Category have been updated successfully'));
                return $this->redirect(['calendar/wbs']);
            }
        }
        if (\Yii::$app->request->isAjax) {
            return $this->renderAjax('/calendar/_wbsform', [
                        'form_model' => $form_model,
            ]);
        } else {
            return $this->render('/calendar/_wbsform', [
                        'model' => $form_model,
            ]);
        }
    }

    public function actionAddwbsaction($wbs_id) {
        $this->view->title = 'Add WBS Action Point';
        $this->view->params['icon'] = 'fa fa-plus';
        $action_model = null;
        $wbs = $this->findModelWbs($wbs_id);
        $form_model = new \app\models\form\ActionForm(ActionPoint::SOURCE_WBS_DIRECT, null, null, $wbs);
        $form_model->action_assigned_by = \Yii::$app->user->identity->id;
        
        $wbsuser = $this->findModelWbsUser($wbs_id);
        
        $muser_groupId = $wbs->wbs_group_id;
        
        if(!empty($muser_groupId)) {
              
                
                $selected_groups_ids = explode(",", $muser_groupId);
                
                 $muser[0]['group_id'] = $selected_groups_ids;
                 
                 
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
            // print_r($array1);
            // exit;
            //$muser[0]['user_id'] = ArrayHelper::getColumn($this->meeting->meetingusers, 'user_id');
                
            // For Check user is exist in group or not
            if(!empty($array1)){    
                $user_member_id = array();
                
                //$selected_users_ids = ArrayHelper::getColumn($this->meeting->meetingusers, 'user_id');
                
                $selected_users_ids = $wbsuser;
                foreach($selected_users_ids as $row => $usr_id): 
                    if (!in_array($usr_id, $array1))
                    {
                        $user_member_id[] = $usr_id;
                    }
                endforeach;
                $muser[0]['user_id'] = $user_member_id;
            }else{
                
                $muser[0]['user_id'] = ArrayHelper::getColumn($this->wbs->wbsusers, 'user_id');
            }
        
        $form_model->action_assigned_to = $muser;
        
        $form_model->scenario = \app\models\form\ActionForm::SCENARIO_WBSACTION;
        $this->performAjaxValidation($form_model);
        if ($form_model->load(\Yii::$app->request->post()) && $form_model->validate()) {
            
            // print_r(\Yii::$app->request->post());
            // exit;
            
            $actionuser = $form_model->action_assigned_to;
            $actionuser_userId = array();
            $actionuser_userId = $actionuser[0]['user_id'];
            $actionuser_groupId = array();
            $actionuser_groupId = $actionuser[0]['group_id'];
           
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
            
            $form_model->action_assigned_to = explode(",", $strUserId);
            
            
            $actionpointentity = new ActionPointEntity($form_model,$action_model);
            if ($actionpointentity->save()) {
                \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'WBS Action Point have been added successfully'));
                return $this->redirect(['/calendar/wbsview?wbs_id=' . $wbs->id]);
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

    protected function findModelWbs($id) {
        $wbs = Wbs::findOne($id);
        if ($wbs === null) {
            throw new NotFoundHttpException('The requested page does not exist');
        }

        return $wbs;
    }
    
    protected function findModelWbsUser($id) {
        
        // return $this->hasMany(WbsUser::className(), ['wbs_id' => $id])->where(['wbs_user.status' => 1]);
        // return $this->hasMany(WbsUser::className(), ['wbs_id' => 'id']);
         $wbs_user_data = \app\models\WbsUser::find('user_id')->where(['=', 'wbs_user.wbs_id', $id])->andWhere(['=', 'wbs_user.status', 1])->all();
         $result = [];
                foreach ($wbs_user_data as $wbs_user_data) {
                    //if ($model->load(\Yii::$app->request->post())) {
                        //\Yii::$app->response->format = Response::FORMAT_JSON;
                        $result[] = $wbs_user_data->user_id;
                    //}
                }
         return $result;
//        $wbs_user = WbsUser::find($id);
//        if ($wbs_user === null) {
//            throw new NotFoundHttpException('Wbs User not found');
//        }
//        return $wbs_user;
    }
    
    protected function findModelMeeting($id) {
        $meeting = Meeting::findOne($id);
        if ($meeting === null) {
            throw new NotFoundHttpException('The requested page does not exist');
        }

        return $meeting;
    }

    private function getMeetingwbs() {
        return \app\models\Meeting::find()->joinWith(['meetingusers'])->where(['=', 'meeting_user.user_id', \Yii::$app->user->identity->id])->andWhere(['!=', 'wbs_id', 0])->all();
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

}
