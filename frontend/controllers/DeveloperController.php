<?php
namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ArrayDataProvider;
use frontend\models\WenetApp;
use frontend\models\AppDeveloper;
use yii\helpers\Json;

/**
 * Developer controller
 */
class DeveloperController extends Controller {

    /**
     * {@inheritdoc}
     */
    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => [
                    'index', 'create', 'update', 'details', 'delete',
                    'conversational-connector',
                    'disable-conversational-connector', 'enable-conversational-connector',
                    'disable-data-connector', 'enable-data-connector'
                ],
                'rules' => [
                    [
                        'actions' => [
                            'index', 'create', 'update', 'details', 'delete',
                            'conversational-connector',
                            'disable-conversational-connector', 'enable-conversational-connector',
                            'disable-data-connector', 'enable-data-connector'
                        ],
                        'allow' => !Yii::$app->user->isGuest && Yii::$app->user->getIdentity()->isDeveloper(),
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions() {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex(){
        $appDevelopers = AppDeveloper::find()->where(['user_id' => Yii::$app->user->id])->all();
        $userApps = [];
        foreach ($appDevelopers as $appDeveloper) {
            $app = $appDeveloper->app;
            if (\in_array($app->status, [WenetApp::STATUS_NOT_ACTIVE, WenetApp::STATUS_ACTIVE])) {
                $userApps[] = $app;
            }
        }

        $provider = new ArrayDataProvider([
            'allModels' => $userApps,
            'pagination' => [
                'pageSize' => 15,
            ],
            'sort' => [
                'attributes' => [],
            ],
        ]);

        return $this->render('index', array(
            'provider' => $provider
		));
    }

    public function actionDetails($id) {
		$app = WenetApp::find()->where(["id" => $id])->one();

        if(!$app || $app->status == WenetApp::STATUS_DELETED){
            throw new NotFoundHttpException('The specified app cannot be found.');
		} else {
			return $this->render('details', array(
                'app' => $app
            ));
		}

        return $this->render('details', array());
    }

    public function actionCreate(){
        $model = new WenetApp;
        $model->owner_id = Yii::$app->user->id;
        $model->conversational_connector = WenetApp::NOT_ACTIVE_CONNECTOR;
        $model->data_connector = WenetApp::NOT_ACTIVE_CONNECTOR;


        if ($model->load(Yii::$app->request->post())) {
            if ($model->create()) {
                $appDeveloper = new AppDeveloper;
                $appDeveloper->app_id = $model->id;
                $appDeveloper->user_id = $model->owner_id;
                $appDeveloper->save();

                return $this->redirect(['oauth/create-oauth', 'id' => $model->id, 'skip' => true]);
            } else {
                Yii::$app->session->setFlash('error', Yii::t('app', 'Could not create app.'));
            }
        }

        return $this->render('create', array(
            'model' => $model
        ));
    }

    public function actionUpdate($id) {
        $app = WenetApp::find()->where(["id" => $id])->one();
        if ($app->load(Yii::$app->request->post())) {
            if ($app->save()) {
                return $this->redirect(['details', "id" => $id]);
            }
        }
        return $this->render('update', ['app' => $app ]);
    }

    public function actionDelete($id) {
        $model = WenetApp::find()->where(["id" => $id])->one();
        $model->status = WenetApp::STATUS_DELETED;
        if ($model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'App successfully deleted.'));
        } else {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Could not delete app.'));
        }
        return $this->redirect(['index']);
    }

    public function actionConversationalConnector($id){
        $app = WenetApp::find()->where(["id" => $id])->one();
        $app->scenario = WenetApp::SCENARIO_CONVERSATIONAL;

        if ($app->load(Yii::$app->request->post())) {
            $app->conversational_connector = WenetApp::ACTIVE_CONNECTOR;

            if ($app->save()) {
                return $this->redirect(['/developer/details', "id" => $id]);
            }
        }
        return $this->render('conversational_connector', [
            'app' => $app
        ]);
    }

    public function actionDisableConversationalConnector($id) {
        $app = WenetApp::find()->where(["id" => $id])->one();
        $app->conversational_connector = WenetApp::NOT_ACTIVE_CONNECTOR;

        $message = Yii::t('app', 'Connector succesfully disabled.');
        $alert_type = 'success';
        if($app->data_connector == WenetApp::NOT_ACTIVE_CONNECTOR && $app->status == WenetApp::STATUS_ACTIVE){
            $app->status = WenetApp::STATUS_NOT_ACTIVE;
            $message = Yii::t('app', 'Because a connector is required for the app to be live, the app has been automatically set as "In development" mode.');
            $alert_type = 'warning';
        }

        if ($app->save()) {
            return JSON::encode([
                'message' => $message,
                'alert_type' => $alert_type
            ]);
        } else {
            return JSON::encode([
                'message' => Yii::t('app', 'Error, please retry later.'),
                'alert_type' => 'danger'
            ]);
        }
    }

    public function actionEnableConversationalConnector($id) {
        $app = WenetApp::find()->where(["id" => $id])->one();
        $app->conversational_connector = WenetApp::ACTIVE_CONNECTOR;

        if ($app->save()) {
            return JSON::encode([
                'message' => Yii::t('app', 'Connector succesfully enabled.'),
                'alert_type' => 'success'
            ]);
        } else {
            return JSON::encode([
                'message' => Yii::t('app', 'Error, please retry later.'),
                'alert_type' => 'danger'
            ]);
        }
    }

    public function actionDisableDataConnector($id) {
        $app = WenetApp::find()->where(["id" => $id])->one();
        $app->data_connector = WenetApp::NOT_ACTIVE_CONNECTOR;

        $message = Yii::t('app', 'Connector succesfully disabled.');
        $alert_type = 'success';
        if($app->conversational_connector == WenetApp::NOT_ACTIVE_CONNECTOR && $app->status == WenetApp::STATUS_ACTIVE){
            $app->status = WenetApp::STATUS_NOT_ACTIVE;
            $message = Yii::t('app', 'Because a connector is required for the app to be live, the app has been automatically set as "In development" mode.');
            $alert_type = 'warning';
        }

        if ($app->save()) {
            return JSON::encode([
                'message' => $message,
                'alert_type' => $alert_type
            ]);
        } else {
            return JSON::encode([
                'message' => Yii::t('app', 'Error, please retry later.'),
                'alert_type' => 'danger'
            ]);
        }
    }

    public function actionEnableDataConnector($id) {
        $app = WenetApp::find()->where(["id" => $id])->one();
        $app->data_connector = WenetApp::ACTIVE_CONNECTOR;

        if ($app->save()) {
            return JSON::encode([
                'message' => Yii::t('app', 'Connector succesfully enabled.'),
                'alert_type' => 'success'
            ]);
        } else {
            return JSON::encode([
                'message' => Yii::t('app', 'Error, please retry later.'),
                'alert_type' => 'danger'
            ]);
        }
    }

}
