<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Organization */

$this->title = 'Update Organization: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Organizations', 'url' => ['index']];
//$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="organization-update">

    <div class='box-content'> 
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>

</div>
