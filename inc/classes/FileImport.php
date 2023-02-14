<?php

namespace CAO;

use EGroupware\Api;
use EGroupware\Api\Header\ContentSecurityPolicy as CSP;
use EGroupware\Api\Vfs;

class FileImport
{
    public function __construct()
    {
        $this->header();
        $this->content();
        $this->footer();
    }

    public function header()
    {
        CSP::add_script_src(['self', 'unsafe-eval', 'unsafe-inline']);
        CSP::add('font-src', ['fonts.gstatic.com']);
        CSP::add('font-src', ['maxcdn.icons8.com']);
        CSP::add('style-src', ['https://fonts.googleapis.com/']);
        CSP::add('style-src', ['https://maxcdn.icons8.com/']);
        CSP::add('script-src', ['https://cdn.datatables.net']);

        echo $GLOBALS['egw']->framework->header(); ?>
		<link rel="stylesheet" type="text/css" href="/egroupware/cao/css/bootstrap.css">
		<script type="text/javascript" src="/egroupware/cao/js/lib/jquery.js"></script>
		<script type="text/javascript" src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
		<script type="text/javascript" src="https://cdn.datatables.net/1.10.16/js/dataTables.bootstrap.min.js"></script>

		<?php
    }

    public static function escape($string)
    {
        $string = str_replace("\r", '', $string);
        $string = str_replace("\n", '', $string);

        return trim(htmlspecialchars($string, ENT_COMPAT | ENT_HTML401, 'ISO-8859-1'), ' ');
    }

    public function content()
    {
        header('Content-Type: text/html; charset=utf-8');
        $path = '/';
        if (isset($_REQUEST['path'])) {
            $path = !empty($_REQUEST['path']) ? $_REQUEST['path'] : $path;
            Api\Cache::setSession('cao', 'is_path', $path);
        }

        if (Api\Cache::getSession('cao', 'is_path')) {
            $path = Api\Cache::getSession('cao', 'is_path');
        } else {
            Api\Cache::setSession('cao', 'is_path', $path);
        }

        if (isset($path) && !empty($path)) {
            if ($path[0] != '/') {
                throw new Api\Exception\WrongUserinput('Not an absolute path!');
            }

            $is_dir = Vfs::is_dir($path);
            $is_file = Vfs::file_exists($path); ?>
			<script type="text/javascript">
				// Every two seconds....
				var path = "<?php echo htmlspecialchars($path); ?>";
				function ApplyPath(event){
					event.preventDefault(); 
					parent.postMessage({"dir_path": path}, window.location);
				}
			</script>
			<div class="container">
				<form method="GET">
					<div class="form-group">
						<label for="path">Pfad einfügen</label>
						<input type="text" class="form-control" name="path" id="path" value="<?php echo $path?>" placeholder="Pfad einfügen">
						<input type="hidden" name="menuaction" value="cao.cao_ui.init">
						<input type="hidden" name="type" value="file_import">
						<button type="submit" class="btn submit btn-primary">Absenden</button>
						<?php if ($is_dir) { ?>
							<button type="apply" class="btn apply btn-primary" onclick="ApplyPath(event);">Übernehmen</button>
						<?php } ?>
					</div>
				</form>
			<?php
            if ($is_dir) {// && ($d = Vfs::opendir($path)))
                $files = [];
                //while(($file = readdir($d)))
                foreach (Vfs::scandir($path) as $file) {
                    if (Vfs::is_readable($fpath = Vfs::concat($path, $file))) {
                        // echo $fpath."</br>";
                        $file = [
                            'url'  => Api\Html::a_href($file, 'cao.cao_ui.init&type=file_import', ['path'=>$fpath]),
                            'name' => $file,
                        ];
                        // $file['url'] .= ' ('.Vfs::mime_content_type($fpath).')';
                        $files[] = $file;
                    }
                }
                //closedir($d);
                $time2f = number_format(1000 * (microtime(true) - $time2), 1); ?>
				<style type="text/css">
					.table-foldersystem tbody tr td:first-child{
					  width:1%;
					}
				</style>
				<div class="col-md-12 col-xs-12" style="padding: 0;"> 
				  <table class="table table-bordered table-hover table-striped table-foldersystem">
					<thead>
					  <tr>
						<th>
							<?php if ($path != '/') { ?>
								<?php
                                $path = explode('/', $path);
                                unset($path[(count($path) - 1)]);
                                $path = implode('/', $path);
                                $path = $path ? $path : '/';
                                $goto = urlencode($path);
                                ?>
								<a href="/egroupware/index.php?path=<?php echo $goto?>&menuaction=cao.cao_ui.init&type=file_import"><-</a>
							<?php } ?>
						</th>
						<th>Name</th>
						<!-- <th>Action</th> -->
					  </tr>
					</thead>
					<tbody>
					  <?php foreach ($files as $key => $file) { ?>
						<?php if (strpos($file['name'], '.') === false) { ?>
							<tr>
								<td><i class="glyphicon glyphicon-folder-open"></i></td>
								<td><?php echo $file['url']?></td>
								<!-- <td></td> -->
							</tr>
						<?php } else { ?>
							<tr>
								<td><i class="glyphicon glyphicon-file"></i></td>
								<td><?php echo $file['url']?></td>
								<!-- <td><button>Import</button></td> -->
							 </tr>
						<?php } ?>
					  <?php } ?>
					   <!-- <tr>
						<td><i class="glyphicon glyphicon-picture"></i></td>
						<td>logo.jpg</td>
						<td>1 MB</td>
						<td>01.02.2015 15:12:34</td>
					  </tr>
					  <tr>
						<td><i class="glyphicon glyphicon-film"></i></td>
						<td>teaser.mp4</td>
						<td>141 MB</td>
						<td>22.09.2014 09:09:12</td>
					  </tr>
					  <tr>
						<td><i class="glyphicon glyphicon-file"></i></td>
						<td>index.php</td>
						<td>0.1 MB</td>
						<td>22.09.2014 09:11:56</td>
					  </tr> -->
					</tbody>
					</table>
				</div>
				<?php
            } elseif ($is_file) {
                ?>
				<style type="text/css">
					.dataTables_length select {
						line-height: 1.5;
					}
				</style>
				<?php

                $output = Core::readFile($path);
                Core::CsvToTable($output); ?>
				<script type="text/javascript">
					jQuery(document).ready(function() {
						$('#csv_output').DataTable();
					} );
				</script>
				<?php
            } ?>
			</div>
			<?php
        }
    }

    public function footer()
    {
        ?>
		<script type="text/javascript">
			// var fileToRead = "<?php echo $fileToRead?>";
			// console.log(fileToRead);
			// jQuery.ajax({
			//   url: fileToRead,
			//   method: "GET",
			//   success: function(res){
			//   	jQuery(".container").html(res);
			//   },
			//   error: function(){
			//   	console.log('Something went wrong!');
			//   },
			// });
		</script>
		<?php
        echo $GLOBALS['egw']->framework->footer();
    }
}
