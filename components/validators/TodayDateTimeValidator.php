<?php

namespace app\components\validators;

use yii\validators\Validator;

class TodayDateTimeValidator extends Validator {

    public function validateAttribute($model, $attribute) {
        
        if (isset($model->$attribute) and $model->$attribute != '0000-00-00') {
            if (strtotime($model->$attribute) < strtotime(date('Y-m-d h:i A'))) {
                $this->addError($model, $attribute, 'Start Date should be greater than current date time.');
            }
        } else {
            $this->addError($model, $attribute, 'Invalid date.');
        }
    }

}
