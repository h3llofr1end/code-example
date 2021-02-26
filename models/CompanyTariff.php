<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class for table company_tariffs
 * @property int $id
 * @property int $company_id
 * @property string $from
 * @property string $to
 * @property int $tariff_id
 * @property int $have_answers
 */
class CompanyTariff extends ActiveRecord
{
    public static function tableName()
    {
        return 'company_tariffs';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTariff()
    {
        return $this->hasOne(Tariff::className(), ['id' => 'tariff_id']);
    }

    /**
     * Update count answers on active tariff for history
     */
    public function updateHaveAnswers()
    {
        $this->have_answers = (int)FoquzPollAnswer::find()
        ->leftJoin('foquz_poll', 'foquz_poll.id = foquz_poll_answer.foquz_poll_id')
        ->where(['in', 'foquz_poll_answer.status', [FoquzPollAnswer::STATUS_DONE, FoquzPollAnswer::STATUS_IN_PROGRESS]])
        ->andWhere(['between', 'foquz_poll_answer.created_at', $this->from.' 00:00:00', $this->to.' 23:59:59'])
        ->andWhere(['foquz_poll.company_id' => $this->company_id])
        ->count();
        $this->save();
    }
}