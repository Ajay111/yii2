<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Organization */

$this->title = 'Create Organization';
$this->params['breadcrumbs'][] = ['label' => 'Organizations', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="organization-create">
<div class='box-content'> 
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
</div>
