$ = jQuery;
var Settings = class Settings {
	constructor($action){
		if ($action == "ART_SCAN") {
			this.ArtScan();
			return false;	
		} else if($action == "Bill"){
			this.Bill($action);
		}
	}

	Bill($action){
		$("#modal_container").click(function(e){
			e.stopPropagation();
			$(this).removeClass("show_modal");
		});
		$("#modal").click(function(e){
			e.stopPropagation();
		});
		$.ajax({
			url: "/egroupware/index.php?menuaction=cao.cao_ui.init&cd=no",
			method: "POST",
			data: "type=settings&action=Bill",
			success: function(res){
				var data = res;
				if (data.html) {
					$("#modal").html(data.html);
					$("#modal_container").addClass("show_modal");	
				}
			},
			error: function(){
				console.log('Something went wrong!');
			},
		});
	}

	ArtScan($action){
		NProgress.start();
		$.ajax({
			url: "/egroupware/cao/controller/",
			method: "POST",
			data: "config=ArtikelController@Scan&args=EINKAUF",
			success: function(data){
				Settings.ArtScan = data;
				NProgress.done();
			},
			error: function(){
				console.log('Something went wrong!');
				NProgress.done();
			},
		});
		NProgress.done();
	}

	get EKBestellung(){
		return false;
	}

	get Rechnung(){
		return false;
	}

	get Artikel(){
		return false;
	}

	get Adressen(){
		return false;
	}

	get All(){
		return true;
	}
}
new Settings("ART_SCAN");