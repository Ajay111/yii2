<?php

namespace app\components;

use Yii;
use app\models\Notification;

/**
 * @author Habibur Rahman <rahman.kld@gmail.com>
 */
//define('FIREBASE_API_KEY', 'AIzaSyAWNxsQ9jWBsSlm5VT3mhJF2GFp1_kkAbY');
//define('FIREBASE_API_KEY', 'AIzaSyCABcUJZ9NUsrIMWVwAl9LtNGejicv0laM');
//define('FIREBASE_API_KEY', 'AIzaSyA2CS05jPEST2T1OWpHtEJYTxxpt_1r-xI');
//define('FIREBASE_API_KEY', 'AIzaSyCm_sjYJOu1r4Cuk7vSUmQzKeXMqm05OYE');
define('FIREBASE_API_KEY', 'AIzaSyCywLYiukuleNv3Oi4oMuT4QDMcWXucC6w');



class GoogleFirebase {

    public $url = "https://fcm.googleapis.com/fcm/send";  
    //public $image = "http://alert.nrb.co.in/images/nrblogo.png";

    /**
     * @var \app\models\Notification
     */
    public $notification_model;

    // sending push message to single user by firebase reg id
    public function __construct($notification_model) {
        $this->notification_model = $notification_model;   
    }

    public function getPush() {
        $res = array();
        $res['title'] = "ALGN"." " . $this->notification_model->message_title;
        $res['message'] = $this->notification_model->message;
        $res['visible'] = $this->notification_model->visible;
        $res['module_type'] = $this->notification_model->notification_type;
        $res['module_sub_type'] = $this->notification_model->notification_sub_type;
        $res['detail_id'] = $this->notification_model->detail_id;
        $res['id'] = $this->notification_model->id;
		$res['updated_by']=$this->notification_model->updated_by;
		$res['created_by']=$this->notification_model->created_by;
        $res['genrated_on'] = $this->notification_model->genrated_on;
        
        
        //$res['data']['is_background'] = $this->is_background;
        //$res['data']['message'] = $this->notification_model->message;
        //$res['data']['image'] = $this->image;
        //$res['data']['additionalData'] = $this->notification_model->content;
        //$res['data']['additionalData']['pf_id'] = $this->notification_model->premium_freight_id;
        //$res['data']['additionalData']['status'] = $this->notification_model->approval_status;
        //$res['data']['additionalData']['sub_segment'] = "CV/2W";//$this->notification_model->approval_status;
        //$res['data']['additionalData']['customer_name'] = "Custome name is very big then how will you show";//$this->notification_model->approval_status;
        //$res['data']['additionalData']['item_description1'] = "ITEM desction is also very long";
        //$res['data']['additionalData']['item_count'] = "3";
        //$res['data']['additionalData']['origin'] = "Plant/Warehoue";
        //$res['data']['additionalData']['request_id'] = $this->notification_model->id;
        //$res['data']['additionalData']['content'] = $this->notification_model->content;
        //$res['data']['timestamp'] = date('Y-m-d G:i:s');
        //$res['data']['content-available'] = $this->content_availabel;
        //$res['data']['sound'] = "chime";
        //$res['data']['force-start']='1';

        return $res;
    }

    public function send($to) {
        $fields = array(
            'to' => $to,
            'data' => $this->getPush(),
        );
        return $this->sendPushNotification($fields);
    }

    // Sending message to a topic by topic name
    public function sendToTopic($to, $message) {
        $fields = array(
            'to' => '/topics/' . $to,
            'data' => $message,
        );
        return $this->sendPushNotification($fields);
    }

    // sending push message to multiple users by firebase registration ids
    public function sendMultiple($registration_ids, $message) {
        $fields = array(
            'to' => $registration_ids,
            'data' => $message,
        );

        return $this->sendPushNotification($fields);
    }

    // function makes curl request to firebase servers
    private function sendPushNotification($fields) {
        $headers = array(
            'Authorization: key=' . FIREBASE_API_KEY,
            'Content-Type: application/json'
        );
      //  print_r($fields);die;
        // Open connection
        $ch = curl_init();

        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $this->url);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        // Execute post
        $result = curl_exec($ch);
        if ($result === FALSE) {
            //die('Curl failed: ' . curl_error($ch));
        }

        // Close connection
        curl_close($ch);
       // print_r($result);die;
        
        return $result;
    }
}
?>