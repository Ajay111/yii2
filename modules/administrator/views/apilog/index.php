<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\Pjax;
use yii\grid\GridView;
/* @var $this yii\web\View */
/* @var $searchModel app\models\ApiLogSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Api Logs';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="api-log-index">

    <?php
    Pjax::begin([
        'id' => 'grid-data',
        'enablePushState' => FALSE,
        'enableReplaceState' => FALSE,
        'timeout' => false,
    ]);
    ?>
    <div class="filter_sec">
        <?php
        echo $this->render('_search', ['model' => $searchModel]);
        ?>
    </div>
    <p class="clearfix"></p> 
    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'layout' => "{items}\n{pager}",
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'app_id',
            'version_no',
            'imei_no',
            'ip',
             'time',
             'request_body:ntext',
             'request_url:url',
            // 'http_response_code',
            // 'api_response_status',
            // 'response:ntext',
            // 'created_at',
        ],
    ]); ?>
    <?php Pjax::end() ?>
</div>
