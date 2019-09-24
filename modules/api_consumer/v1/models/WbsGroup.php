<?php

namespace app\modules\api\v1\models;

use Yii;

/**
 * This is the model class for table "wbs_group".
 *
 * @property int $id
 * @property int $wbs_id
 * @property int $group_id
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 * @property int $status
 */
class WbsGroup extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wbs_group';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['wbs_id', 'group_id', 'created_at', 'created_by', 'updated_at', 'updated_by', 'status'], 'required'],
            [['wbs_id', 'group_id', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['status'], 'string', 'max' => 4],
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
            'group_id' => 'Group ID',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
            'status' => 'Status',
        ];
    }
}
