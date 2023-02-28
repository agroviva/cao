<?php if (!CAO_API) {
	exit();
}
use AgroEgw\DB;
use CAO\Core\Adressen;

require APPDIR.'/graph/views/check.php';

$Address = new Adressen();
?>

<link rel="stylesheet" type="text/css" href="/egroupware/cao/css/table.css">
<h2>Diese Egroupware Adressen sind mit CAO Adressen verknüpft.</h2>
<table class="table-fill">
	<thead>
	  <tr>
		<th>Egroupware</th>
		<th>Cao (Kunden-Nr)</th>
	  </tr>
	</thead>
	<tbody class="table-hover">
		<?php
		$cao = Adressen::Find($Address->relationships['cao'])->toArray() ?: [];

		if (!empty($Address->relationships['egw'])) {
			$egw = (new DB('
				SELECT * FROM egw_addressbook 
				WHERE contact_id IN('.implode(',', $Address->relationships['egw']).')
			'))->FetchAll() ?: [];
		}

		foreach ((array) $Address->relationships['egw_cao'] as $egw_id => $cao_id) {
			if (!$row = $Address->SearchFor($cao, 'KUNNUM1', $cao_id)) {
				$cao_name = "<td style='color: red;'>----------</td>";
			} else {
				$cao_name = '<td>'.($row['NAME1'].' '.$row['NAME2'].' '.$row['NAME3'].' ('.$row['KUNNUM1'].')').'</td>';
			}
			//Dump($cao, $egw);
			?>
			<tr>
				<td><?php echo $Address->SearchFor($egw, 'contact_id', $egw_id)['n_fileas']?></td>
				<?php echo $cao_name?>
			</tr>
			<?php
		}
		?>
	</tbody>
</table>
