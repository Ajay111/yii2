<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\AppDetailSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="app-detail-search">

    <?php
    $form = ActiveForm::begin([
                'options' => [
                    'class' => 'form-inline',
                    'data-pjax' => true,
                ],
                'method' => 'get',
    ]);
    ?>
    <?= $form->field($model, 'user_id')->label('')->dropDownList(\yii\helpers\ArrayHelper::map(\app\models\UserModel::find()->select(['id','name'])->orderBy('name asc')->distinct()->all(), 'id', 'name'), ['prompt' => 'Select User']) ?>
    <?= $form->field($model, 'org_id')->label('')->dropDownList(\yii\helpers\ArrayHelper::map(\app\models\Organization::find()->select(['id','name'])->orderBy('name asc')->distinct()->all(), 'id', 'name'), ['prompt' => 'Select Organization']) ?>
    <?= $form->field($model, 'imei_no')->label('')->dropDownList(\yii\helpers\ArrayHelper::map(\app\models\AppDetail::find()->select('imei_no')->orderBy('imei_no asc')->distinct()->all(), 'imei_no', 'imei_no'), ['prompt' => 'Select IMEI NO']) ?>
    <?= $form->field($model, 'status')->dropDownList(['1' => 'Active', '0' => 'Inactive'], ['prompt' => 'Select']) ?>
   
    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>

    </div>

    <?php ActiveForm::end(); ?>

</div>
