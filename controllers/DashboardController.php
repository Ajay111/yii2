<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\models\GraphSearchModel;
use app\models\ActionPointSearch;
use app\models\UserModel;

/**
 * DashboardController .
 */
class DashboardController extends Controller {

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
                'only' => ['index',''],
                'rules' => [

                    [
                        'allow' => true,
                        'actions' => ['index'],
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['actionPoints'],
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                    return (!Yii::$app->user->isGuest && \Yii::$app->user->identity->isOrgAdmin);
                }
                    ],
                ],
            ],
        ];
    }

    public function actionIndex() {

            return $this->render('index');
        
    }

    public function actionActionPoints($id) {
        $model = $this->findModel($id);
        $searchModelBy = new ActionPointSearch();
        
        $searchModelBy->created_by = $id;
        //$searchModelBy->action_assigned_by = $id;
        if (isset($_REQUEST['_date'])) {
            $searchModelBy->daterange = $_REQUEST['_date'];
            $searchModelBy->SetDateRange($searchModel->daterange);
        }
        $BydataProvider = $searchModelBy->search(Yii::$app->request->queryParams);
        $searchModelTo = new ActionPointSearch();
        $searchModelTo->action_assigned_to = $id;
        if (isset($_REQUEST['_date'])) {
            $searchModelTo->daterange = $_REQUEST['_date'];
            $searchModelTo->SetDateRange($searchModel->daterange);
        }
      //  print_r($searchModelTo->SetDateRange);die;
        $TodataProvider = $searchModelTo->search(Yii::$app->request->queryParams);

        return $this->render('summary', [
                    'model' => $model,
                    'BydataProvider' => $BydataProvider,
                    'searchModelBy' => $searchModelBy,
                    'TodataProvider' => $TodataProvider,
                    'searchModelTo' => $searchModelTo,
        ]);
    }

    protected function findModel($id) {
        $user = UserModel::findOne(['id' => $id, 'org_id' => \Yii::$app->user->identity->org_id]);
        if ($user === null) {
            throw new NotFoundHttpException('The requested page does not exist');
        }

        return $user;
    }

}
