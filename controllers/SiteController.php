<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\db\Expression;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\form\AlgnSupportForm;
//use app\models\LoginForm;

class SiteController extends Controller {

  //  public $enableCsrfValidation = false;

    public function init() {
        parent::init();
        $this->layout = 'static';
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post', 'get'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
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
    public function beforeAction($action)
    {
        if ($action->id == 'error'){
            if (!Yii::$app->user->isGuest) {
              $this->layout = 'main';
            }
        }

        return parent::beforeAction($action);
    }
    /**
     * Displays homepage.
     *
     * @return string
     */
    public $data_json;
    public $php_input;
    public $post_json;
    public function actionLogin1() {
        $returnData= \app\models\UserModel::find()->all();
        $this->php_input = file_get_contents("php://input");
        $this->post_json = json_decode(base64_decode($this->php_input), true);
        $this->post_json = json_decode($this->php_input, true);
        $this->data_json = $this->post_json['data'];
        $response = Yii::$app->response;
        $response->format = \yii\web\Response::FORMAT_JSON;
        $model = \app\modules\api\v1\models\User::find()
       ->where(['or',
           ['email'=>$this->data_json['username']],
           ['username'=>$this->data_json['username']]
       ])
       ->all();
      //  print_r($model);die;
//        $password="password";
//        $hash = Yii::$app->getSecurity()->generatePasswordHash($this->data_json['password']);
//        print_r( Yii::$app->getSecurity()->validatePassword($password, $hash));die;
        $response->data['status']='success';
        $response->data['data'] = $model;

    return $response;
      }
      
       public function actionAdduser() {
       
    }

    
    public function actionIndex() {
		if (Yii::$app->user->isGuest) {
            
        } else {
            return $this->redirect(['/dashboard']);
        }
		
        $model = \Yii::createObject(LoginForm::className());
        $this->performAjaxValidation($model);
		if ($model->load(\Yii::$app->request->post()) && $model->login()) {
            return $this->redirect(['/dashboard']); // return $this->goBack();
        }
		return $this->render('index', ['model' => $model]);
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
//    public function actionIndex1() {
//        
//        $smtpmail = \Yii::$app->postmark->compose('@app/mail/email_templates/action_assign',['data'=>'vikas k c. how r u'])
//                ->setFrom('hello@algn.me')
//                ->setTo('vikas@arthify.com')
//                ->setSubject('test mail');
//                //->setHtmlBody('test message123');
//         $smtpmail->send();
//       // echo "<pre/>";
//       // print_r($smtpmail); exit;
//        
//       // return $this->render('index1');
//    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin() {
		
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->render('login', [
                    'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout() {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact() {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
                    'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout() {
        return $this->render('about');
    }

    public function actionCopyright() {
        return $this->render('copyright');
    }

    public function actionPrivacypolicy() {
        return $this->render('privacypolicy');
    }

    public function actionMarketing() {
        return $this->render('marketing');
    }

    public function actionSupport() {
        $model = new AlgnSupportForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['supportEmail'])) {
            Yii::$app->session->setFlash('supportFormSubmitted');

            return $this->refresh();
        }
        return $this->render('support', [
                    'model' => $model,
        ]);
        return $this->render('support');
    }

    public function actionForgotpassword() {
        return $this->render('forgotpassword');
    }

    /**
     * Performs AJAX validation.
     * @param array|Model $models
     * @throws \yii\base\ExitException
     */
    protected function performAjaxValidation($models) {
//        if (\Yii::$app->request->isAjax) {
//            if (is_array($models)) {
//                $result = [];
//                foreach ($models as $model) {
//                    if ($model->load(\Yii::$app->request->post())) {
//                        \Yii::$app->response->format = Response::FORMAT_JSON;
//                        $result = array_merge($result, ActiveForm::validate($model));
//                    }
//                }
//                echo json_encode($result);
//                \Yii::$app->end();
//            } else {
//                if ($models->load(\Yii::$app->request->post())) {
//                    \Yii::$app->response->format = Response::FORMAT_JSON;
//                    echo json_encode(ActiveForm::validate($models));
//                    \Yii::$app->end();
//                }
//            }
//        }
    }

}
