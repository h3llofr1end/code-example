<?php

namespace app\models\search;

use app\models\company\Company;

class ListedCompaniesSearch extends Company
{
    public function search($params)
    {
        $page = $params['page'] ?? 1;

        $companiesQuery = Company::find()
            ->distinct()
            ->where(['affiliate_code_id' => $params['affiliate_code_id']])
            ->leftJoin('company_tariffs', 'company_tariffs.id = (
                SELECT id
                FROM company_tariffs ct
                WHERE ct.company_id = company.id AND `from` <= "'.date('Y-m-d').'" AND `to` >= "'.date('Y-m-d').'"
                ORDER BY id DESC
                LIMIT 1
              )')
            ->leftJoin('tariffs', 'company_tariffs.tariff_id = tariffs.id')
            ->where(['company.deleted' => 0]);

        if(isset($params['created_at']) && $params['created_at'] !== '') {
            $companiesQuery->andWhere(['like', 'DATE_FORMAT(company.created_at, "%d.%m.%Y")', $params['created_at']]);
        }
        if(isset($params['name']) && $params['name'] !== '') {
            $companiesQuery->andWhere(['like', 'name', $params['name']]);
        }
        if(isset($params['tariff']) && $params['tariff'] !== '') {
            $companiesQuery->andWhere(['like', 'tariffs.title', $params['tariff']]);
        }

        if(isset($params['affiliate_code_id'])) {
            $companiesQuery->andWhere(['affiliate_code_id' => $params['affiliate_code_id']]);
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
                'created_at' => 'company.created_at',
                'name' => 'company.name',
                'tariff' => 'tariff.title',
            ];
            if(in_array($order, array_keys($orderArray))) {
                $companiesQuery->orderBy([
                    $orderArray[$order] => $type
                ]);
            } else {
                $companiesQuery->orderBy('company.created_at DESC');
            }
        } else {
            $companiesQuery->orderBy('company.created_at DESC');
        }

        $companies = $companiesQuery->limit(30)->offset(($page-1)*30)->all();

        return $companies;
    }
}