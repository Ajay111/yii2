<?php

namespace app\modules\api\v1\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "group_user".
 *
 * @property int $id
 * @property int $group_id
 * @property int $user_id
 * @property string $username
 * @property string $email
 * @property string $name
 * @property int $org_id
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 * @property int $status
 */
class GroupUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'group_user';
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
            [['group_id', 'user_id', 'org_id'], 'required'],
            [['group_id', 'user_id', 'org_id', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
             [['created_at', 'created_by', 'updated_at', 'updated_by', 'status'], 'safe'],
            [['username', 'email', 'name'], 'string', 'max' => 100],
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
            'group_id' => 'Group ID',
            'user_id' => 'User ID',
            'username' => 'Username',
            'email' => 'Email',
            'name' => 'Name',
            'org_id' => 'Org ID',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
            'status' => 'Status',
        ];
    }
}
