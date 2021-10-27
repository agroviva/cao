<?php

namespace CAO;

class UI
{
    public static function Error($msg, $color = 'red')
    {
        ?>
			<h3 style="color: <?php echo $red?>;"><?php echo $msg?></h3>
		<?php
    }

    public static function Warning($msg)
    {
        ?>
			<p class="warning" style="text-align: center;background: red;color: white;">
				<?php echo $msg?>
			</p>
		<?php
    }

    public static function Rechnung()
    {
        ?><div class="holder"><a class="btn btn-icon"><span class="icon"><img src="/egroupware/cao/icons/invoice.svg"></span><span class="label">Rechnung</span></a></div><?php
    }

    public static function Einkauf()
    {
        ?> <div class="holder"> <a class="btn btn-icon"> <span class="icon"> <img src="/egroupware/cao/icons/einkauf.svg"> </span> <span class="label">Einkauf</span> </a> </div> <?php
    }

    public static function EKBestellung()
    {
        ?> <div class="holder"> <a class="btn btn-icon"> <span class="icon"> <img src="/egroupware/cao/icons/ekbestellung.svg"> </span> <span class="label">EK-Bestellung</span> </a> </div> <?php
    }

    public static function Lieferschein()
    {
        ?><div class="holder"><a class="btn btn-icon"><span class="icon"><img src="/egroupware/cao/icons/lieferschein.svg"></span><span class="label">Lieferschein</span></a></div><?php
    }

    public static function Gutschrift()
    {
        ?><div class="holder"><a class="btn btn-icon"><span class="icon"><img src="/egroupware/cao/icons/gutschrift.svg"></span><span class="label">Gutschrift</span></a></div><?php
    }

    public static function StickyNav(array $items = [])
    {
        ?>
		<ul class="nav-sticky" style="margin-top: 30px;">
			<?php
                if (!empty($items)) {
                    foreach ($items as $item) {
                        $title = $item['title'];
                        $onclick = $item['onclick'];
                        $icon = $item['icon']; ?>
							<li class="nav-sticky__item settings" data-placement="left" data-toggle="tooltip" title="<?php echo $title?>">
								<a onclick="<?php echo $onclick?>"><i class="fa <?php echo $icon?>"></i></a>
							</li>
						<?php
                    }
                } else {
                    ?>
					<li class="nav-sticky__item settings" data-placement="left" data-toggle="tooltip" title="Einstellungen">
						<a onclick="Settings('Bill')"><i class="fa fa-cog"></i></a>
					</li>

					<li class="nav-sticky__item settings" data-placement="left" data-toggle="tooltip" title="Dateiimport">
						<a onclick="FileImport('Bill')"><i class="fa fa-upload"></i></a>
					</li>
					<?php
                } ?>
		</ul>
		<?php
    }
}
