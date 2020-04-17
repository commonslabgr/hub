<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "app_platform_telegram".
 *
 * @property int $id
 * @property string $app_id
 * @property string $bot_username
 * @property int $created_at
 * @property int $updated_at
 *
 * @property App $app
 */
class AppPlatformTelegram extends \yii\db\ActiveRecord {
    /**
     * {@inheritdoc}
     */
    public static function tableName() {
        return 'app_platform_telegram';
    }

    /**
     * {@inheritdoc}
     */
    public function rules() {
        return [
            [['created_at', 'updated_at'], 'required'],
            [['created_at', 'updated_at'], 'integer'],
            [['app_id', 'bot_username'], 'string', 'max' => 128],
            [['app_id'], 'exist', 'skipOnError' => true, 'targetClass' => App::className(), 'targetAttribute' => ['app_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels() {
        return [
            'bot_username' => Yii::t('app', 'Bot Username'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Gets query for [[App]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getApp() {
        return $this->hasOne(WenetApp::className(), ['id' => 'app_id']);
    }
}
