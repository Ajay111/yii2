<?php

namespace app\modules\api\v1\models;

use Yii;

/**
 * This is the model class for table "wbs_user".
 *
 * @property int $id
 * @property int $wbs_id
 * @property int $user_id
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 * @property int $status
 */
class WbsUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wbs_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['wbs_id', 'user_id'], 'required'],
            [['wbs_id', 'user_id', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
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
            'wbs_id' => 'Wbs ID',
            'user_id' => 'User ID',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
            'status' => 'Status',
        ];
    }
}
