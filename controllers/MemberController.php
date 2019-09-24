<?php

namespace app\controllers;

use dektrium\user\filters\AccessRule;
use dektrium\user\Finder;
use dektrium\user\models\Profile;
use app\models\UserModel;
use dektrium\user\helpers\Password;
use dektrium\user\Module;
use dektrium\user\traits\EventTrait;
use yii;
use yii\base\ExitException;
use yii\base\Model;
use yii\base\Module as Module2;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;
use app\models\UserSearch;

/**
 * MemberController allows 
 *
 * @property Module $module
 *
 * @author Habibur Rahman <rahman.kld@gmail.com
 */
class MemberController extends Controller {

    use EventTrait;

    /**
     * Event is triggered before creating new user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_BEFORE_CREATE = 'beforeCreate';

    /**
     * Event is triggered after creating new user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_AFTER_CREATE = 'afterCreate';

    /**
     * Event is triggered before updating existing user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_BEFORE_UPDATE = 'beforeUpdate';

    /**
     * Event is triggered after updating existing user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_AFTER_UPDATE = 'afterUpdate';

    /**
     * Event is triggered before impersonating as another user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_BEFORE_IMPERSONATE = 'beforeImpersonate';

    /**
     * Event is triggered after impersonating as another user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_AFTER_IMPERSONATE = 'afterImpersonate';

    /**
     * Event is triggered before updating existing user's profile.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_BEFORE_PROFILE_UPDATE = 'beforeProfileUpdate';

    /**
     * Event is triggered after updating existing user's profile.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_AFTER_PROFILE_UPDATE = 'afterProfileUpdate';

    /**
     * Event is triggered before confirming existing user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_BEFORE_CONFIRM = 'beforeConfirm';

    /**
     * Event is triggered after confirming existing user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_AFTER_CONFIRM = 'afterConfirm';

    /**
     * Event is triggered before deleting existing user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_BEFORE_DELETE = 'beforeDelete';

    /**
     * Event is triggered after deleting existing user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_AFTER_DELETE = 'afterDelete';

    /**
     * Event is triggered before blocking existing user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_BEFORE_BLOCK = 'beforeBlock';

    /**
     * Event is triggered after blocking existing user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_AFTER_BLOCK = 'afterBlock';

    /**
     * Event is triggered before unblocking existing user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_BEFORE_UNBLOCK = 'beforeUnblock';

    /**
     * Event is triggered after unblocking existing user.
     * Triggered with \dektrium\user\events\UserEvent.
     */
    const EVENT_AFTER_UNBLOCK = 'afterUnblock';

    /**
     * Name of the session key in which the original user id is saved
     * when using the impersonate user function.
     * Used inside actionSwitch().
     */
    const ORIGINAL_USER_SESSION_KEY = 'original_user';

    /** @var Finder */
    protected $finder;

    /**
     * @param string  $id
     * @param Module2 $module
     * @param Finder  $finder
     * @param array   $config
     */
//    public function __construct($id, $module, Finder $finder, $config = [])
//    {
//        $this->finder = $finder;
//        parent::__construct($id, $module, $config);
//    }

    /** @inheritdoc */
    public function behaviors() {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                    'confirm' => ['post'],
                    'resend-password' => ['post'],
                    'block' => ['post'],
                    'switch' => ['post'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['index', 'add', 'update', 'block'],
                'rules' => [

                    [
                        'allow' => true,
                        'actions' => ['index', 'add'],
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                    return (!Yii::$app->user->isGuest && \Yii::$app->user->identity->isOrgAdmin);
                }
                    ],
                    [
                        'allow' => true,
                        'actions' => ['changepassword', 'updateprofile'],
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['update', 'block'],
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                    if (isset($_REQUEST['id'])) {
                        $user = UserModel::findOne($_REQUEST['id']);
                        return $user != NULL ? ($user->org_id == \Yii::$app->user->identity->org_id && \Yii::$app->user->identity->isOrgAdmin) : false;
                    }
                }
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all User models.
     *
     * @return mixed
     */
    public function actionIndex() {
        //Url::remember('', 'actions-redirect');
        $searchModel = \Yii::createObject(UserSearch::className());
        $searchModel->org_id = \Yii::$app->user->identity->org_id;
        $dataProvider = $searchModel->search(\Yii::$app->request->get());

        return $this->render('index', [
                    'dataProvider' => $dataProvider,
                    'searchModel' => $searchModel,
        ]);
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'index' page.
     *
     * @return mixed
     */
    public function actionAdd() {
        /** @var User $user */
        $user = \Yii::createObject([
                    'class' => UserModel::className(),
                    'scenario' => 'create',
        ]);
        $user->role = UserModel::ROLE_ORG_USER;
        $user->org_id = \Yii::$app->user->identity->org_id;
        $user->status = 1;
        $event = $this->getUserEvent($user);

        $this->performAjaxValidation($user);

        $this->trigger(self::EVENT_BEFORE_CREATE, $event);
        if ($user->load(\Yii::$app->request->post()) && $user->create()) {
            $fileupload = \yii\web\UploadedFile::getInstances($user, 'profile_image');
            if ($fileupload != NULL) {
                if (!(file_exists(Yii::getAlias('@app') . '/web/upload/users/' . $user->id))) {
                    mkdir(Yii::getAlias('@app') . '/web/upload/users/' . $user->id);
                    chmod(Yii::getAlias('@app') . '/web/upload/users/' . $user->id, 0777);
                }
                $TEMP_FILE = Yii::getAlias('@app') . '/web/upload/users/' . $user->id . '/' . $fileupload[0]->name;
                $fileupload[0]->saveAs($TEMP_FILE);
                if (chmod($TEMP_FILE, 0777)) {
                    
                }
                $user->profile_image = $fileupload[0]->name;
                $user->update();
            }
            \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'User has been created'));
            $this->trigger(self::EVENT_AFTER_CREATE, $event);
            
            \app\entites\UserEntity::genrateNotification($user,1);
            return $this->redirect(['/member']);
        }

        return $this->render('create', [
                    'user' => $user,
        ]);
    }

    /**
     * Updates an existing User model.
     *
     * @param int $id
     *
     * @return mixed
     */
    public function actionUpdate($id) {
        Url::remember('', 'actions-redirect');
        $user = $this->findModel($id);
        $user->scenario = 'update';
        $event = $this->getUserEvent($user);

        $this->performAjaxValidation($user);
        $olgimage = $user->profile_image;
        $this->trigger(self::EVENT_BEFORE_UPDATE, $event);
        if ($user->load(\Yii::$app->request->post()) && $user->save()) {
            $fileupload = \yii\web\UploadedFile::getInstances($user, 'profile_image');
            if ($fileupload != NULL) {
                if (!(file_exists(Yii::getAlias('@app') . '/web/upload/users/' . $user->id))) {
                    mkdir(Yii::getAlias('@app') . '/web/upload/users/' . $user->id);
                    chmod(Yii::getAlias('@app') . '/web/upload/users/' . $user->id, 0777);
                }
                $TEMP_FILE = Yii::getAlias('@app') . '/web/upload/users/' . $user->id . '/' . $fileupload[0]->name;
                $fileupload[0]->saveAs($TEMP_FILE);
                if (chmod($TEMP_FILE, 0777)) {
                    
                }
                $user->profile_image = $fileupload[0]->name;
                $user->update();
            } else {
                $user->profile_image = $olgimage;
                $user->update();
            }
            \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Account details have been updated'));
            $this->trigger(self::EVENT_AFTER_UPDATE, $event);
            
            \app\entites\UserEntity::genrateNotification($user,0);
            
            return $this->redirect(['/member']);
        }

        return $this->render('_account', [
                    'user' => $user,
        ]);
    }

    public function actionUpdateprofile() {
        Url::remember('', 'actions-redirect');
        $user = $this->findModel(\Yii::$app->user->identity->id);
        $user->scenario = 'update';
        $event = $this->getUserEvent($user);

        $this->performAjaxValidation($user);
        $olgimage = $user->profile_image;
        $this->trigger(self::EVENT_BEFORE_UPDATE, $event);
        if ($user->load(\Yii::$app->request->post()) && $user->save()) {
            $fileupload = \yii\web\UploadedFile::getInstances($user, 'profile_image');
            if ($fileupload != NULL) {
                if (!(file_exists(Yii::getAlias('@app') . '/web/upload/users/' . $user->id))) {
                    mkdir(Yii::getAlias('@app') . '/web/upload/users/' . $user->id);
                    chmod(Yii::getAlias('@app') . '/web/upload/users/' . $user->id, 0777);
                }
                $TEMP_FILE = Yii::getAlias('@app') . '/web/upload/users/' . $user->id . '/' . $fileupload[0]->name;
                $fileupload[0]->saveAs($TEMP_FILE);
                if (chmod($TEMP_FILE, 0777)) {
                    
                }
                $user->profile_image = $fileupload[0]->name;
                $user->update();
            } else {
                $user->profile_image = $olgimage;
                $user->update();
            }
            \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Profile details have been updated'));
            $this->trigger(self::EVENT_AFTER_UPDATE, $event);
            return $this->redirect(['/dashboard']);
        }

        return $this->render('_updateprofile', [
                    'user' => $user,
        ]);
    }

    public function actionChangepassword() {
        /** @var User $user */
        $user = \Yii::createObject([
                    'class' => \app\models\form\ChangePasswordForm::className(),
        ]);

        $this->performAjaxValidation($user);

        if ($user->load(\Yii::$app->request->post()) && $user->save()) {
            \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'Password Change successfuly'));
            return $this->goHome();
        }

        return $this->render('changepassword', [
                    'user' => $user
        ]);
    }

    /**
     * Shows information about user.
     *
     * @param int $id
     *
     * @return string
     */
    public function actionInfo($id) {
        Url::remember('', 'actions-redirect');
        $user = $this->findModel($id);

        return $this->render('_info', [
                    'user' => $user,
        ]);
    }

    /**
     * Switches to the given user for the rest of the Session.
     * When no id is given, we switch back to the original admin user
     * that started the impersonation.
     *
     * @param int $id
     *
     * @return string
     */
    public function actionSwitch($id = null) {
        if (!$this->module->enableImpersonateUser) {
            throw new ForbiddenHttpException(Yii::t('user', 'Impersonate user is disabled in the application configuration'));
        }

        if (!$id && Yii::$app->session->has(self::ORIGINAL_USER_SESSION_KEY)) {
            $user = $this->findModel(Yii::$app->session->get(self::ORIGINAL_USER_SESSION_KEY));

            Yii::$app->session->remove(self::ORIGINAL_USER_SESSION_KEY);
        } else {
            if (!Yii::$app->user->identity->isAdmin) {
                throw new ForbiddenHttpException;
            }

            $user = $this->findModel($id);
            Yii::$app->session->set(self::ORIGINAL_USER_SESSION_KEY, Yii::$app->user->id);
        }

        $event = $this->getUserEvent($user);

        $this->trigger(self::EVENT_BEFORE_IMPERSONATE, $event);

        Yii::$app->user->switchIdentity($user, 3600);

        $this->trigger(self::EVENT_AFTER_IMPERSONATE, $event);

        return $this->goHome();
    }

    /**
     * Confirms the User.
     *
     * @param int $id
     *
     * @return Response
     */
    public function actionConfirm($id) {
        $model = $this->findModel($id);
        $event = $this->getUserEvent($model);

        $this->trigger(self::EVENT_BEFORE_CONFIRM, $event);
        $model->confirm();
        $this->trigger(self::EVENT_AFTER_CONFIRM, $event);

        \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'User has been confirmed'));

        return $this->redirect(Url::previous('actions-redirect'));
    }

    /**
     * Blocks the user.
     *
     * @param int $id
     *
     * @return Response
     */
    public function actionBlock($id) {
        if ($id == \Yii::$app->user->getId()) {
            \Yii::$app->getSession()->setFlash('danger', \Yii::t('user', 'You can not block your own account'));
        } else {
            $user = $this->findModel($id);
            $event = $this->getUserEvent($user);
            if ($user->getIsBlocked()) {
                $this->trigger(self::EVENT_BEFORE_UNBLOCK, $event);
                $user->unblock();
                $this->trigger(self::EVENT_AFTER_UNBLOCK, $event);
                \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'User has been unblocked'));
            } else {
                $this->trigger(self::EVENT_BEFORE_BLOCK, $event);
                $user->block();
                $this->trigger(self::EVENT_AFTER_BLOCK, $event);
                \Yii::$app->getSession()->setFlash('success', \Yii::t('user', 'User has been blocked'));
            }
        }

        return $this->redirect(['/member']);
    }

    /**
     * Generates a new password and sends it to the user.
     *
     * @return Response
     */
    public function actionResendPassword($id) {
        $user = $this->findModel($id);
        if ($user->isAdmin) {
            throw new ForbiddenHttpException(Yii::t('user', 'Password generation is not possible for admin users'));
        }

        if ($user->resendPassword()) {
            Yii::$app->session->setFlash('success', \Yii::t('user', 'New Password has been generated and sent to user'));
        } else {
            Yii::$app->session->setFlash('danger', \Yii::t('user', 'Error while trying to generate new password'));
        }

        return $this->redirect(Url::previous('actions-redirect'));
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param int $id
     *
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id) {
        $user = UserModel::findOne($id);
        if ($user === null) {
            throw new NotFoundHttpException('The requested page does not exist');
        }

        return $user;
    }

    /**
     * Performs AJAX validation.
     *
     * @param array|Model $model
     *
     * @throws ExitException
     */
    protected function performAjaxValidation($model) {
        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax) {
            if ($model->load(\Yii::$app->request->post())) {
                \Yii::$app->response->format = Response::FORMAT_JSON;
                echo json_encode(ActiveForm::validate($model));
                \Yii::$app->end();
            }
        }
    }

}
