<?php

use AgroEgw\DB;

$Data = (new DB("SELECT * FROM egw_cao_meta WHERE meta_name LIKE 'settings'"))->Fetch();

if (!empty($Data)) {
    $Settings = json_decode($Data['meta_data'], true);
}

?>
<div class="container">
    <form>
	    <input name="config" type="hidden" value="SaveSettings@upload"> 
        <div class="form-group">
            <label for="inputMySQLServer">MySQL Server</label>
            <input type="text" name="MySQLServer" class="form-control" id="inputMySQLServer" value="<?php echo $Settings['MySQLServer']?>" required>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="inputMySQLUsername">MySQL Benutzername</label>
                <input type="text" name="MySQLUsername" class="form-control" id="inputMySQLUsername" value="<?php echo $Settings['MySQLUsername']?>" required>
            </div>
            <div class="form-group col-md-6">
                <label for="inputMySQLPassword">MySQL Passwort</label>
                <input type="text" name="MySQLPassword" class="form-control" id="inputMySQLPassword" value="<?php echo $Settings['MySQLPassword']?>" required>
            </div>
            <div class="form-group col-md-12">
                <label for="inputMySQLDatabase">MySQL Datenbank</label>
                <input type="text" name="MySQLDatabase" class="form-control" id="inputMySQLDatabase" value="<?php echo $Settings['MySQLDatabase']?>" required>
            </div>
        </div>
        <div class="form-group">
            <label for="inputSFTP">SFTP Server</label>
            <input type="text" name="SFTPServer" class="form-control" id="inputSFTP" value="<?php echo $Settings['SFTPServer']?>" required>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="inputSFTPUsername">Benutzername</label>
                <input type="text" name="SFTPUsername" class="form-control" id="inputSFTPUsername" value="<?php echo $Settings['SFTPUsername']?>" required>
            </div>
            <div class="form-group col-md-6">
                <label for="inputSFTPPassword">Passwort</label>
                <input type="text" name="SFTPPassword" class="form-control" id="inputSFTPPassword" value="<?php echo $Settings['SFTPPassword']?>" required>
            </div>
            <div class="form-group col-md-12">
                <label for="inputSFTPPath">Pfad</label>
                <input type="text" name="SFTPPath" class="form-control" id="inputSFTPPath" value="<?php echo $Settings['SFTPPath']?>" required>
            </div>
        </div>
    </form>
    <button class="btn btn-primary submit-form">Speichern</button>
</div>

<!--   Core JS Files   -->
<script src="/egroupware/threecx/material/assets/js/core/jquery.min.js" type="text/javascript"></script>
<script src="/egroupware/threecx/material/assets/js/core/popper.min.js" type="text/javascript"></script>
<script src="/egroupware/threecx/material/assets/js/core/bootstrap-material-design.min.js" type="text/javascript"></script>
<script src="/egroupware/threecx/material/assets/js/plugins/moment.min.js"></script>
<!--	Plugin for the Datepicker, full documentation here: https://github.com/Eonasdan/bootstrap-datetimepicker -->
<script src="/egroupware/threecx/material/assets/js/plugins/bootstrap-datetimepicker.js" type="text/javascript"></script>
<!--  Plugin for the Sliders, full documentation here: http://refreshless.com/nouislider/ -->
<script src="/egroupware/threecx/material/assets/js/plugins/nouislider.min.js" type="text/javascript"></script>
<!--	Plugin for Sharrre btn -->
<script src="/egroupware/threecx/material/assets/js/plugins/jquery.sharrre.js" type="text/javascript"></script>
<!-- Control Center for Material Kit: parallax effects, scripts for the example pages etc -->
<script src="/egroupware/threecx/material/assets/js/material-kit.js?v=2.0.4" type="text/javascript"></script>
<script src="/egroupware/threecx/js/sweetalert.min.js" type="text/javascript"></script>
<script>
    $("button.submit-form").click(function(e){
        e.preventDefault();
        $.post('/egroupware/cao/controller/', $("form").serialize(), function(data){
            if (data.responde == "success") {
                window.location.href = "/egroupware/cao/graph/datenbank.php";
            } else {
                swal("Fehler!", {
                    text: "Etwas hat nicht funktioniert!",
                    button: false,
                    icon: "error",
                    timer: 2000
                });
            }
        });
    });
</script>