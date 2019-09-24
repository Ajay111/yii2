<?php

namespace app\modules\api\v1\models;

use Yii;

/**
 * This is the model class for table "wbs".
 *
 * @property int $id
 * @property int $org_id
 * @property int $owner_id
 * @property string $wbs_title
 * @property string $start_date
 * @property string $end_date
 * @property string $wbs_group_id
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 * @property int $status
 */
class Wbs extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
const SCENARIO_CREATE = 'create';
    public static function tableName()
    {
        return 'wbs';
    }

    /**
     * @inheritdoc
     */
     public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['create'] = ['users', 'group_name', 'org_id']; 
        return $scenarios; 
    }
    public function rules()
    {
        return [
            [['org_id', 'owner_id', 'wbs_title', 'start_date', 'end_date', 'wbs_group_id', 'created_at', 'created_by', 'updated_at', 'updated_by', 'status'], 'required'],
            [['org_id', 'owner_id', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['start_date', 'end_date'], 'safe'],
            [['wbs_title'], 'string', 'max' => 255],
            [['wbs_group_id'], 'string', 'max' => 100],
            [['status'], 'string', 'max' => 1],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'org_id' => 'Org ID',
            'owner_id' => 'Owner ID',
            'wbs_title' => 'Wbs Title',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'wbs_group_id' => 'Wbs Group ID',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
            'status' => 'Status',
        ];
    }
}
