<?php   
namespace app\controllers;   
   
use Yii;
use app\models\UserModel;      
use app\models\Group;   
use yii\web\Controller;  
  


/**  
 * Group  
 **/   
class GroupController extends Controller   
{    
    /**  
     * Create  
     */   

   //  public function actionCreateNew1()   
   //  {   
   //      \Yii::$app->response->format = \yii\web\Response:: FORMAT_JSON;
   //       $model = new Group();  
   //      // $student->scenario = Group:: SCENARIO_CREATE;
        
   //     //   $model->attributes = \yii::$app->request->post();
   // //  die;
   //        if($model->validate())
   //        {
   //         $model->save();
   //         return array('status' => true, 'data'=> 'Student record is successfully updated');
   //        }
   //        else
   //        {
   //         return array('status'=>false,'data'=>$student->getErrors());    
   //        } 
   //      echo'hello';
   //      die;
             
   //     // return array('data'=>$student->getErrors());
   //  } 

    

    public function actionCreate()   
    {   
         
        $model = new Group();   
   
   //exit;
        // new record   
        if($model->load(Yii::$app->request->post())){ 

		$model->users=implode(',',$model->users);
                $model->org_id=\Yii::$app->user->identity->org_id;
        if($model->save())
        return $this->redirect(['index']);   
		// print_r( Yii::$app->request->post()); 
            // return $this->redirect(['index']);   
        }   
                   
        return $this->render('create', ['model' => $model]);   
    } 

 /**  
     * Read  
     */   
    public function actionIndex() 
	
    {  
       $group = Group::find()->all();
/*
$group = Group::find()
	->joinWith('user_group')
	->orderBy('user.id, user_group.id')
	->all();
*/
		

		
           
        return $this->render('index', ['model' => $group]);   
    } 

    /**  
     * Edit  
     * @param integer $id  
     */   
    public function actionEdit($id)   
    {   
        $model = Group::find()->where(['id' => $id])->one();   
   
        // $id not found in database   
        if($model === null)   
            throw new NotFoundHttpException('The requested page does not exist.');   
           
        // update record   
        if($model->load(Yii::$app->request->post())){  
		
		$model->users=implode(',',$model->users);
                $model->org_id=\Yii::$app->user->identity->org_id;

        if($model->save())
        return $this->redirect(['index']); 		
            return $this->redirect(['index']);   
        }   
        $model->users=explode(',',$model->users); 
        return $this->render('edit', ['model' => $model]);   
    } 


     /**  
    * Delete  
     * @param integer $id  
     */   
     public function actionDelete($id)   
     {   
         $model = Group::findOne($id);   
           
        // $id not found in database   
        if($model === null)   
            throw new NotFoundHttpException('The requested page does not exist.');   
               
        // delete record   
        $model->delete();   
           
        return $this->redirect(['index']);   
     }      
	
	
	
 }  
