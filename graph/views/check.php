<?php
if (!PREMISSION) {
	?>
	<h3 style="color: red;">Sie habe keinen Zugriff auf dieser Seite</h3>
	<?php
	exit();
} elseif (!MA_ID) {
	?>
	<h3 style="color: red;">Verknüpfen Sie zuerst dieses EGroupware-Benutzerkonto mit einem Mitarbeiter aus CAO um Rechnungen erstellen zu können!!</h3>
	<?php
	exit();
}
