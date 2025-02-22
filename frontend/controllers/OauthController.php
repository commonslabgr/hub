<?php
namespace frontend\controllers;

use Yii;
use yii\helpers\Url;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use frontend\models\AuthorisationForm;
use frontend\models\SignupForm;
use frontend\models\WenetApp;
use frontend\models\AppSocialLogin;
use frontend\models\AppUser;

class OauthController extends BaseController {

    /**
     * {@inheritdoc}
     */
    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => [
                    'login', 'signup', 'authorise', 'complete',
                    'create-oauth', 'update-oauth', 'delete-oauth'
                ],
                'rules' => [
                    [
                        'actions' => ['authorise', 'login', 'signup', 'complete'],
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                    [
                        'actions' => [
                            'create-oauth', 'update-oauth', 'delete-oauth',
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

    private function verifyAppExistance($clientId) {
        $app = WenetApp::findOne($clientId);
        if ($app == NULL) {
            # TODO error : provided client id is not valid
            # should render here error page
            exit();
        }
        return $app;
    }

    public function actionLogin($client_id, $scope=null, $external_id=null) {
        $this->verifyAppExistance($client_id);

        $this->layout = "easy.php";
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['oauth/authorise', 'client_id' => $client_id, 'scope' => $scope, 'external_id' => $external_id]);
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->redirect(['oauth/authorise', 'client_id' => $client_id, 'scope' => $scope, 'external_id' => $external_id]);
        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
                'client_id' => $client_id,
                'scope' => $scope
            ]);
        }
    }

    public function actionSignup($client_id, $scope=null, $external_id=null) {
        $this->verifyAppExistance($client_id);

        $this->layout = "easy.php";
        $model = new SignupForm();
        $model->scenario = SignupForm::SCENARIO_CREATE;
        if ($model->load(Yii::$app->request->post()) && $model->signup()) {
            Yii::$app->session->setFlash('success', Yii::t('signup', 'Thank you for registration. Please check your inbox for verification email.'));
            return $this->redirect(['login', 'client_id' => $client_id, 'scope' => $scope, 'external_id' => $external_id]);
        }

        return $this->render('signup', [
            'model' => $model,
            'client_id' => $client_id,
            'scope' => $scope
        ]);
    }

    private function saveAppUserAssociation($appId, $userId) {
        $association = AppUser::find()->where([
            'app_id' => $appId,
            'user_id' => $userId,
        ])->one();

        if (!$association) {
            $association = new AppUser();
            $association->app_id = $appId;
            $association->user_id = $userId;
            if (!$association->save()) {
                return false;
            }
        }
        return true;
    }

    public function actionAuthorise($client_id, $scope=null, $external_id=null) {
        $this->layout = "easy.php";
        $model = new AuthorisationForm();
        $model->appId = $client_id;
        $model->userId = Yii::$app->user->id;
        $app = $this->verifyAppExistance($client_id);

        if (isset($scope)) {
            $model->withSpecifiedScope(explode(',', $scope));
        } else {
            $socialLogin = $app->getSocialLogin();
            $model->withSpecifiedScope($socialLogin->allowedScopes);
        }

        if ($model->load(Yii::$app->request->post())) {
            if($model->allowedReadScope == "" || $model->allowedReadScope == null){
                $model->allowedReadScope = [];
            }
            if($model->allowedWriteScope == "" || $model->allowedWriteScope == null){
                $model->allowedWriteScope = [];
            }
            $allowedScope = array_merge(array_keys($model->publicScope()), $model->allowedReadScope, $model->allowedWriteScope);

            if (isset(Yii::$app->params['kong.ignore']) && Yii::$app->params['kong.ignore']) {
                $redirectUri = 'http://google.com?code=123';
            } else {
                $redirectUri = Yii::$app->kongConnector->createAuthenticatedUser($model->appId, $model->userId, implode(' ', $allowedScope));
            }
            if (isset($redirectUri)) {
                if ($external_id) {
                    $redirectUri = $redirectUri . '&external_id=' . $external_id;
                }
                $result = $this->saveAppUserAssociation($client_id, Yii::$app->user->id);
                $this->redirect($redirectUri);
            } else {
                # TODO show error page - something went wrong during authorisation
                print_r('error');
                exit();
            }
        }

        $appDeveloperIds = \array_map(function($d){
            return $d->user_id;
        }, $app->appDevelopers);

        return $this->render('authorise', [
            'model' => $model,
            'userCanProceed' => $app->status == WenetApp::STATUS_ACTIVE || ($app->status == WenetApp::STATUS_NOT_ACTIVE && \in_array(Yii::$app->user->id, $appDeveloperIds))
        ]);
    }

    public function actionComplete($app_id, $redirect_url=null, $error_message=null) {
        $this->layout = "easy.php";
        $app = WenetApp::find()->where(["id" => $app_id])->one();

        if(!isset($redirect_url)){
            $redirect_url = "";
        }

        return $this->render('complete', [
            'redirect_url' => $redirect_url,
            'error_message' => $error_message,
            'app' => $app
        ]);
    }

    public function actionCreateOauth($id, $skip=false){
        $app = WenetApp::find()->where(["id" => $id])->one();

        // TODO check if there are other active social_login!!! non dovrebbe succedere (almeno non da interfaccia ma non si sa mai!)

        $model = new AppSocialLogin;
        $model->app_id = $id;
        $model->status = AppSocialLogin::STATUS_ACTIVE;
        $model->scenario = AppSocialLogin::SCENARIO_CREATE;

        if ($model->load(Yii::$app->request->post())) {
            if (isset(Yii::$app->params['kong.ignore']) && Yii::$app->params['kong.ignore']) {
                $model->oauth_app_id = 'oauthId';
            } else {
                Yii::$app->kongConnector->createConsumer($app->id);
                $model->oauth_app_id = Yii::$app->kongConnector->createOAuthCredentials($app->id, $app->token, $model->callback_url);
            }
            if ($model->save()) {
                return $this->redirect(['/developer/details', 'id' => $id]);
            } else {
                // TODO Yii::error('Could not add social login', '');
                Yii::$app->session->setFlash('error', Yii::t('app', 'Could not add social login.'));
            }
        }

        return $this->render('create_oauth', array(
            'model' => $model,
            'app' => $app,
            'skipConfiguration' => $skip,
        ));
    }

    public function actionUpdateOauth($id){
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        $transactionOk = true;
        $dataConnectorDisable = false;

        $model = AppSocialLogin::find()->where(["id" => $id])->one();
        $app = WenetApp::find()->where(["id" => $model->app_id])->one();
        $model->scenario = AppSocialLogin::SCENARIO_UPDATE;

        if ($model->load(Yii::$app->request->post())) {
            if (isset(Yii::$app->params['kong.ignore']) && Yii::$app->params['kong.ignore']) {
                $model->oauth_app_id = 'oauthId';
            } else {
                Yii::$app->kongConnector->deleteOAuthCredentials($app->id, $model->oauth_app_id);
                $model->oauth_app_id = Yii::$app->kongConnector->createOAuthCredentials($app->id, $app->token, $model->callback_url);
            }

            if (!$model->save()) {
                $transactionOk = false;
                Yii::error('Could not update oauth', 'wenet.platform');
            } else {
                if(!$app->hasWritePermit() && $app->data_connector == WenetApp::ACTIVE_CONNECTOR){
                    $dataConnectorDisable = true;
                    $app->data_connector = WenetApp::NOT_ACTIVE_CONNECTOR;
                }

                if($dataConnectorDisable){
                    if(!$app->save()){
                        $transactionOk = false;
                        Yii::error('Could not disable data connector', 'wenet.platform');
                    }
                }
            }

            if ($transactionOk) {
                if($dataConnectorDisable){
                    Yii::$app->session->setFlash('warning', Yii::t('app', 'Data connector disabled'));
                }
                Yii::$app->session->setFlash('success', Yii::t('app', 'OAuth2 successfully updated.'));
                $transaction->commit();

                return $this->redirect(['/developer/details', "id" => $model->app_id]);
            } else {
                Yii::$app->session->setFlash('error', Yii::t('app', 'Error updating the OAuth2'));
                $transaction->rollback();
            }

        }
        return $this->render('create_oauth', [
            'model' => $model,
            'app' => $app,
            'skipConfiguration' => false,
        ]);
    }

    public function actionDeleteOauth($id) {
        $connection = \Yii::$app->db;
        $transaction = $connection->beginTransaction();
        $transactionOk = true;
        $appToDevMode = true;

        $model = AppSocialLogin::find()->where(["id" => $id])->one();
        $app = WenetApp::find()->where(["id" => $model->app_id])->one();
        $model->status = AppSocialLogin::STATUS_NOT_ACTIVE;

        if (!$model->save()) {
            $transactionOk = false;
            Yii::error('Could not delete oauth', 'wenet.platform');
        } else {
            if($appToDevMode){
                if($app->conversational_connector == WenetApp::ACTIVE_CONNECTOR){
                    $app->conversational_connector = WenetApp::NOT_ACTIVE_CONNECTOR;
                }
                if($app->data_connector == WenetApp::ACTIVE_CONNECTOR){
                    $app->data_connector = WenetApp::NOT_ACTIVE_CONNECTOR;
                }
                if(!$app->save()){
                    $transactionOk = false;
                    Yii::error('Could not put app ['.$app->id.'] in dev mode', 'wenet.platform');
                }
            }
        }

        if ($transactionOk) {
            if($appToDevMode){
                Yii::$app->session->setFlash('warning', Yii::t('app', 'Because OAuth2 is required for the app, the app has been automatically set as "In development" mode.'));
            }
            Yii::$app->session->setFlash('success', Yii::t('app', 'OAuth2 successfully deleted.'));
            $transaction->commit();

            // TODO include in transaction!
            if (isset(Yii::$app->params['kong.ignore']) && Yii::$app->params['kong.ignore']) {
                # nothing to do
            } else {
                Yii::$app->kongConnector->deleteConsumer($model->app_id);
            }

        } else {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Could not delete OAuth2.'));
            $transaction->rollback();
        }
        return $this->redirect(['/developer/details', 'id' => $model->app_id]);
    }


}
