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
use app\models\MeetingComplaintSearch;
use app\models\MeetingOrderSearch;

/**
 * MeetingController 
 */
class MeetingController extends Controller {

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
                'only' => ['index', 'add', 'update', 'snooze', 'cancel', 'actionpoints', 'customerinfo', 'addorder', 'addcomplaint', 'addactionpoint', 'acceptdecline'],
                'rules' => [

                    [
                        'allow' => true,
                        'actions' => ['index', 'add', 'update', 'snooze', 'cancel', 'actionpoints', 'customerinfo', 'addorder', 'addcomplaint', 'addactionpoint', 'acceptdecline'],
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

    public function actionView($meeting_id) {
        $meeting = Meeting::getModel($meeting_id);
        $searchModelA = new ActionPointSearch();
        $searchModelA->origin_source = ActionPoint::SOURCE_MEETING;
        $searchModelA->meeting_id = $meeting->id;
        $AdataProvider = $searchModelA->search(Yii::$app->request->queryParams);
        $searchModelO = new MeetingOrderSearch();
        $searchModelO->meeting_id = $meeting->id;
        $OdataProvider = $searchModelO->search(Yii::$app->request->queryParams);
        $searchModelC = new MeetingComplaintSearch();
        $searchModelC->meeting_id = $meeting->id;
        $CdataProvider = $searchModelC->search(Yii::$app->request->queryParams);
        return $this->render('view', [
                    'model' => $meeting,
                    'AdataProvider' => $AdataProvider,
                    'searchModelA' => $searchModelA,
                    'OdataProvider' => $OdataProvider,
                    'searchModelO' => $searchModelO,
                    'CdataProvider' => $CdataProvider,
                    'searchModelC' => $searchModelC,
        ]);
    }

    public function actionActionpoints($meeting_id) {
        $meeting = Meeting::getModel($meeting_id);
        $searchModel = new ActionPointSearch();
        $searchModel->origin_source = ActionPoint::SOURCE_MEETING;
        $searchModel->meeting_id = $meeting->id;
        $searchModel->daterange = $searchModel->GetMinDate() . ' to ' . $searchModel->GetMaxDate();
        if (isset($_REQUEST['_date']))
            $searchModel->daterange = $_REQUEST['_date'];

        $searchModel->SetDateRange($searchModel->daterange);

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $allmodels = $dataProvider->getModels();
        return $this->render('actionpoints', [
                    'dataProvider' => $dataProvider,
                    'searchModel' => $searchModel,
                    'meeting' => $meeting
        ]);
    }

    public function actionCustomerinfo($meeting_id) {
        $meeting = Meeting::getModel($meeting_id);
        $searchModelO = new MeetingOrderSearch();
        $searchModelO->meeting_id = $meeting->id;
        $OdataProvider = $searchModelO->search(Yii::$app->request->queryParams);
        $searchModelC = new MeetingComplaintSearch();
        $searchModelC->meeting_id = $meeting->id;
        $CdataProvider = $searchModelC->search(Yii::$app->request->queryParams);

        return $this->render('customerinfo', [
                    'OdataProvider' => $OdataProvider,
                    'searchModelO' => $searchModelO,
                    'CdataProvider' => $CdataProvider,
                    'searchModelC' => $searchModelC,
                    'meeting' => $meeting,
        ]);
    }

    public function actionAdd() {
        $this->view->title = 'Add Meeting';
        $this->view->params['icon'] = 'fa fa-plus';
        $meeting_model = null;
        $form_model = new \app\models\form\MeetingForm(Meeting::SOURCE_GENERAL_MEETING, $meeting_model);
        $form_model->responsible_user_id = \Yii::$app->user->identity->id;
        $form_model->scenario = MeetingForm::SCENARIO_DIRECT_MEETING;
        $this->performAjaxValidation($form_model);
        if ($form_model->load(\Yii::$app->request->post()) && $form_model->validate()) {
            $muid = 0;
            $muser = $form_model->muser;
            $form_model->meeting_user_id ='';
            $form_model->meeting_user_id = implode(',', $muser[0]['user_id']);
           $muser_groupId = array();
            if(!empty($muser[0]['group_id'])){
                $muser_groupId = $muser[0]['group_id'];
            }
            $array1 = array();
                
            if(!empty($muser_groupId)) {
                $form_model->muser ='';
                $form_model->meeting_group_id = '';
                $form_model->meeting_group_id = implode(',', $muser_groupId);
                $selected_groups_ids = $muser_groupId;
                $groups_member_id = array();
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
            
            $muser_userId = array();
            if(!empty($muser[0]['user_id'])){
                $muser_userId = $muser[0]['user_id'];
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
             $form_model->muser =explode(",",$strUserId);
           //  print_r($form_model->muser);die;
             $meetingentity = new MeetingEntity($form_model);

            if ($meetingentity->save()) {
                \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Meeting have been added successfully'));
                return $this->redirect(['/meeting']);
            }
        }
        if (\Yii::$app->request->isAjax) {
            return $this->renderAjax('/meeting/add', [
                        'model' => $form_model,
            ]);
        } else {
            return $this->render('/calendar/add', [
                        'model' => $form_model,
            ]);
        }
    }

    public function actionAddorder($meeting_id) {
        $this->view->title = 'Add Meeting Order';
        $this->view->params['icon'] = 'fa fa-plus';
        $meeting_model = Meeting::getModel($meeting_id, \Yii::$app->user->identity->id);
        if ($meeting_model == null) {
            throw new \yii\web\ForbiddenHttpException("Forbidden. Meeting Id not found.");
        }
        if (($meeting_model->getMeetingprogressstatus() == Meeting::MEETING_PROGRESS_STATUS_ONGOING || $meeting_model->getMeetingprogressstatus() == Meeting::MEETING_PROGRESS_STATUS_FINISHED)) {
            
        } else {
            $message = 'You can not add meeting order .';
            if ($meeting_model->status == Meeting::MEETING_PROGRESS_STATUS_PLANNED) {
                $message .=' Meeting is planned';
            } elseif ($meeting_model->status == Meeting::MEETING_PROGRESS_STATUS_CANNCELED) {
                $message .=' Meeting is canceled';
            } else {
                $message .=' Meeting is not performed';
            }
            \Yii::$app->getSession()->setFlash('success', \Yii::t('user', $message));
            return $this->redirect(['/meeting']);
        }
        $form_model_order = new \app\models\form\MeetingOrderForm();
        $form_model_order->meeting_id = $meeting_model->id;
        $form_model_order->ordermodel = Yii::createObject([
                    'class' => \app\models\MeetingOrder::className(),
        ]);

        if ($form_model_order->load(\Yii::$app->request->post()) && $form_model_order->validate()) {
            $meetingentity = new MeetingEntity(null, $meeting_model);

            if ($meetingentity->saveOrder($form_model_order)) {
                \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Meeting Order have been added successfully'));
                return $this->redirect(['/meeting']);
            }
        }
        if (\Yii::$app->request->isAjax) {
            return $this->renderAjax('/meeting/_orderform', [
                        'model' => $form_model_order,
            ]);
        } else {
            return $this->render('/meeting/_orderform', [
                        'model' => $form_model_order,
            ]);
        }
    }

    public function actionAddcomplaint($meeting_id) {
        $this->view->title = 'Add Meeting Complaint';
        $this->view->params['icon'] = 'fa fa-plus';
        $meeting_model = Meeting::getModel($meeting_id, \Yii::$app->user->identity->id);
        if ($meeting_model == null) {
            throw new \yii\web\ForbiddenHttpException("Forbidden. Meeting Id not found.");
        }
        if (($meeting_model->getMeetingprogressstatus() == Meeting::MEETING_PROGRESS_STATUS_ONGOING || $meeting_model->getMeetingprogressstatus() == Meeting::MEETING_PROGRESS_STATUS_FINISHED)) {
            
        } else {
            $message = 'You can not add meeting complaint .';
            if ($meeting_model->status == Meeting::MEETING_PROGRESS_STATUS_PLANNED) {
                $message .=' Meeting is planned';
            } elseif ($meeting_model->status == Meeting::MEETING_PROGRESS_STATUS_CANNCELED) {
                $message .=' Meeting is canceled';
            } else {
                $message .=' Meeting is not performed';
            }
            \Yii::$app->getSession()->setFlash('success', \Yii::t('user', $message));
            return $this->redirect(['/meeting']);
        }
        $form_model_complaint = new \app\models\form\MeetingComplaintForm();
        $form_model_complaint->meeting_id = $meeting_model->id;
        $form_model_complaint->complaintmodel = Yii::createObject([
                    'class' => \app\models\MeetingComplaint::className(),
        ]);

        if ($form_model_complaint->load(\Yii::$app->request->post()) && $form_model_complaint->validate()) {

            $meetingentity = new MeetingEntity(null, $meeting_model);

            if ($meetingentity->saveComplaint($form_model_complaint)) {
                \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Meeting Complaint have been added successfully'));
                return $this->redirect(['/meeting']);
            }
        }
        if (\Yii::$app->request->isAjax) {
            return $this->renderAjax('/meeting/_complaintform', [
                        'model' => $form_model_complaint,
            ]);
        } else {
            return $this->render('/meeting/_complaintform', [
                        'model' => $form_model_complaint,
            ]);
        }
    }

    public function actionAddactionpoint($meeting_id) {
        $this->view->title = 'Add Meeting Action Point';
        $this->view->params['icon'] = 'fa fa-plus';
        $this->view->params['breadcrumbs'][] = ['label' => Yii::t('user', 'Schedule Meeting'), 'url' => ['index']];
        $this->view->params['breadcrumbs'][] = $this->view->title;
        $action_model = NULL;
        $meeting = Meeting::getModel($meeting_id, \Yii::$app->user->identity->id);
        if ($meeting == null) {
            throw new \yii\web\ForbiddenHttpException("Forbidden. Meeting Id not found.");
        }
        if (($meeting->getMeetingprogressstatus() == Meeting::MEETING_PROGRESS_STATUS_ONGOING || $meeting->getMeetingprogressstatus() == Meeting::MEETING_PROGRESS_STATUS_FINISHED)) {
            
        } else {
            $message = 'You can not add action point.';
            if ($meeting->status == Meeting::MEETING_PROGRESS_STATUS_PLANNED) {
                $message .=' Meeting is planned';
            } elseif ($meeting->status == Meeting::MEETING_PROGRESS_STATUS_CANNCELED) {
                $message .=' Meeting is canceled';
            } else {
                $message .=' Meeting is not performed';
            }
            \Yii::$app->getSession()->setFlash('success', \Yii::t('user', $message));
            return $this->redirect(['/meeting']);
        }
        $wbs_id = $meeting->wbs_id;
        $wbs = Wbs::findOne($wbs_id);
        if ($wbs_id == 0)
            $form_model = new \app\models\form\ActionForm(ActionPoint::SOURCE_MEETING, null, $meeting);
        else
            $form_model = new \app\models\form\ActionForm(ActionPoint::SOURCE_MEETING_WBS, null, $meeting, $wbs);

        $form_model->action_assigned_by = \Yii::$app->user->identity->id;
        $form_model->reoccur = ActionPoint::ACTION_REOCCUR_ONETIME;
        $this->performAjaxValidation($form_model);
        if ($form_model->load(\Yii::$app->request->post()) && $form_model->validate()) {
            $actionpointentity = new ActionPointEntity($form_model, $action_model);
            if ($actionpointentity->save()) {
                \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Meeting Action Point have been added successfully'));
                return $this->redirect(['/meeting']);
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

    public function actionSnooze($meeting_id) {

        $this->view->title = 'Snooze Meeting';
        $this->view->params['icon'] = 'fa fa-bell';
        $meeting = Meeting::getModel($meeting_id, \Yii::$app->user->identity->id);
        $meeting_model = \app\models\Meeting::getModel($meeting_id, null);
        $form_model = new \app\models\form\MeetingForm($meeting->origin_source, $meeting);


        $form_model->meeting = $meeting;
        $form_model->meeting_id = $meeting->id;
        //$form_model->responsible_user_id = \Yii::$app->user->identity->id;

        $form_model->scenario = MeetingForm::SCENARIO_SNOOZE;

        $this->performAjaxValidation($form_model);
        if ($form_model->load(\Yii::$app->request->post())) {

//            if ($form_model->responsible_user_id == '')
//                $form_model->responsible_user_id = $meeting->responsible_user_id;
            if ($form_model->validate()) {
                $meetingentity = new MeetingEntity($form_model, $meeting);

                if ($meetingentity->snooze()) {

                    \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Meeting ' . $form_model->snooze_time . ' Min snooze successfully'));
                    return $this->redirect(['/meeting']);
                }
            }
        }
        if (\Yii::$app->request->isAjax) {
            return $this->renderAjax('/meeting/_snoozeform', [
                        'model' => $form_model,
            ]);
        } else {
            return $this->render('/meeting/_snoozeform', [
                        'model' => $form_model,
            ]);
        }
    }

    public function actionCancel($meeting_id) {

        $this->view->title = 'Cancel Meeting';
        $this->view->params['icon'] = 'fa fa-remove';
        $meeting_model = Meeting::getModel($meeting_id, \Yii::$app->user->identity->id);
        if ($meeting_model == null) {
            throw new \yii\web\ForbiddenHttpException("Forbidden. Meeting Id not found.");
        }
        if ($meeting_model->getMeetingprogressstatus() !== Meeting::MEETING_PROGRESS_STATUS_PLANNED) {
            return $this->redirect(['/meeting']);
        }
        $form_model = new \app\models\form\MeetingForm($meeting_model->origin_source, $meeting_model);
        $form_model->meeting = $meeting_model;
        $form_model->meeting_id = $meeting_model->id;

        $form_model->scenario = MeetingForm::SCENARIO_CANCEL;
        $this->performAjaxValidation($form_model);
        if ($form_model->load(\Yii::$app->request->post())) {
            if ($form_model->validate()) {
                $meetingentity = new MeetingEntity($form_model, $meeting_model);

                if ($meetingentity->cancel()) {

                    \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Meeting Cancel successfully'));
                    return $this->redirect(['/meeting']);
                }
            }
        }
        if (\Yii::$app->request->isAjax) {
            return $this->renderAjax('/meeting/_cancelform', [
                        'model' => $form_model,
            ]);
        } else {
            return $this->render('/meeting/_cancelform', [
                        'model' => $form_model,
            ]);
        }
    }

    public function actionUpdate($meeting_id) {
        $this->view->title = 'Update Meeting';
        $this->view->params['icon'] = 'fa fa-edit';
        $meeting_model = Meeting::getModel($meeting_id, \Yii::$app->user->identity->id);
        if ($meeting_model == null){
            throw new \yii\web\ForbiddenHttpException("Forbidden. Meeting Id not found.");
        }
        if ($meeting_model->getMeetingprogressstatus() == Meeting::MEETING_PROGRESS_STATUS_PLANNED) {
            
        } else {
            $message = 'You can not update meeting';
            \Yii::$app->getSession()->setFlash('success', \Yii::t('user', $message));
            return $this->redirect(['/meeting']);
        }
        $form_model = new \app\models\form\MeetingForm($meeting_model->origin_source, $meeting_model);
        //print_r($form_model);
        //exit;
        if ($meeting_model->origin_source == Meeting::SOURCE_CALENDAR) {
            $this->view->params['breadcrumbs'][] = ['label' => Yii::t('user', 'Schedule Meeting'), 'url' => ['/calendar/']];
            $form_model->scenario = MeetingForm::SCENARIO_CALENDER_MEETING;
        }
        if ($meeting_model->origin_source == Meeting::SOURCE_WBS) {
            $this->view->params['breadcrumbs'][] = ['label' => Yii::t('user', 'Schedule Meeting'), 'url' => ['/meeting']];
            $form_model->scenario = MeetingForm::SCENARIO_WBS_MEETING;
        }
        if ($meeting_model->origin_source == Meeting::SOURCE_GENERAL_MEETING) {
            $this->view->params['breadcrumbs'][] = ['label' => Yii::t('user', 'Schedule Meeting'), 'url' => ['/meeting/']];
            $form_model->scenario = MeetingForm::SCENARIO_DIRECT_MEETING;
        }

        $this->view->params['breadcrumbs'][] = $this->view->title;

        $this->performAjaxValidation($form_model);
        if ($form_model->load(\Yii::$app->request->post())) {
            if ($form_model->validate()) {
            
            $muser = $form_model->muser;
            $muser_userId = array();
            $muser_userId = $muser[0]['user_id'];
//            $muser_groupId = array();
//            $muser_groupId = $muser[0]['group_id'];
//           
//            $form_model->meeting_user_id ='';
//            $form_model->meeting_user_id = implode(',', $muser[0]['user_id']);
            if(!empty($muser_groupId)) {
                $form_model->muser ='';
                $form_model->meeting_group_id = '';
                $form_model->meeting_group_id = implode(',', $muser_groupId);
               
                $selected_groups_ids = $muser_groupId;
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
            
           
            $form_model->muser = explode(",", \Yii::$app->user->identity->id.",".$strUserId);
                
                $meetingentity = new MeetingEntity($form_model, $meeting_model);
                
                if ($meetingentity->save()) {
                    \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Meeting have been update successfully'));
                    return $this->redirect(['/meeting']);
                }
            }
        }
        if (\Yii::$app->request->isAjax) {
            return $this->renderAjax('/meeting/update', [
                        'model' => $form_model,
            ]);
        } else {
            return $this->render('/meeting/update', [
                        'model' => $form_model,
            ]);
        }
    }

    public function actionAcceptdecline($meeting_id, $meeting_user_id) {
        $meeting_model = \app\models\Meeting::getModel($meeting_id, null);
        if ($meeting_model == null) {
            throw new \yii\web\ForbiddenHttpException("Forbidden. Meeting Id not found.");
        }
        $meeting_user = $this->findModelMeetingUser($meeting_user_id);
        $form_accept_decline = new \app\models\form\MeetingAcceptDeclineForm($meeting_user, $meeting_model);
        $form_accept_decline->meeting_id = $meeting_id;
        $this->performAjaxValidation($form_accept_decline);
        if ($form_accept_decline->load(\Yii::$app->request->post()) && $form_accept_decline->validate()) {
            $meetingentity = new MeetingEntity(null, $meeting_model);
            $meetingentity->saveAcceptdecline($form_accept_decline);

            $message = "Meeting ";
            if ($form_accept_decline->availability == MeetingUser::MEETING_AVAILABILITY_STATUS__ACCEOPT)
                $message .=" Accept successfully ";
            if ($form_accept_decline->availability == MeetingUser::MEETING_AVAILABILITY_STATUS__DECLINE)
                $message .=" Decline successfully ";
            Yii::$app->session->setFlash('success', $message);
            return $this->redirect(['/meeting']);
        }
        if (\Yii::$app->request->isAjax) {
            return $this->renderAjax('_acceptdeclineform', [
                        'model' => $form_accept_decline,
                        'meeting_user' => $meeting_user,
            ]);
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

    protected function findModelMeetingUser($id) {
        $meeting_user = MeetingUser::findOne($id);
        if ($meeting_user === null) {
            throw new NotFoundHttpException('Meeting User not found');
        }

        return $meeting_user;
    }

}
