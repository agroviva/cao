<style type="text/css">
	body {
		background: rgba(0, 0, 0, 0.9);
	}

	form {
		position: absolute;
		top: 50%;
		left: 50%;
		margin-top: -100px;
		margin-left: -250px;
		width: 500px;
		height: 200px;
		border: 4px dashed #fff;
	}

	form p {
		width: 100%;
		height: 100%;
		text-align: center;
		line-height: 170px;
		color: #ffffff;
		font-family: Arial;
	}

	form input {
		position: absolute;
		margin: 0;
		padding: 0;
		width: 100%;
		height: 100%;
		outline: none;
		opacity: 0;
	}

	form button {
		margin: 0;
		color: #fff;
		background: #16a085;
		border: none;
		width: 508px;
		height: 35px;
		margin-top: -20px;
		margin-left: -4px;
		border-radius: 4px;
		border-bottom: 4px solid #117A60;
		transition: all .2s ease;
		outline: none;
	}

	form button:hover {
		background: #149174;
		color: #0C5645;
	}

	form button:active {
		border: 0;
	}
</style>
<script type="text/javascript" src="/egroupware/cao/js/lib/jquery.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		$('form input').change(function() {
			$('form p').text(this.files.length + " file(s) selected");
		});
		$('button').click(function(){
			e.preventDefault();
			$('form').submit();
		});
	});
</script>
<form action="/egroupware/cao/controller/" method="POST" enctype="multipart/form-data">
	<input name="config" type="hidden" value="FileController@upload"> 
	<input multiple="multiple" name="UploadFile[]" type="file">
	<p>Drag your files here or click in this area.</p>
	<button type="submit">Upload</button>
</form>