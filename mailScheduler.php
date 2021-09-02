<?php
/**
 * mailScheduler : Set up and configure survey reminders per participant
 *
 * @author Timothy Waldron <tim@waldrons.com.au>
 * @copyright 2021 Timothy Waldron <https://www.waldrons.com.au>
 * @license AGPL v3
 * @version 1.0.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

class mailScheduler extends PluginBase
{
  protected $storage = 'DbStorage';
  static protected $name = 'mailScheduler';
  static protected $description = 'Set up and configure survey reminders per participant';

  /**
   * Initial function, used to subscribe to events
   */
  public function init()
  {
    /* Adding link in the survey menu */
    $this->subscribe('beforeToolsMenuRender');
    
    // App()->setFlashMessage('Welcome to the mailScheduler plugin!');
  }

  /**
   * Rendering the item into the Tools submenu when in a survey config screen
   */
  public function beforeToolsMenuRender()
  {
    $event = $this->getEvent();
    $surveyId = $event->get('surveyId');

    $aMenuItem = array(
      'label' => 'Mail Scheduler',
      'iconClass' => 'fa fa-envelope-square',
      'href' => Yii::app()->createUrl(
        'admin/pluginhelper',
        array(
          'sa' => 'sidebody',
          'plugin' => get_class($this),
          'method' => 'actionSettings',
          'surveyId' => $surveyId
        )
      ),
    );

    // Check for old LimeSurvey
    $menuItem = class_exists("\LimeSurvey\Menu\MenuItem") ? new \LimeSurvey\Menu\MenuItem($aMenuItem) : new \ls\menu\MenuItem($aMenuItem);

    $event->append('menuItems', array($menuItem));
  }

  /**
   * Main Function
  */
  public function actionSettings($surveyId)
  {
    $oSurvey = Survey::model()->findByPk($surveyId);
    $aData = array();

    if (!$oSurvey)
    {
      throw new CHttpException(404, 'This survey does not seem to exist.');
    }

    if (!Permission::model()->hasSurveyPermission($surveyId, 'surveysettings', 'update'))
    {
      throw new CHttpException(403);
    }

    // Get all the participants in a survey
    $aData['users'] = Yii::app()->db->createCommand('SELECT token, firstname, lastname, attribute_10, attribute_11 FROM {{tokens_' . $surveyId . '}}')->queryAll();
    $aData['surveyId'] = $surveyId;

    // Create URLs to call the functions that ping the backend
    $aData['getSchedulerURI'] = Yii::app()->createUrl('admin/pluginhelper', array('plugin' => $this->getName(), 'sa'=>'sidebody', 'method'=>'getScheduleData'));
    $aData['saveSchedulerURI'] = Yii::app()->createUrl('admin/pluginhelper', array('plugin' => $this->getName(), 'sa'=>'sidebody', 'method'=>'saveScheduleData'));

    $content = $this->renderPartial('settings', $aData, true);

    return $content;
  }

  /**
   * Function to get the user schedule data
   */
  public function getScheduleData()
  {
    $token = $_GET['token'];
    $surveyId = $_GET['surveyId'];

    $response = $this->httpGet(array('token'=>$token, 'surveyId'=>$surveyId));
  
    echo $response;
  }

  /**
   * Function to save the user schedule data
   */
  public function saveScheduleData()
  {
    // Variabless from the HTML form submit
    $token = $_GET['token'];
    $surveyId = $_GET['surveyId'];

    // Get all users
    // Injury Type:   attribute_10
    // Surgury Date:  attribute_11 | TODO: Check if there's disparities between the surgery dates in the LS MySQL / MS MongoDB databases
    $users = Yii::app()->db->createCommand('SELECT token, firstname, lastname, email, attribute_10, attribute_11 FROM {{tokens_' . $surveyId . '}}')->queryAll();

    // Create the payload
    $userScheduleModel = array();

    // Find user from list of users and create user schedule model to be sent to the backend
    foreach ($users as $user)
    {
      if ($user['token'] == $token)
      {
        $userScheduleModel = $user;
      }
    }

    $userScheduleModel['surveyId'] = $surveyId;
    $userScheduleModel['recruitmentDate'] = $_GET['formData']['recruitmentDate'];
    $userScheduleModel['surgeryDate'] = $_GET['formData']['surgeryDate'];
    $userScheduleModel['injuryType'] = $_GET['formData']['injuryType'];
    $userScheduleModel['recalcFollowupDates'] = $_GET['formData']['recalcFollowupDates'];
    $userScheduleModel['followupDates'] = $_GET['formData']['followupDates'];

    $this->httpPost('', $userScheduleModel);
  }

  /**
   * Send a POST request to the backend API
   */
  private function httpPost($route, $postData)
  {
    // Create a new cURL resource
    $ch = curl_init('http://localhost:5000/scheduler' . $route);
    $payload = json_encode($postData);

    // Set the request options
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the POST request
    $response = curl_exec($ch);

    // Close cURL resource
    curl_close($ch);

    // Echo response
    echo $response;
  }

  /**
   * Send a GET request to the backend API
   */
  private function httpGet($params)
  {
    $postData = http_build_query($params, '', '&');
    $fullUrl = 'http://localhost:5000/scheduler/' . $params["surveyId"] . '?' . $postData;
    $fp = fopen(dirname(__FILE__) . '/errorlog.txt', 'w');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);

    return $output;
  }

  /**
   * Called externally to
   */
  public function initUserSchedule()
  {
    echo "<script>console.log('hello from initUserSchedule')";
    $payload = $_GET['payload'];
    
    $this->httpPost('/init', $payload);

    echo $_GET['data'];
  }
}