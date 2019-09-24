<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\Pjax;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\AppDetailSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'App Details';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="app-detail-index">
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
            [
                'attribute' => 'User',
                'value' => function($model) {
                    return $model->user != NULL ? $model->user->name : '';
                }
            ],
            [
                'attribute' => 'Organozation',
                'value' => function($model) {
                    return $model->organization != NULL ? $model->organization->name : '';
                }
            ],         
            'imei_no',
//            'os_type',
             'manufacturer_name',
            // 'os_version',
            // 'firebase_token:ntext',
            // 'app_version',
             'date_of_install',
            // 'date_of_uninstall',
            [
                'attribute' => 'status',
                'format' => 'html',
                'filter' => false,
                'value' => function ($model) {
                    if ($model->status == 1) {
                        $text = 'Active';
                        $class = 'label-success';
                    } else {
                        $text = 'Inactive';
                        $class = 'label-warning';
                    }

                    return '<span class="label ' . $class . '">' . $text . '</span>';
                }
            ],
            ['class' => 'yii\grid\ActionColumn', 'template' => '{view}'],
        ],
    ]);
    ?>
    <?php Pjax::end() ?>  
</div>
