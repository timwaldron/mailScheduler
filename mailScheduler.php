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
  }

  /**
   * Rendering the item into the Tools submenu when in a survey config screen
   */
  public function beforeToolsMenuRender()
  {
    $event = $this->getEvent();
    $surveyId = $event->get('surveyId');

    $aMenuItemUsers = array(
      'label' => 'Mail Scheduler - Users',
      'iconClass' => 'fa fa-envelope-square',
      'href' => Yii::app()->createUrl(
        'admin/pluginhelper',
        array(
          'sa' => 'sidebody',
          'plugin' => get_class($this),
          'method' => 'userSchedule',
          'surveyId' => $surveyId
        )
      ),
    );
    $aMenuItemSettings = array(
      'label' => 'Mail Scheduler - Global Settings',
      'iconClass' => 'fa fa-cog',
      'href' => Yii::app()->createUrl(
        'admin/pluginhelper',
        array(
          'sa' => 'sidebody',
          'plugin' => get_class($this),
          'method' => 'globalSettings',
          'surveyId' => $surveyId
        )
      ),
    );

    // Check for old LimeSurvey
    $menuUsers = class_exists("\LimeSurvey\Menu\MenuItem") ? new \LimeSurvey\Menu\MenuItem($aMenuItemUsers) : new \ls\menu\MenuItem($aMenuItemUsers);
    $menuSettings = class_exists("\LimeSurvey\Menu\MenuItem") ? new \LimeSurvey\Menu\MenuItem($aMenuItemSettings) : new \ls\menu\MenuItem($aMenuItemSettings);

    // Todo: potentially generate the token here?

    $event->append('menuItems', array($menuUsers, $menuSettings));
  }

  
  //////////////////// USER SCHEDULE SECTION //////////////////// 
  /**
   * Main Function - Users
  */
  public function userSchedule($surveyId)
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
    $aData['debugMailTestURI'] = Yii::app()->createUrl('admin/pluginhelper', array('plugin' => $this->getName(), 'sa'=>'sidebody', 'method'=>'debugMailTest'));

    $content = $this->renderPartial('userSchedule', $aData, true);

    return $content;
  }

  /**
   * Function to get the user schedule data
   */
  public function getScheduleData()
  {
    $token = $_GET['token'];
    $surveyId = $_GET['surveyId'];

    $response = $this->httpGet('scheduler/' . $surveyId, array('token'=>$token));
  
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

    $this->httpPost('scheduler', $userScheduleModel);
  }

  /**
   * Function to request a test mail based on the surgery date
   */
  public function debugMailTest()
  {
    // Variabless from the HTML form submit
    $payload = array();

    $payload['token'] = $_GET['token'];
    $payload['surveyId'] = $_GET['surveyId'];
    // $payload['followupDate'] = $_GET['followupDate'];

    echo $payload['token'] . ' | ' . $payload['surveyId'] . ' | ' . $payload['followupDate'] . ' | ';

    $this->httpGet('scheduler/mailtest/' . $_GET['followupDate'], $payload);
  }

  /**
   * Initialises a user in the scheduler system
   * This function is called by injecting javascript into the registration form question
   * 
   * TODO: Move this into a README of some sort
   * 
   * Example of question group description (This is where the script pulls the data from)
   *   <div id="data" style="display:none;">
   *    <input id="firstName" value="{TOKEN:FIRSTNAME}" />
   *    <input id="lastName" value="{TOKEN:LASTNAME}" />
   *    <input id="email" value="{TOKEN:EMAIL}" />
   *    <input id="injuryType" value="{TOKEN:ATTRIBUTE_10}" />
   *    <input id="surgeryDate" value="{TOKEN:ATTRIBUTE_11}" />
   *    <input id="token" value="{TOKEN:TOKEN}" />
   *    <input id="surveyId" value="{SID}" />
   *   </div>
   * 
   * Example of JavaScript to be injected into registration form question:
   *   $(document).ready(function () {
   *     const payload = {
   *       firstName: document.getElementById('firstName').value,
   *       lastName: document.getElementById('lastName').value,
   *       email: document.getElementById('email').value,
   *       injuryType: document.getElementById('injuryType').value,
   *       surgeryDate: document.getElementById('surgeryDate').value,
   *       token: document.getElementById('token').value,
   *       surveyId: document.getElementById('surveyId').value,
   *     };
   *     
   *     $.ajax({
   *       url: '/index.php?r=admin/pluginhelper&plugin=mailScheduler&sa=sidebody&method=initUserSchedule',
   *       type: 'GET',
   *       data: { payload, },
   *       success: function (response) { }, // Do something with response if you want
   *       error: function(xhr) { } // Do something with response if you want
   *     });
   *   });
   */
  public function initUserSchedule()
  {
    $payload = $_GET['payload'];
    
    $this->httpPost('scheduler/init', $payload);

    echo $_GET['data'];
  }


  //////////////////// GLOBAL SETTINGS SECTION //////////////////// 
  /**
   * Main Function - Plugin Settings
   */
  public function globalSettings($surveyId)
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

    // Get all the plugin settings
    $aData['settings'] = ''; // API Call to backend to get settings
    $aData['surveyId'] = $surveyId;

    // Create URLs to call the functions that ping the backend
    $aData['getTelemetryURI'] = Yii::app()->createUrl('admin/pluginhelper', array('plugin' => $this->getName(), 'sa'=>'sidebody', 'method'=>'getTelemetry'));
    $aData['getMSSettingsURI'] = Yii::app()->createUrl('admin/pluginhelper', array('plugin' => $this->getName(), 'sa'=>'sidebody', 'method'=>'getMSSettings'));
    $aData['saveMSSettingsURI'] = Yii::app()->createUrl('admin/pluginhelper', array('plugin' => $this->getName(), 'sa'=>'sidebody', 'method'=>'saveMSSettings'));

    $content = $this->renderPartial('globalSettings', $aData, true);

    return $content;
  }

  public function getTelemetry()
  {
    $response = $this->httpGet('telemetry', array());
    echo $response;
  }

  public function getMSSettings()
  {

  }

  public function saveMSSettings()
  {

  }


  //////////////////// PRIVATE INTERNAL FUNCTIONS //////////////////// 
  /**
   * Send a POST request to the backend API
   */
  private function httpPost($route, $postData)
  {
    // Create a new cURL resource
    $ch = curl_init('http://localhost:5000/' . $route);
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
  private function httpGet($route, $params)
  {
    $postData = http_build_query($params, '', '&');
    $fullUrl = 'http://localhost:5000/' . $route . '?' . $postData;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);

    return $output;
  }
}