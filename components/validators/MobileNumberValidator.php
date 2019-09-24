<?php

namespace app\components\validators;

use yii\validators\Validator;

class MobileNumberValidator extends Validator {

    public function validateAttribute($model, $attribute) {
        if ($model->$attribute != '') {
            if (!preg_match('/^[123456789]\d{9}$/', $model->$attribute)) {
                $this->addError($model, $attribute, 'in valid number');
            }
        }
    }

}
