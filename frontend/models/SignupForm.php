<?php
namespace frontend\models;

use Yii;
use yii\base\Model;
use common\models\User;

/**
 * Signup form
 */
class SignupForm extends Model {

    public $username;
    public $email;
    public $password;
    public $password_repeat;

    /**
     * {@inheritdoc}
     */
    public function rules() {
        return [
            ['username', 'trim'],
            ['username', 'required'],
            ['username', 'unique', 'targetClass' => '\common\models\User', 'message' => 'This username has already been taken.'],
            ['username', 'string', 'min' => 2, 'max' => 255],

            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => '\common\models\User', 'message' => 'This email address has already been taken.'],

            [['password', 'password_repeat'], 'required'],
            ['password', 'string', 'min' => 6],
            [['password', 'password_repeat'], 'checkPassword'],
        ];
    }

    public function attributeLabels() {
        return [
            'username' => Yii::t('common', 'Username'),
            'email' => Yii::t('common', 'Email'),
            'password' => Yii::t('common', 'Password'),
            'password_repeat' => Yii::t('signup', 'Repeat password'),

        ];
    }

    public function checkPassword($attribute, $params) {
        if($this->password == $this->password_repeat){
            return true;
        } else {
            $this->addError($attribute, Yii::t('signup', 'Password does not match.'));
            $this->addError('password_repeat', Yii::t('signup', 'Password does not match.'));
        }
    }

    /**
     * Signs user up.
     *
     * @return bool whether the creating new account was successful and email was sent
     */
    public function signup() {
        if (!$this->validate()) {
            return null;
        }

        $user = new User();
        $user->username = $this->username;
        $user->email = $this->email;
        $user->status = User::STATUS_ACTIVE; // TODO
        $user->developer = User::NOT_DEVELOPER;
        $user->setPassword($this->password);
        $user->generateAuthKey();
        $user->generateEmailVerificationToken();

        if ($user->save()) {
            Yii::$app->serviceApi->initUserProfile($user->id);
            // $this->sendEmail($user);
            return $user;
        } else {
            return null;
        }
    }

    public function changePassword() {
        $user = User::find()->where(['id' => Yii::$app->user->id])->one();
        $user->username = Yii::$app->user->identity->username;
        $user->email = Yii::$app->user->identity->email;
        $user->setPassword($this->password);

        // validate?

        // print_r($user);
        // exit();


        if ($user->save()) {
            return $user;
        } else {
            return null;
        }
    }

    /**
     * Sends confirmation email to user
     * @param User $user user model to with email should be send
     * @return bool whether the email was sent
     */
    protected function sendEmail($user) {
        return Yii::$app
            ->mailer
            ->compose(
                ['html' => 'emailVerify-html', 'text' => 'emailVerify-text'],
                ['user' => $user]
            )
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
            ->setTo($this->email)
            ->setSubject('Account registration at ' . Yii::$app->name)
            ->send();
    }
}
