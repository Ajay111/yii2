<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use app\models\Reminder;
use yii\filters\AccessControl;
use app\models\UserModel;
use app\models\Meeting;

/**
 * CompanyController implements the CRUD actions for Company model.
 */
class AjaxController extends Controller {

    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;

    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['getnotification', 'readnotification', 'getresponsible'],
                'rules' => [

                    [
                        'allow' => true,
                        'actions' => ['getnotification', 'readnotification', 'getresponsible'],
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionGetnotification() {
        $reminder_model = Reminder::find()->where(['reminder_user_id' => \Yii::$app->user->identity->id, 'is_read' => 0])->count();
        $response['Reminder'] = $reminder_model;

        echo json_encode($response);
    }

    public function actionReadnotification($notification_id) {
        $reminder_model = Reminder::findOne($notification_id);
        $reminder_model->is_read = 1;
        $reminder_model->update();
    }

    public function actionGetresponsible() {
        if (\Yii::$app->request->isAjax) {
            if (isset($_POST['depdrop_parents'])) {

                $parents = $_POST['depdrop_parents'];
                $user_ids = $parents[0];
                if (empty($user_ids))
                    $user_ids = [];
                $meeting_id = $_REQUEST['meeting_id'];
                $meeting_model = Meeting::findOne($meeting_id);
                $array = [];
                array_push($user_ids, $meeting_model->responsible_user_id);
                $model = UserModel::find()->where(['org_id' => \Yii::$app->user->identity->org_id])->andWhere((['id' => $user_ids]))->orderBy('name asc')->all();
                if ($model != NULL) {
                    foreach ($model as $member) {
                        $array[$member->id] = ['id' => $member->id, 'name' => $member->name];
                    }
                }
                echo Json::encode(['output' => $array, 'selected' => $meeting_model->responsible_user_id]);
                return;
            }
            echo Json::encode(['output' => '', 'selected' => '']);
        }
    }

}
