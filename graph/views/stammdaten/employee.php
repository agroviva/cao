<?php if (!CAO_API) {
	exit();
}
use AgroEgw\DB;
use CAO\Core\Mitarbeiter;

?>

<link rel="stylesheet" type="text/css" href="/egroupware/cao/css/cao.css">
<link rel="stylesheet" type="text/css" href="/egroupware/cao/css/table.css">
<link rel="stylesheet" type="text/css" href="/egroupware/cao/css/employee.css">
<script type="text/javascript" src="/egroupware/cao/js/lib/jquery.js"></script>
<script type="text/javascript" src="/egroupware/cao/js/lib/jquery-ui.js"></script>
<script type="text/javascript" src="/egroupware/cao/js/lib/sweetalert.min.js"></script>

<?php
if (!PREMISSION) {
	?>
	<h3>Sie habe keinen Zugriff auf dieser Seite</h3>
	<?php
} else {
		$CaoMitarbeiter = Mitarbeiter::all(); ?>
	<div id="Mitarbeiter" style="margin: 0 auto;width: 840px;">
		<table>
			<thead>
				<tr>
					<th>CAO-Mitarbeiter</th>
					<th>EGroupware-Benutzerkonto</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($CaoMitarbeiter as $key => $Mitarbeiter) { ?>
				<tr>
					<td><?php echo $Mitarbeiter['ANZEIGE_NAME']?></td>
					<td>
						<ul class="input-list">
							<li class="input">
								<input type="text" placeholder="Suchen..." id="address" style="outline: none;">
							</li>
						</ul>
						<div id="<?php echo $Mitarbeiter['MA_ID']?>" class="addresses">
							<?php
							$MB = (new DB("SELECT * FROM egw_cao_meta WHERE meta_name = 'mitarbeiter' AND meta_data = '$Mitarbeiter[MA_ID]'"))->FetchAll();
							if ($MB) {
								foreach ($MB as $key => $data) {
									$query = "SELECT n_given, n_family FROM egw_addressbook WHERE account_id = '$data[meta_connection_id]'";
									$address = (new DB($query))->Fetch();
									$fullname = $address['n_family'].', '.$address['n_given']; ?>
										<div id="<?php echo $data['meta_connection_id']?>" class="address active" onclick="setUser(this)">
											<p><?php echo $fullname?></p>
											<span class="delete">×</span>
										</div>
									<?php
								}
							}
							?>
						</div>
					</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
	<script type="text/javascript">
		var $ = jQuery;
		//setup before functions
		var typingTimer;                //timer identifier
		var doneTypingInterval = 1000;  //time in ms, 5 second for example
		var $input = $("input#address");

		//on keyup, start the countdown
		$input.on('keyup', function () {
		  clearTimeout(typingTimer);
		  typingTimer = setTimeout(doneTyping(this), doneTypingInterval);
		});

		//on keydown, clear the countdown 
		$input.on('keydown', function () {
		  clearTimeout(typingTimer);
		});

		//user is "finished typing," do something
		function doneTyping (elem) {
			var addresses = $(elem).parent().parent().parent().find(".addresses");
			var value = $(elem).val();

			$.ajax({
				url: "/egroupware/json.php?menuaction=EGroupware\\Api\\Etemplate\\Widget\\Taglist::ajax_search&query="+value+"&app=addressbook&type=account&account_type=accounts",
				method: "GET",
				success: function(res){
					var text;
					console.log(res);
					if (res.length) {
						for (var i = 0; i < res.length; i++) {
							text = "<div id=\""+res[i].id+"\" class=\"address\" onclick=\"setUser(this)\"><p>"+res[i].label+"</p><span class=\"delete\">×</span></div>";
							if (i == 0) {
								addresses.html(text);
							} else {
								addresses.append(text);
							}
						}
					} else {
						addresses.html("");
					}
				},
				error: function(){
					console.log('Something went wrong!');
				},
			});
		}

		function setUser(elem){
			var cao_id = $(elem).parent()[0].id;
			$.ajax({
				url: "/egroupware/index.php?menuaction=cao.cao_ui.init&cd=no",
				method: "POST",
				data: "type=ajax&add=mitarbeiter&cao_id="+cao_id+"&account_id="+elem.id,
				success: function(data){
					if (data[0] == "ok") {
						$(elem).toggleClass("active");
					}
				},
				error: function(){
					console.log('Something went wrong!');
				},
			});
		}
	</script>
	<?php
	}
