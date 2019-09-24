<?php

namespace app\helpers;

use yii\db\Expression;
use app\models\Meeting;
use app\models\ActionPoint;

/**
 * ReminderHelper.
 *
 * @author Habibur Rahman <rahman.kld@gmail.com>
 */
class ReminderHelper {

    const REMINDER_MEETING = 1;
    const REMINDER_ACTION_TO_ASSIGN = 2;
    const REMINDER_ACTION_BY_ASSIGN = 3;

    /**
     * Wrapper for meeting or action reminder  text ReminderHelper method.
     * @return string
     */
    public static function get_reminder_text($type = ReminderHelper::REMINDER_MEETING, $meeting = NULL, $action = NULL, $user = NULL) {
        $string = '';
        if ($type == ReminderHelper::REMINDER_MEETING) {
            if ($meeting != NULL) {
                $string .='A ' . $meeting->meeting_name;
                if ($meeting->origin_source == Meeting::SOURCE_WBS and $meeting->meeting_type == '1') {
                    $string .=' with ' . $meeting->client_name . ' Meeting';
                }
                $string .='is scheduled to start at ';
                $string .=\Yii::$app->formatter->asDatetime($meeting->start_datetime, "php:d-m-Y h:i A");
            }
        } elseif ($type == ReminderHelper::REMINDER_ACTION_TO_ASSIGN) {
            if ($action != NULL) {
                $string .='A ' . $action->action . ' ';
                $string .='Action is due to complete on ';
                $string .=\Yii::$app->formatter->asDatetime($action->deadline, "php:d-m-Y h:i A");
            }
        } elseif ($type == ReminderHelper::REMINDER_ACTION_BY_ASSIGN) {
            if ($action != NULL) {
                $string .='A ' . $action->action . ' ';
                $string .='Action is not complete by ';
                if ($action->assignto != NULL) {
                    $string .= $action->assignto->name;
                }
                $string .= "Action deadline is ";
                $string .=\Yii::$app->formatter->asDatetime($action->deadline, "php:d-m-Y h:i A");
            }
        } else {
            
        }
        return $string;
    }

}
