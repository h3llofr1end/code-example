<?php

namespace app\controllers\api;

use app\components\AmoCrmComponent;
use app\models\company\Company;
use app\models\CompanyTariff;
use app\models\FoquzContact;
use app\models\Tariff;
use app\models\TariffChangeRequest;
use yii\filters\auth\QueryParamAuth;
use yii\filters\VerbFilter;

class TariffsController extends ApiController
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
                'index' => ['GET'],
                'change' => ['POST'],
                'refresh-count' => ['GET'],
                'send-request' => ['POST']
            ]
        ];
        return $behaviors;
    }

    public function actionIndex()
    {
        return $this->response(200, ['items' => Tariff::find()->all()]);
    }

    public function actionChange($id, $tariffId)
    {
        $company = Company::findOne($id);
        if(!\Yii::$app->user->identity->superadmin) {
            return $this->response(403, []);
        }
        if(!($tariff = Tariff::findOne($tariffId))) {
            return $this->response(400, ['errors' => ['tariffId' => 'Тариф не найден']]);
        }

        $currentTariff = $company->getCurrentTariff();
        $currentTariff->to = date('Y-m-d');
        $currentTariff->save();

        $newTariffRecord = new CompanyTariff([
            'company_id' => $company->id,
            'tariff_id' => $tariffId,
            'from' => date('Y-m-d'),
            'to' => date('Y-m-d', strtotime('+30 days'))
        ]);

        if($newTariffRecord->save()) {
            $company->refresh();
            return $this->response(200, ['company' => $company]);
        }

        return $this->response(400, ['errors' => $newTariffRecord->errors]);
    }

    public function actionRefreshCount($company_id)
    {
        $company = Company::findOne($company_id);
        if($company) {
            return $this->response(200, [
                'count' => $company->answersLimit - $company->answersTariffPeriodCount
            ]);
        }
        return $this->response(400, [
            'errors' => [
                'company_id' => 'Не удалось получить данные компании'
            ]
        ]);
    }

    public function actionRefreshMailsCount($company_id)
    {
        $company = Company::findOne($company_id);
        if($company) {
            return $this->response(200, [
                'count' => $company->mailsLimit - $company->mailsTariffPeriodCount
            ]);
        }
        return $this->response(400, [
            'errors' => [
                'company_id' => 'Не удалось получить данные компании'
            ]
        ]);
    }

    public function actionSendRequest($company_id)
    {
        $post = \Yii::$app->request->post();
        $request = new TariffChangeRequest([
            'company_id' => $company_id,
            'tariff_id' => $post['tariff_id'],
            'name' => $post['name'],
            'phone' => $post['phone']
        ]);
        if($request->save()) {
            $request->refresh();
            $amo = new AmoCrmComponent();
            $contact = $amo->findContact(FoquzContact::preformatPhone($request->phone));
            if(!$contact) {
                $contact = \Yii::$app->user->identity->email != '' ? $amo->findContact(\Yii::$app->user->identity->email) : null;
                if(!$contact) {
                    $companyId = $amo->createCompany($request->company->name, \Yii::$app->params['protocol'].'://'.$request->company->alias);
                    $contactId = $amo->createContact($request->name, \Yii::$app->user->identity->email, FoquzContact::preformatPhone($request->phone), $companyId);
                } else {
                    $contactId = $contact['id'];
                    $amo->updateContact($contact['id'], \Yii::$app->user->identity->email, FoquzContact::preformatPhone($request->phone));
                }
            } else {
                $contactId = $contact['id'];
                $amo->updateContact($contact['id'], \Yii::$app->user->identity->email, FoquzContact::preformatPhone($request->phone));
            }
            $amo->createTask('Сменить тариф компании на "'.$request->tariff->title.'"', time()+(24*60*60), $contactId, 'contacts');

            return $this->response(201, []);
        }
        return $this->response(400, ['errors' => $request->errors]);
    }

    public function actionChangeLimits($company_id)
    {
        $company = Company::findOne($company_id);
        $company->limit_answers = \Yii::$app->request->post('limit_answers') ?? $company->limit_answers;
        $company->limit_mails = \Yii::$app->request->post('limit_mails') ?? $company->limit_mails;
        $company->unlimited = \Yii::$app->request->post('unlimited') ?? $company->unlimited;
        $company->unlimited_mails = \Yii::$app->request->post('unlimited_mails') ?? $company->unlimited_mails;
        $company->save();
        $company->refresh();
        return $this->response(200, ['company' => $company]);
    }
}