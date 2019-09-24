<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\AppDetail */

$this->title = 'App Detail';
$this->params['breadcrumbs'][] = ['label' => 'App Details', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="app-detail-view">

    <?=
    DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'attribute' => 'User',
                'value' => $model->user != NULL ? $model->user->name : '',
            ],
            [
                'attribute' => 'Organization',
                'value' => $model->organization != NULL ? $model->organization->name : '',
            ],
            'imei_no',
            'os_type',
            'manufacturer_name',
            'os_version',
            'firebase_token:ntext',
            'app_version',
            'date_of_install',
            'date_of_uninstall',
            [
                'attribute' => 'status',
                'format' => 'html',
                'value' => $model->status != 0 ? "<span class='label label-success'>Active</span>" : "<span class='label label-warning'>Inactive</span>",
            ],
        ],
    ])
    ?>

</div>
