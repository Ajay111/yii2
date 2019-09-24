<?php

namespace app\components\validators;

use yii\validators\Validator;
use yii\helpers\ArrayHelper;

class CheckMeetingMemberValidator extends Validator {

    public function validateAttribute($model, $attribute) {

        if (isset($model->meeting) and $model->meeting != NULL) {
            if ($model->responsible_user_id == "" or $model->responsible_user_id == 0 or $model->responsible_user_id == \Yii::$app->user->identity->id or in_array($model->responsible_user_id, $model->muser)) {
                
            } else
                $this->addError($model, $attribute, 'Invalid responsible user.');
        } else {

            $this->addError($model, $attribute, 'Invalid responsible user.');
        }
    }

}
