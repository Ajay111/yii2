<?php

namespace app\components\validators;

use yii\validators\Validator;

class TodayDateValidator extends Validator {

    public function validateAttribute($model, $attribute) {
        
        if (isset($model->$attribute) and $model->$attribute != '0000-00-00') {
            if($model->status==1){
                if (strtotime($model->$attribute) < strtotime(date('Y-m-d'))) {
                    $this->addError($model, $attribute, 'Start Date should be greater than today\'s date.');
                }
            }
        } else {
            $this->addError($model, $attribute, 'Invalid date.');
        }
    }

}
