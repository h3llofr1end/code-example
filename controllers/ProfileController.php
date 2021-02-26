<?php

namespace app\controllers;

use yii\filters\AccessControl;
use yii\web\Controller;

class ProfileController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'matchCallback' => function () {
                            return isset(\Yii::$app->user->identity) && !\Yii::$app->user->identity->superadmin;
                        }
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $view = \Yii::$app->user->identity->isExecutor() ? 'executors' : 'index';
        $this->layout = \Yii::$app->user->identity->isExecutor() ? 'simple' : 'foquz';
        return $this->render($view, [
            'userDevices' => \Yii::$app->user->identity->notificationDevices,
            'notifications' => \Yii::$app->user->identity->notifications
        ]);
    }
}