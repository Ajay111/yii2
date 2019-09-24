<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use \yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $model app\models\OrganizationSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="organization-search">

     <?php
        $form = ActiveForm::begin([
                    'options' => [
                        'class' => 'form-inline',
                        'data-pjax' => true,
                    ],
                    'method' => 'get',
        ]);
        ?>
    <?= $form->field($model, 'name')->label('')->textInput(['placeholder' => 'Enter Name']) ?>
    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
