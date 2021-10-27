<?php
use CAO\Core\Collections\ArtikelCollection;

if (!CAO_API) {die();}

?>

<link rel="stylesheet" type="text/css" href="/egroupware/cao/css/cao.css">
<link rel="stylesheet" type="text/css" href="/egroupware/cao/css/nprogress.css">
<link rel="stylesheet" type="text/css" href="/egroupware/cao/css/table.css">
<link rel="stylesheet" href="https://maxcdn.icons8.com/fonts/line-awesome/1.1/css/line-awesome-font-awesome.min.css">
<script type="text/javascript" src="/egroupware/cao/js/lib/jquery.js"></script>
<script type="text/javascript" src="/egroupware/cao/js/lib/nprogress.js"></script>
<script type="text/javascript" src="/egroupware/cao/js/lib/jquery-ui.js"></script>
<script type="text/javascript" src="/egroupware/cao/js/lib/sweetalert.min.js"></script>

<?php
	require APPDIR.'/graph/views/check.php';

	$collection = new ArtikelCollection();
	$artikel = ArtikelCollection::Find(346);

	Dump($collection->diff($artikel));
	// echo $collection->All()->dump();