<?php

namespace app\controllers\api;

use yii\filters\Cors;
use yii\rest\Controller;

class ApiController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['corsFilter'] = [
            'class' => Cors::className(),
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Allow-Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
            ]
        ];
        return $behaviors;
    }

    public function init()
    {
        parent::init();
        \Yii::$app->user->enableSession = false;
    }

    protected function response($code, $data = [])
    {
        \Yii::$app->response->statusCode = $code;
        \Yii::$app->response->data = $data;
    }
}