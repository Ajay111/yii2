<?php

namespace app\components\validators;

use yii\validators\Validator;

class PinCodeValidator extends Validator {

    public function validateAttribute($model, $attribute) {
        if ($model->$attribute != '') {
            if (!preg_match('/^[0-9]{6}+$/', $model->$attribute)) {
                $this->addError($model, $attribute, 'In valid pin code');
            }
        }
    }

}
