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
use app\models\GraphSearchModel;

/**
 * GraphController 
 */
class GraphController extends Controller {

    public $enableCsrfValidation = false;

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
                'only' => ['index','org'],
                'rules' => [

                    [
                        'allow' => true,
                        'actions' => ['index'],
                        'roles' => ['?','@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['org'],
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                    return (!Yii::$app->user->isGuest && \Yii::$app->user->identity->isOrgAdmin);
                }
                    ],
                ],
            ],
        ];
    }

    public function beforeAction($action) {
        if ($action->id == 'index') {
            if (isset($_REQUEST['user_id'])) {
                $this->layout = 'graph';
            } else {
                $this->layout = 'main';
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex($user_id = 0) {
        $searchModel = new GraphSearchModel();
        if ($user_id and is_numeric($user_id)) {
            $searchModel->user_id = $user_id;
        } else {
            $searchModel->user_id = isset(\Yii::$app->user->identity->id) ? \Yii::$app->user->identity->id : 0;
        }

        $searchModel->daterange = $searchModel->GetMinDate() . ' to ' . $searchModel->GetMaxDate();
        if (isset($_REQUEST['_date']))
            $searchModel->daterange = $_REQUEST['_date'];

        $searchModel->SetDateRange($searchModel->daterange);
        $dataProvider = $searchModel->search($searchModel);
        //print_r($dataProvider);die;
        return $this->render('index', [
                    'data' => $dataProvider,
                    'searchModel' => $searchModel,
                    'graph_type' => 'member',
        ]);
    }

    public function actionOrg($user_id = 0) {
        $searchModel = new GraphSearchModel();
        $searchModel->org_id = \Yii::$app->user->identity->org_id;
        $searchModel->daterange = $searchModel->GetMinDate() . ' to ' . $searchModel->GetMaxDate();
        if (isset($_REQUEST['_date']))
            $searchModel->daterange = $_REQUEST['_date'];

        $searchModel->SetDateRange($searchModel->daterange);
        if (!isset($_POST['GraphSearchModel']))
            $searchModel->graph_type = GraphSearchModel::GRAPH_TYPE_MEETING;
        if ($searchModel->load(\Yii::$app->request->post())) {
            
        }
        $dataProvider = $searchModel->search_dashboard($searchModel);
        
        return $this->render('graph', [
                    'data' => $dataProvider,
                    'json_data' => json_encode($dataProvider),
                    'searchModel' => $searchModel,
                        ]
        );
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
