<?php
use AgroEgw\DB;
use EGroupware\Api;
use GuzzleHttp\Client;

$client = new Client();
$categories = (new DB("SELECT * FROM egw_categories WHERE cat_appname = 'timesheet'"))->FetchAll();
$config = Api\Config::read('timesheet');

?>
<link rel="stylesheet" type="text/css" href="/egroupware/cao/css/settings.css">
<script type="text/javascript" src="/egroupware/cao/js/lib/jquery.js"></script>
<div class="container slider-one-active">
  <div class="steps">
    <div class="step step-one">
      <span>Kategorien</span>
    </div>
    <div class="step step-two">
      <span>Verknüpfung</span>
    </div>
    <div class="step step-three">
      <span>Status</span>
    </div>
  </div>
  <div class="line">
    <div class="dot-move"></div>
    <div class="dot zero"></div>
    <div class="dot center"></div>
    <div class="dot full"></div>
  </div>
  <div class="slider-ctr">
    <div class="slider">
      <form class="slider-form slider-one">
        <h2>Wählen Sie die Kategorien aus!</h2>
        <ul style="list-style: none;-webkit-padding-start: 0px;">
        <?php
            foreach ($categories as $key => $category) {
                ?>
					<li>
					<input class="categories" type="checkbox" id="<?php echo $category['cat_name']?>" name="categories[]" value="<?php echo $category['cat_id']?>">
		 			<label for="<?php echo $category['cat_name']?>"><?php echo $category['cat_name']?></label>
		 			</li>
				<?php
            }
        ?>
		</ul>
        <button class="first next">Nächster Schritt</button>
      </form>
      <form class="slider-form slider-two">
        <h2>Die jeweils zugehörige Artikelnummer eingeben!</h2>
        <div class="label-ctr artikel_cat">

        </div>
        <button class="second next">Nächster Schritt</button>
      </form>
      <div class="slider-form slider-three">
      	<div class="status_labels">
	       	<p>Nur die Stundenzettel mit diesem Status können in CAO-Faktura importiert werden:</p>
			<select id="status_import" name="status_import" style="margin-top: -30px;">
				<option value="none">Status...</option>
				<?php
                foreach ($config['status_labels'] as $key => $status) {
                    ?>
					<option value="<?php echo $key?>"><?php echo $status['name']?></option>
					<?php
                }
                ?>
			</select>
			<p style="margin-top: -30px;">Bei einer erfolgreich erstellten Rechnung wird der Status der Stundenzettel geändert auf:</p>
			<select id="status_finish" name="status_finish" style="margin-top: -30px;">
				<option value="none">Status...</option>
				<?php
                foreach ($config['status_labels'] as $key => $status) {
                    ?>
					<option value="<?php echo $key?>"><?php echo $status['name']?></option>
					<?php
                }
                ?>
			</select>
			<br />
	        <button class="finish">Speichern</button>
	    </div>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript" src="/egroupware/cao/js/myapp.js"></script>
