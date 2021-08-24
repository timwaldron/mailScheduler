<div class="row">
  <div class="col-lg-12 content-right">
    <?php echo CHtml::beginForm('', 'POST', array('id'=>'settingsForm'));?>

      <h3 class="clearfix">Mail Schedule Settings
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
          <label class="default control-label col-sm-3" for="followUp0">Follow Up: 1</label>
          <input type="date" id="followUp0" name="followUp0" value="">
        </div>
        
        <div class="form-group setting setting-select">
          <label class="default control-label col-sm-3" for="followUp1">Follow Up: 2</label>
          <input type="date" id="followUp1" name="followUp1" value="">
        </div>
        
        <div class="form-group setting setting-select">
          <label class="default control-label col-sm-3" for="followUp2">Follow Up: 3</label>
          <input type="date" id="followUp2" name="followUp2" value="">
        </div>
        
        <div class="form-group setting setting-select">
          <label class="default control-label col-sm-3" for="followUp3">Follow Up: 4</label>
          <input type="date" id="followUp3" name="followUp3" value="">
        </div>
        
        <div class="form-group setting setting-select">
          <label class="default control-label col-sm-3" for="followUp4">Follow Up: 5</label>
          <input type="date" id="followUp4" name="followUp4" value="">
        </div>
        
        <div class="form-group setting setting-select">
          <label class="default control-label col-sm-3" for="followUp5">Follow Up: 6</label>
          <input type="date" id="followUp5" name="followUp5" value="">
        </div>
        
        <div class="form-group setting setting-select">
          <label class="default control-label col-sm-3" for="followUp6">Follow Up: 7</label>
          <input type="date" id="followUp6" name="followUp6" value="">
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
          console.log('Cannot find data for token/survey: <?php echo $_POST['usertoken'] ?> / <?php echo $surveyId ?>');
          document.getElementById('nullParticipant').innerHtml = '<h4><strong>Cannot find data for this participant in scheduling system, you can set and save it in this screen.</strong></h4>';
        }
        
        if (!payload) {
          payload = {
            recruitmentDate: '',
            surgeryDate: '',
            recalcFollowupDates: false,
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
        // TODO: Display notification that save was successful!
      }
    });
  }
</script>