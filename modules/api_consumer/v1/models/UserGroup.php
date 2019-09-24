<?php

namespace app\modules\api\v1\models;

use Yii;

use yii\db\Expression;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;

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

    public function behaviors() {
        return [
            [
                'class' => BlameableBehavior::className(),
                'value' => \Yii::$app->request->isConsoleRequest ? 0 : NULL,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
        ];
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['users', 'group_name', 'org_id', 'created_at', 'created_by', 'updated_at', 'updated_by', 'status'], 'required'],
            [['org_id', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
             [['created_at', 'created_by', 'updated_at', 'updated_by'], 'safe'],
            [['users'], 'string', 'max' => 100],
            [['status'], 'default', 'value' => '1'],
            [['group_name'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 1],
        ];
    }
public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['create'] = ['users', 'group_name', 'org_id']; 
        return $scenarios; 
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
