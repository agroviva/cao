<?php
use AgroEgw\DB;

class Setup
{
	public function __construct()
	{
		$this->load();
	}

	public function load()
	{
		$this->create_header();
		if (!empty($this->settings)) {
			if (!is_array($this->decodedSet['categories'][0])) {
				$this->setCategoryIds();
			} else {
				$this->index();
			}
			$this->create_footer();
			exit();
		}
		$this->InsertData();
	}

	public function InsertData($warning = false)
	{
		$this->create_header();
		if ($warning) {
			echo "<p style=\"color: red;\">{$warning}</p>";
		} ?>
		<form action="/egroupware/index.php?menuaction=cao.cao_ui.request&appname=cao" method="POST">
			<h3>Bevor Sie mit dem Import der Stundenzettel anfangen, sollten Sie erstmal die Software vollständig einstellen.</h3>
			<p>Nach welche Kategorien filtern? (Mehrauswahl ist möglich)</p>
			<?php
			foreach ($this->categories as $key => $category) {
				?>
				<input type="checkbox" id="<?php echo $category['cat_id']?>" name="categories[]" value="<?php echo $category['cat_id']?>">
				<label for="<?php echo $category['cat_id']?>"><?php echo $category['cat_name']?></label>
				<?php
			} ?>

			<p>Nur die Stundenzettel mit diesem Status können in CAO Faktura importiert werden:</p>
			<select id="status" name="status">
				<option value="none">Status...</option>
				<?php
				foreach ($this->config['status_labels'] as $key => $status) {
					?>
					<option value="<?php echo $key?>"><?php echo $status['name']?></option>
					<?php
				} ?>
			</select>
			<button type="submit">Speichern</button>
		</form>
		<?php

		//Dump($this->me);
		//Dump($this->config, $this->categories);
		//Dump((new DB("SELECT * FROM egw_timesheet WHERE ts_owner = '116' AND cat_id = '318' AND ts_status = '1' ORDER BY ts_start DESC"))->FetchAll());
		$this->create_footer();
	}

	public function request()
	{
		$POST = $_POST;
		if (!empty($_GET['callback'])) {
			call_user_func([$this, $_GET['callback']]);
			exit();
		}
		if (!empty($POST)) {
			if (empty($POST['categories'])) {
				$this->InsertData('Wähle mindestens eine Kategorie aus!');
			} elseif ($POST['status'] == 'none') {
				$this->InsertData('Bitte wählen Sie einen status aus!');
			} else {
				$data = json_encode($_POST);
				(new DB("INSERT INTO egw_cao SET data = '{$data}'"));
				$GLOBALS['egw']->redirect_link('/index.php', 'menuaction=cao.cao_ui.init');
			}
		} else {
			$this->InsertData('Keine Daten wurden verschickt!! Bitte, versuchen Sie wieder!');
		}
	}

	public function setCategoryIds()
	{
		if (!empty($_POST)) {
			if (!empty($_POST['cat'])) {
				$i = 0;
				foreach ($_POST['cat'] as $key => $value) {
					$this->decodedSet['categories'][$i] = [
						'cat_id'	   => $key,
						'cao_value'	=> $value,
					];
					$i++;
				}
			}
			(new DB("UPDATE egw_cao SET data = '".json_encode($this->decodedSet)."' WHERE id = '".$this->settings['id']."'"));
			$GLOBALS['egw']->redirect_link('/index.php', 'menuaction=cao.cao_ui.init');
			exit();
		} ?>
		<h3>Tragen Sie die zugehörige Artikelnummer pro Kategorie ein.</h3>
		<form action="/egroupware/index.php?menuaction=cao.cao_ui.request&callback=setCategoryIds" method="POST">
		<?php
		foreach ($this->decodedSet['categories'] as $key => $category) {
			?>
			<span><?php echo $this->CatFromId($category)['cat_name']?></span>
			<input type="number" name="cat[<?php echo $category?>]">
			<br />
			<?php
		} ?>
		<button type="submit">Speichern</button>
		</form>
		<?php
		//Dump($this->settings);
	}

	public function CatFromId($id)
	{
		return (new DB())->Query("SELECT * FROM egw_categories WHERE cat_id = '".$id."'")->Fetch();
	}
}
