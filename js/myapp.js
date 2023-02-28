var $ = jQuery;
var $firstButton = $(".first"),
	$secondButton = $(".second"),
	$categories = Array(),
	$ARTNUM = Array(),
	$SendSettings = {},
	$ctr = $(".container");

$firstButton.on("click", function(e){
	var checked1 = false;
	$("input.categories").each(function(){
		//console.log(this.checked);
		if (this.checked) {
			$categories.push({
				"id":   $(this).val(),
				"name": this.id
			}); 
			checked1=true; 
		}
	})
	if (checked1) {
		$(this).text("Speichern...");
		$.each($categories, function(){
			console.log(this);
			$(".artikel_cat").append('<div><span>'+this.name+':</span></div><input type="number" id="'+this.id+'" value="" placeholder="Artikelnummer"><input type="text" data-catid="'+this.id+'" value="" placeholder="Maßeinheit"><br />');
		});
		setTimeout(function() {
			$ctr.addClass("center slider-two-active").removeClass("full slider-one-active");
		}, 900);
	} else {
		var text = $(this).text(), elem = this;
		$(this).text("Whähle eine Kategorie aus...")
		setTimeout(function() {
			$(elem).text(text);
		}, 2000);
	}
	e.preventDefault();
});

$secondButton.on("click", function(e){
	var checked2 = true, art_num;
	$ARTNUM = Array();
	$('.artikel_cat input').each(function(){
		var value = $(this).val();
		if (value == 0 || !value) {
			checked2 = false;
		} else {
			if (this.id) {
				var me_einheit = $("input[data-catid="+this.id+"]").val();
				$ARTNUM.push({"art": value, "cat": this.id, "me_einheit": me_einheit});
			}
		}
	});
	if (checked2 == false) {
		var text = $(this).text(), elem = this;
		$(this).text("Fülle alle Felder aus...")
		setTimeout(function() {
			$(elem).text(text);
		}, 2000);
	} else {
		var check_artikel = Array(), elem = this;
		$.each($ARTNUM, function(){
			check_artikel.push("check_artikel[]="+this['art']);
		});
		$.ajax({
			url: "/egroupware/index.php?menuaction=cao.cao_ui.init&cd=no",
			method: "POST",
			data: "type=ajax&"+check_artikel.join("&"),
			success: function(res){
					var data = res, finish = true, cat;
					if (data && typeof data == "object") {
						$.each(data, function(key, value){
							$.each($ARTNUM, function(){
								if (this.art == key) {
									cat = this.cat;
								}
							});
							if (value == "Y") {
								$(".artikel_cat input#"+cat).css("border-color", "green");
							} else {
								finish = false;
								$(".artikel_cat input#"+cat).css("border-color", "red");
							}

						});
						if (finish) {
							$(elem).text("Speichern...").delay(900).queue(function(){
								$ctr.addClass("full slider-three-active").removeClass("center slider-two-active slider-one-active");
							});
						}
					}
			},
			error: function(){
				console.log('Something went wrong!');
			},
		});
	}
	e.preventDefault();
});

$(".finish").click(function(){
	$SendSettings['connection'] = $ARTNUM;
	var status_import = $( "#status_import option:selected" ).val();
	var status_finish = $( "#status_finish option:selected" ).val();

	if (!isNaN(parseInt(status_import)) && !isNaN(parseInt(status_finish))) {
		$(this).text("Speichern...");
		$SendSettings['status_settings'] = {
			"status_import": status_import,
			"status_finish": status_finish
		};
		$.ajax({
			url: "/egroupware/index.php?menuaction=cao.cao_ui.init&cd=no",
			method: "POST",
			data: "type=ajax&save=settings&data="+JSON.stringify($SendSettings),
			success: function(res){
					var data = res, finish = true;
					if (data && typeof data == "object") {
						console.log(data);
						if (data.connection && data.status_settings) {
							window.location.reload();
						}
					}
			},
			error: function(){
				console.log('Something went wrong!');
			}
		});
	} else {
		$(this).text("Whähle einen Status....");
	}
});


var CAO = class CAO {
	constructor($action){
		this.ArtScan();
	}

	ArtScan(){
		$.ajax({
			url: "/egroupware/index.php?menuaction=cao.cao_ui.init&cd=no",
			method: "POST",
			data: "type=Einkauf||Einkauf&action=ART_SCAN",
			success: function(data){
				CAO.ArtScan = data;
			},
			error: function(){
				console.log('Something went wrong!');
			},
		});
	}
}
new CAO("ART_SCAN");