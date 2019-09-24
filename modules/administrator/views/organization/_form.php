<?php

use yii\helpers\Html;
use kartik\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Organization */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="organization-form">

    <?php
    $form = ActiveForm::begin([
                'id' => 'organization-form',
                'options' => ['class' => 'form-horizontal'],
                'fieldConfig' => [
                    'template' => "{label}\n<div class=\"col-lg-4\">{input}</div>\n<div >{error}</div>",
                    'labelOptions' => ['class' => 'col-md-3 control-label'],
                ],
    ]);
    ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'sendbird_app_id')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'status')->dropDownList(['1' => 'Active', '0' => 'Inactive'], ['prompt' => 'Select']) ?>

    <div class="form-group">
        <div class="col-sm-offset-4 ">
            <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btnbtn-success' : 'btn btn-success']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
