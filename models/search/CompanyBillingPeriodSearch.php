<?php

namespace app\models\search;

use yii\db\Expression;
use yii\db\Query;

class CompanyBillingPeriodSearch
{
    public function search($params)
    {
        $page = $params['page'] ?? 1;

        $dataQuery = (new Query())
            ->select('tariffs.title, have_answers')
            ->addSelect(new Expression("CONCAT(DATE_FORMAT(company_tariffs.from,\"%d.%m.%Y\"), '–', DATE_FORMAT(company_tariffs.to, \"%d.%m.%Y\")) as period"))
            ->from('company_tariffs')
            ->leftJoin('tariffs', 'tariffs.id = company_tariffs.tariff_id')
            ->where(['company_id' => $params['company_id']]);

        if(isset($params['period']) && $params['period'] !== '') {
            $dataQuery->andWhere(['like', new Expression("CONCAT(DATE_FORMAT(company_tariffs.from,\"%d.%m.%Y\"), '–', DATE_FORMAT(company_tariffs.to, \"%d.%m.%Y\"))"), $params['period']]);
        }
        if(isset($params['tariff']) && $params['tariff'] !== '') {
            $dataQuery->andWhere(['like', 'tariffs.title', $params['tariff']]);
        }
        if(isset($params['have_answers']) && $params['have_answers'] !== '') {
            $dataQuery->andWhere(['like', 'have_answers', $params['have_answers']]);
        }

        if(isset($params['order'])) {
            if($params['order'][0] === '-') {
                $type = SORT_DESC;
                $order = substr($params['order'], 1);
            } else {
                $type = SORT_ASC;
                $order = $params['order'];
            }

            $orderArray = [
                'period' => 'company_tariffs.id',
                'tariff' => 'tariffs.title',
                'have_answers' => 'have_answers',
            ];
            if(in_array($order, array_keys($orderArray))) {
                $dataQuery->orderBy([
                    $orderArray[$order] => $type
                ]);
            } else {
                $dataQuery->orderBy('company_tariffs.id DESC');
            }
        } else {
            $dataQuery->orderBy('company_tariffs.id DESC');
        }

        $data = $dataQuery->limit(30)->offset(($page-1)*30)->all();

        return $data;
    }
}