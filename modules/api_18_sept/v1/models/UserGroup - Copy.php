<?php

namespace app\modules\api\v1\models;

use Yii;

/**
 * This is the model class for table "user_group".
 *
 * @property int $id
 * @property string $users
 * @property string $group_name
 * @property int $org_id
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 * @property int $status
 */
class UserGroup extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
      const SCENARIO_CREATE = 'create';
    public static function tableName()
    {
        return 'user_group';
    }
    public function scenarios()
 {
        $scenarios = parent::scenarios();
        $scenarios['create'] = ['users','group_name','org_id']; 
        return $scenarios; 
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['users', 'group_name', 'org_id', 'created_at', 'created_by', 'updated_at', 'updated_by', 'status'], 'required'],
            [['org_id', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['users'], 'string', 'max' => 100],
            [['group_name'], 'string', 'max' => 255],
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
            'users' => 'Users',
            'group_name' => 'Group Name',
            'org_id' => 'Org ID',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
            'status' => 'Status',
        ];
    }
}
