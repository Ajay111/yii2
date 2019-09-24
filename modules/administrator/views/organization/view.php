<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Organization */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Organizations', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="organization-view">


    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'name',
            'sendbird_app_id',
            'status',
        ],
    ]) ?>

</div>
