<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\components\behaviors;

use Yii;
use yii\db\BaseActiveRecord;
use yii\behaviors\AttributeBehavior;

class OrganizationIdBehavior extends AttributeBehavior {
    
    /*
     * Column Name
     */
    public $org_id = 'org_id';
    
    public $value;

    public function init() {
        parent::init();
        $this->attributes = [BaseActiveRecord::EVENT_BEFORE_INSERT => $this->org_id];
    }

    protected function getValue($event) {
        if ($this->value === null) {
            return \Yii::$app->user->identity->org_id;
        }
        return parent::getValue($event);
    }
}