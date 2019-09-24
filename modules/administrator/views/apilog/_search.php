<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ApiLogSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="api-log-search">
<?php
        $form = ActiveForm::begin([
                    'options' => [
                        'class' => 'form-inline',
                        'data-pjax' => true,
                    ],
                    'method' => 'get',
        ]);
        ?>
    <?= $form->field($model, 'app_id')->label('')->dropDownList(\yii\helpers\ArrayHelper::map(\app\models\ApiLog::find()->select('app_id')->orderBy('app_id asc')->distinct()->all(), 'app_id', 'app_id'), ['prompt' => 'Select App']) ?>
    <?= $form->field($model, 'version_no')->label('')->dropDownList(\yii\helpers\ArrayHelper::map(\app\models\ApiLog::find()->select('version_no')->orderBy('version_no asc')->distinct()->all(), 'version_no', 'version_no'), ['prompt' => 'Select Version']) ?>
    <?= $form->field($model, 'imei_no')->label('')->dropDownList(\yii\helpers\ArrayHelper::map(\app\models\ApiLog::find()->select('imei_no')->orderBy('imei_no asc')->distinct()->all(), 'imei_no', 'imei_no'), ['prompt' => 'Select IMEI NO']) ?>
   <?= $form->field($model, 'ip')->label('')->dropDownList(\yii\helpers\ArrayHelper::map(\app\models\ApiLog::find()->select('ip')->orderBy('ip asc')->distinct()->all(), 'ip', 'ip'), ['prompt' => 'Select IP']) ?>


    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
