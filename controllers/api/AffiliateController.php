<?php

namespace app\controllers\api;

use app\models\company\Company;
use app\models\search\CompanyBillingPeriodSearch;
use app\models\search\ListedCompaniesSearch;
use yii\filters\auth\QueryParamAuth;
use yii\filters\VerbFilter;

class AffiliateController extends ApiController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => QueryParamAuth::className()
        ];
        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [
                'companies' => ['GET'],
                'company' => ['GET'],
            ]
        ];
        return $behaviors;
    }

    public function actionCompanies($id = null)
    {
        $params = \Yii::$app->request->get();

        if(\Yii::$app->user->identity->superadmin) {
            $company = Company::findOne($id);
            $affiliateCodeData = $company->affiliateCode;
        } else {
            $affiliateCodeData = \Yii::$app->user->identity->company->affiliateCode;
        }

        $params['affiliate_code_id'] = $affiliateCodeData->id;

        $companiesSearch = new ListedCompaniesSearch();

        return $this->response(200, ['items' => $companiesSearch->search($params)]);
    }

    public function actionCompany($id)
    {
        $company = Company::findOne($id);
        $params = \Yii::$app->request->get();
        $params['company_id'] = $company->id;

        if(!\Yii::$app->user->identity->superadmin) {
            $affiliateCodeData = \Yii::$app->user->identity->company->affiliateCode;

            if($company->affiliate_code_id !== $affiliateCodeData->id) {
                return $this->response(400, ['errors' => ['id' => 'Данные компании по расчетному периоду недоступны']]);
            }
        }

        $dataSearch = new CompanyBillingPeriodSearch();

        return $this->response(200, [
            'title' => $company->name,
            'items' => $dataSearch->search($params)
        ]);
    }
}