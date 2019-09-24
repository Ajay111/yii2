<?php

namespace app\components\validators;

use yii\validators\Validator;

class NameValidator extends Validator {

    public function validateAttribute($model, $attribute) {
        if ($model->$attribute != '') {
            if (!preg_match('/^[\p{L}\p{N} .-]+$/', $model->$attribute)) {
                $this->addError($model, $attribute, 'Only letters and white space allowed');
            }
        }
    }

}
