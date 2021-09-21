<div class="row">
  <div class="col-lg-12 content-right">
    <?php echo CHtml::beginForm('', 'POST', array('id'=>'settingsForm'));?>
      <h3 class="clearfix">User Schedules
        <div class='pull-right'>
          <button id="save-btn" style="display: none;" type="button" class="btn btn-primary" onclick="saveScheduleData();">
            <i class="fa fa-check" aria-hidden="true"></i> Save
          </button>

          <?php
            echo CHtml::link('Close', Yii::app()->createUrl('admin/survey', array('sa'=>'view', 'surveyid'=>$surveyId)), array('class'=>'btn btn-danger'));
          ?>
        </div>
      </h3>

      <div class="form-group setting setting-select">
        <label class="default control-label col-sm-3" for="usertoken">Select a patient to configure email reminders</label>

        <select id="usertoken" name="usertoken" class="" onchange="getScheduleData();">
          <option value="">Select Participant</option>
          <?php
            foreach ($users as $user) {
          ?>
            <option value="<?php echo $user['token'];?>">
              <?php echo $user['firstname'] . ' ' . $user['lastname'];?>
            </option>
          <?php
            }
          ?>
        </select>
      </div>

      <div class="form-group setting setting-select">
        <label class="default control-label col-sm-3" for="injuryType">Patient injury type</label>
        
        <select id="injuryType" name="injuryType">
          <option value="">Category of Patient</option>
          <option value="H">Hip</option>
          <option value="KN">Knee Non-Arthritis</option>
          <option value="KA">Knee Arthroplasty</option>
          <option value="SA">Shoulder Arthroplasty</option>
          <option value="SI">Shoulder Instability</option>
        </select>
      </div>

      <div id="followupContent">
        <h3>Dates</h3>
        <div id="nullParticipant"></div>

        <!-- Recruitment Date -->
        <div class="form-group setting setting-select">
          <label class="default control-label col-sm-3" for="recruitmentDate">Recruitment Date</label>
          <input type="date" id="recruitmentDate" name="recruitmentDate" value="">
        </div>

        <!-- Surgery Date -->
        <div class="form-group setting setting-select">
          <label class="default control-label col-sm-3" for="surgeryDate">Surgery Date</label>
          <input type="date" id="surgeryDate" name="surgeryDate" value="">
        </div>

        
        <div class="form-group setting setting-select">
          <label class="default control-label col-sm-3" for="recalcFollowupDates">Recalculate Follow-up Dates?</label>
          <input type="checkbox" id="recalcFollowupDates" name="recalcFollowupDates">
        </div>
        
        <!-- Followup Dates -->
        <h3>Follow Up Dates</h3>
        <div class="form-group setting setting-select">
          <label class="default control-label col-sm-3" for="debugMailTest">Enable Debug Mail Test?</label>
          <input type="checkbox" id="debugMailTest" name="debugMailTest" onchange="toggleDebugMailTest(event.target.checked);">
        </div>

        <div class="form-group setting setting-select">
          <label class="default control-label col-sm-3" for="followUp0">Follow Up: 1</label>
          <input type="date" id="followUp0" name="followUp0" value="">
          <button type="button" id="testMail0" name="testMail0" style="display: none;" onclick="handleMailTest('followUp0');">Test Email</button>
        </div>
        
        <div class="form-group setting setting-select">
          <label class="default control-label col-sm-3" for="followUp1">Follow Up: 2</label>
          <input type="date" id="followUp1" name="followUp1" value="">
          <button type="button" id="testMail1" name="testMail1" style="display: none;" onclick="handleMailTest('followUp1');">Test Email</button>
        </div>
        
        <div class="form-group setting setting-select">
          <label class="default control-label col-sm-3" for="followUp2">Follow Up: 3</label>
          <input type="date" id="followUp2" name="followUp2" value="">
          <button type="button" id="testMail2" name="testMail2" style="display: none;" onclick="handleMailTest('followUp2');">Test Email</button>
        </div>
        
        <div class="form-group setting setting-select">
          <label class="default control-label col-sm-3" for="followUp3">Follow Up: 4</label>
          <input type="date" id="followUp3" name="followUp3" value="">
          <button type="button" id="testMail3" name="testMail3" style="display: none;" onclick="handleMailTest('followUp3');">Test Email</button>
        </div>
        
        <div class="form-group setting setting-select">
          <label class="default control-label col-sm-3" for="followUp4">Follow Up: 5</label>
          <input type="date" id="followUp4" name="followUp4" value="">
          <button type="button" id="testMail4" name="testMail4" style="display: none;" onclick="handleMailTest('followUp4');">Test Email</button>
        </div>
        
        <div class="form-group setting setting-select">
          <label class="default control-label col-sm-3" for="followUp5">Follow Up: 6</label>
          <input type="date" id="followUp5" name="followUp5" value="">
          <button type="button" id="testMail5" name="testMail5" style="display: none;" onclick="handleMailTest('followUp5');">Test Email</button>
        </div>
        
        <div class="form-group setting setting-select">
          <label class="default control-label col-sm-3" for="followUp6">Follow Up: 7</label>
          <input type="date" id="followUp6" name="followUp6" value="">
          <button type="button" id="testMail6" name="testMail6" style="display: none;" onclick="handleMailTest('followUp6');">Test Email</button>
        </div>
      </div>

    <?php echo CHtml::endForm();?>
  </div>
</div>

<script>
  function getScheduleData() {
    const userToken = document.getElementById('usertoken').value;

    if (userToken) {
      document.getElementById('save-btn').style.display = 'inline-block';
    }

    $.ajax({
      url: '<?php echo $getSchedulerURI ?>',
      type: 'GET', 
      data: { 
        token: userToken,
        surveyId: '<?php echo $surveyId ?>',
      },
      success: function(response) {
        // Yes, there is a more elegant way of doing this, but timelines etc.
        let payload = undefined;
        document.getElementById('nullParticipant').innerHtml = '';

        try {
          payload = JSON.parse(response.split('<!DOCTYPE html>')[0]); // Split by end-of-payload & start of HTML
        } catch (e) {
          console.log('Cannot find data for token/survey: ' + userToken + ' / <?php echo $surveyId ?>');
          document.getElementById('nullParticipant').innerHtml = '<h4><strong>Cannot find data for this participant in scheduling system, you can set and save it in this screen.</strong></h4>';
        }
        
        if (!payload) {
          payload = {
            recruitmentDate: '',
            surgeryDate: '',
            recalcFollowupDates: false,
            injuryType: '',
            followupDates: [
              '', // 6 Weeks
              '', // 3 Months
              '', // 6 Months
              '', // 12 Months
              '', // 2 Years
              '', // 5 Years
              '', // 10 Years
            ],
          };
        }
        
        // Setting form data values
        document.getElementById('recruitmentDate').value = payload['recruitmentDate'];
        document.getElementById('surgeryDate').value = payload['surgeryDate'];
        document.getElementById('injuryType').value = payload['injuryType'];
        document.getElementById('recalcFollowupDates').checked = false;

        // Followup dates
        let index = 0;
        for (let date of payload['followupDates']) {
          document.getElementById('followUp' + index).value = payload['followupDates'][index];
          index++;
        }
      },
      error: function(xhr) {
        document.getElementById('followupContent').innerHTML = 'Failed: <pre>' + JSON.stringify(xhr) + '</pre>';
      }
    });
  }
  
  function saveScheduleData() {
    $.ajax({
      url: '<?php echo $saveSchedulerURI ?>',
      type: 'GET', 
      data: {
        token: document.getElementById('usertoken').value,
        surveyId: '<?php echo $surveyId ?>',
        formData: {
          recruitmentDate: document.getElementById('recruitmentDate').value,
          surgeryDate: document.getElementById('surgeryDate').value,
          injuryType: document.getElementById('injuryType').value,
          recalcFollowupDates: document.getElementById('recalcFollowupDates').checked,
          followupDates: [
            document.getElementById('followUp0').value, // 6 Weeks
            document.getElementById('followUp1').value, // 3 Months
            document.getElementById('followUp2').value, // 6 Months
            document.getElementById('followUp3').value, // 12 Months
            document.getElementById('followUp4').value, // 2 Years
            document.getElementById('followUp5').value, // 5 Years
            document.getElementById('followUp6').value, // 10 Years
          ]
        },
      },
      success: function(response) {
        // TODO: Display notification that save was successful!

        if (document.getElementById('recalcFollowupDates').checked === true) {
          getScheduleData(); // Reload form data with recalculated dates
        }
      },
      error: function(xhr) {
        // TODO: Display notification that save was unsuccessful!
      }
    });
  }

  function toggleDebugMailTest(showDebug) {
    const display = showDebug ? 'inline-block' : 'none';

    for (let i = 0; i < 100; i++) {
      const debugBtn = document.getElementById('testMail' + i);

      if (!debugBtn) {
        break;
      }
      
      debugBtn.style.display = display;
    }
  }

  function handleMailTest(domElementId) {
    const date = document.getElementById(domElementId);

    if (!date) {
      return;
    }

    $.ajax({
      url: '<?php echo $debugMailTestURI ?>',
      type: 'GET',
      data: { 
        token: document.getElementById('usertoken').value,
        surveyId: '<?php echo $surveyId ?>',
        followupDate: date.value,
      },
      success: function(response) {
        // TODO: Display notification that save was successful!
      },
      error: function(xhr) {
        // TODO: Display notification that save was unsuccessful!
      }
    });
  }
</script>