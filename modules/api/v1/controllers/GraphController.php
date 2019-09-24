<?php

namespace app\modules\api\v1\controllers;

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
use app\models\GraphSearchModel;



use app\modules\user\Finder;
use app\modules\user\models\Account;
use app\models\LoginForm;
use yii\db\Expression;
use app\models\AppDetail;

/**
 * Member controller for the `api` module
 */
class GraphController extends Controller {

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
    
    public function actionIndex($user_id = 0) {
        $searchModel = new GraphSearchModel();
        if ($user_id and is_numeric($user_id)) {
            $searchModel->user_id = $user_id;
        } else {
            $searchModel->user_id = isset(\Yii::$app->user->identity->id) ? \Yii::$app->user->identity->id : 0;
        }
        $searchModel->daterange = $searchModel->GetMinDate() . ' to ' . $searchModel->GetMaxDate();
        if (isset($this->data_json['start_date']) && isset($this->data_json['end_date']) ) 
        $searchModel->daterange = $this->data_json['start_date'] . ' to ' . $this->data_json['end_date'];

        $searchModel->SetDateRange($searchModel->daterange);
        $dataProvider = $searchModel->search($searchModel);
        $this->response['graph_detail_all']=$dataProvider;
        return $this->response;
        
    }
     public function actionOrg($user_id = 0) {
        $searchModel = new GraphSearchModel();
         if (isset($this->data_json['user_id']))
        $searchModel->user_id =$this->data_json['user_id'];
        $searchModel->org_id = \Yii::$app->user->identity->org_id;
        $searchModel->daterange = $searchModel->GetMinDate() . ' to ' . $searchModel->GetMaxDate();
        if (isset($this->data_json['start_date']) && isset($this->data_json['end_date']) ) 
        $searchModel->daterange = $this->data_json['start_date'] . ' to ' . $this->data_json['end_date'];
        $searchModel->SetDateRange($searchModel->daterange);
        if (isset($this->data_json['graph_type']))
            $searchModel->graph_type =$this->data_json['graph_type'];
        
        // print_r($searchModel);die;
        $dataProvider = $searchModel->search_dashboard($searchModel);
        $this->response['graph_detail_all']=$dataProvider;
        return $this->response;
        
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
            throw new NotFoundHttpException('The requested page does not exist');
        }

        return $action_point;
    }

}
