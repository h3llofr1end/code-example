<?php

namespace app\models;

use app\models\company\Company;
use app\helpers\MailHelper;
use Aws\Ses\SesClient;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Class for table tariff_change_request
 *
 * @property int $id
 * @property int $company_id
 * @property int $tariff_id
 * @property string $name
 * @property string $phone
 * @property string $created_at
 *
 * @property Company $company
 * @property Tariff $tariff
 */
class TariffChangeRequest extends ActiveRecord
{
    public static function tableName()
    {
        return 'tariff_change_request';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => (new \DateTime('now'))->format('Y-m-d H:i:s'),
                'updatedAtAttribute' => false,
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        if($insert) {
            $charset = 'UTF-8';
            $sharedConfig = ['profile' => 'default', 'region' => 'eu-west-1', 'version' => 'latest'];
            $sender_email = MailHelper::mime_header_encode( "Опросы Foquz.ru", $charset, $charset). ' <'.\Yii::$app->params['main_sender_email'].'>';
            $sesClient = new SesClient($sharedConfig);

            $result = $sesClient->sendEmail([
                'Destination' => ['ToAddresses' => \Yii::$app->params['lead_to_emails']],
                'ReplyToAddresses' => [$sender_email],
                'Source' => $sender_email,
                'Message' => [
                    'Body' => [
                        'Html' => ['Charset' => $charset,'Data' => \Yii::$app->mailer->render('tariff-change-request', ['request' => $this])],
                    ],
                    'Subject' => ['Charset' => $charset, 'Data' => 'Компания "'.$this->company->name.'". Заявка на смену тарифа'],
                ],
            ]);
        }
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTariff()
    {
        return $this->hasOne(Tariff::className(), ['id' => 'tariff_id']);
    }
}