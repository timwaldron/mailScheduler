<div class="row">
  <div class="col-lg-12 content-right">
    <?php echo CHtml::beginForm('', 'POST', array('id'=>'settingsForm'));?>
      <h3 class="clearfix">Mail Schedule Settings
        <div class='pull-right'>
          <button id="save-btn" style="display: none;" type="button" class="btn btn-primary">
            <i class="fa fa-check" aria-hidden="true"></i> Save
          </button>

          <?php
            echo CHtml::link('Close', Yii::app()->createUrl('admin/survey', array('sa'=>'view', 'surveyid'=>$surveyId)), array('class'=>'btn btn-danger'));
          ?>
        </div>
      </h3>
      
      <div class="form-group setting setting-select">
        <label class="default control-label col-sm-3" for="cronExpression">CRON expression for scheduled emails</label>
        
        <input type="text" id="cronExpression" name="cronExpression" />
      </div>

      <h3>Telemetry Data</h3>
      
      <div class="form-group setting setting-select">
        <label class="default control-label col-sm-3" for="telemetryData">Telemetry data</label>
        
        <textarea type="textarea" id="telemetryData" name="telemetryData" style="width: 70%; font-family:'Lucida Console', monospace;" rows="30" placeholder="Telemetry logs will appear here..."></textarea>
      </div>

    <?php echo CHtml::endForm();?>
  </div>
</div>

<script>
  // Get the global settings from the backend
  function getSettings() {

  }

  // Get the telemetry data from the backend
  function getTelemetryData() {
    $.ajax({
      url: '<?php echo $getTelemetryURI ?>',
      type: 'GET', 
      data: { },
      success: function(response) {
        const result = JSON.parse(response.split('<!DOCTYPE html>')[0]);
        document.getElementById('telemetryData').value = '';
        
        for (let line of result) {
          document.getElementById('telemetryData').value += line + String.fromCharCode(13, 10);
        }
      },
      error: function(xhr) {
        // TODO: Display notification that save was unsuccessful!
      }
    });
  }

  //getSettings();
  getTelemetryData();
</script>