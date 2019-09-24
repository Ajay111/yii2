<?php

namespace app\controllers;

use Yii;

use yii\web\Controller;
use yii\filters\AccessControl;
use yii\widgets\ActiveForm;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\Response;
use yii\data\ArrayDataProvider;
use app\models\Broadcast;
use app\models\BroadcastSearch;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * BroadcastController implements the CRUD actions for Broadcast model.
 */
class BroadcastController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Broadcast models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new BroadcastSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Broadcast model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        
        $broadcast = \app\models\BroadcastUser::find()->where(['=', 'brodecast_id', $id])->orderBy(['id' => SORT_DESC])->all();
        return $this->render('view', [
            'model' => $this->findModel($id),
            'broadcast'=>$broadcast,
        ]);
    }

    /**
     * Creates a new Broadcast model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $this->view->title = 'Add Broadcast';
        $this->view->params['icon'] = 'fa fa-plus';
        $broadcast_model = Yii::createObject([
                    'class' => \app\models\Broadcast::className(),
        ]);
         $form_model = new \app\models\form\BroadcastForm();
        // print_r($broadcast_model->attributes);die;
        $this->performAjaxValidation($form_model);
       //  print_r(Yii::$app->request->post());die;
        if ($form_model->load(Yii::$app->request->post()) && $form_model->validate()) {
             $broadcast_model = null;
            if (isset($form_model->notification_type) && $form_model->notification_type != ''){
                if($form_model->notification_type==1){
                    $form_model->notification_sub_type=602;
                }
                else if($form_model->notification_type==2){
                    $form_model->notification_sub_type=601;
                }
                else
                    $form_model->notification_sub_type=603;
            }
           
           
            $muser = $form_model->muser;
            $user_userId = array();
            if($muser[0]['user_id']){
            $user_userId = $muser[0]['user_id'];
            $form_model->broadcast_user_id = implode(',', $user_userId);
            }else{
            $form_model->broadcast_user_id = implode(',', $user_userId);
            }
             $group_groupId = array();
            if(isset($muser[0]['group_id'])){
            $group_groupId = $muser[0]['group_id'];
            }else {
                $form_model->broadcast_group_id = implode(',', $group_groupId);
            }
                    $array1 = array();
                    if(!empty($group_groupId)) {
                            $form_model->broadcast_group_id = '';
                             $form_model->broadcast_group_id = implode(',', $group_groupId);
                            $groups_member_id = array();
                            foreach($group_groupId as $row => $grp_id): 
                            $selected_member_group = '';
                            $group_member = \app\models\Group::findOne($grp_id);
                            $selected_member_group = $group_member->users;
                            $groups_member_id = explode(",", $group_member->users);
                            $array1 = array_merge($array1,$groups_member_id);
                            endforeach;
                            $array1 = array_unique($array1);
                            $strUserId = implode(",",$array1);
                     }
                      
                      $selected_users_ids = $user_userId;
                    if(!empty($selected_users_ids)) {
                        if(!empty($strUserId)){
                            $groups_member_id = explode(",", $strUserId);
                            $array1 = array_merge($selected_users_ids,$groups_member_id);
                            $array1 = array_unique($array1);
                            $strUserId = implode(",",$array1);
                        }
                        else{
                             $array1 = $user_userId;
                        }
                    }
                          
                    $form_model->muser=$array1;
                   
                    $broadcast_entity = new \app\entites\BroadcastEntity($form_model,$broadcast_model);
            
            if ($broadcast_entity->save()) {

            //    \Yii::$app->getSession()->setFlash('success', \Yii::t('Broadcast/create', 'Broadcast have been added successfully'));

                return $this->redirect(['broadcast/index']);
            }
          }

        if (\Yii::$app->request->isAjax) {
//            return $this->renderAjax('create', [
//                        'model' => $form_model,
//            ]);
        } else {
            return $this->render('create', [
                        'model' => $form_model,
            ]);
        }
//        return $this->render('create', [
//            'model' => $model,
//        ]);
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
    /**
     * Updates an existing Broadcast model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            
              $broadcast_model = null;
            $array1 = array();
            $broadcast_user=[];
            $muser = $form_model->muser;
            $user_userId = array();
            $user_userId = $muser[0]['user_id'];
            $group_groupId = array();
            $group_groupId = $muser[0]['group_id'];
            $form_model->broadcast_user_id ='';
            $form_model->broadcast_user_id = implode(',', $user_userId);
            $form_model->broadcast_group_id = '';
            $form_model->broadcast_group_id = implode(',', $group_groupId);
            
                    $array1 = array();
                    if(!empty($form_model->broadcast_group_id)) {
                             $selected_groups_ids = explode(",", $form_model->broadcast_group_id);
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
                      if(!empty($form_model->broadcast_user_id)){  
                          $brod_users = explode(",", $form_model->broadcast_user_id);
                          $array1 = array_merge($brod_users,$array1);
                          $array1 = array_unique($array1);
                          } else{$array1=[];}
                    $form_model->muser=$array1;
                  //  print_r($form_model->muser);die;
                    $broadcast_entity = new \app\entites\BroadcastEntity($form_model,$broadcast_model);
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Broadcast model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Broadcast model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Broadcast the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Broadcast::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
