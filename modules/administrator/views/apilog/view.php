<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\ApiLog */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Api Logs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="api-log-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'app_id',
            'version_no',
            'imei_no',
            'ip',
            'time',
            'request_body:ntext',
            'request_url:url',
            'http_response_code',
            'api_response_status',
            'response:ntext',
            'created_at',
        ],
    ]) ?>

</div>
