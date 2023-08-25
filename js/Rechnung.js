$ = jQuery;

$( document ).ready(function() {
	$(function(){
		$(".notification.warning").click(function(){
			$(".dropdown-cheader").toggle();
		});
	});
	function GoShowTimesheet(ts_id){
		$("#t_"+ts_id).parent().parent().parent().find(".reply-list").show();
			$('html, body').animate({
		        scrollTop: $("#t_"+ts_id).offset().top
		    }, 2000);
	}
	
	$('.pos_toggle').click(function(e) {
		e.preventDefault();
		$(this).parent().parent().parent().parent().find(".reply-list").toggle('blind', {}, 500);
	});
	$('.btn').click(function(e) {
		e.preventDefault();
		var cao_conn = $(this).parent().parent().parent().find(".no_cao_connection"),
			action;
		var contact_id = $(this).parent().parent().parent().parent().parent()[0].id.replace("address_", "");
		console.log(contact_id);
		if (cao_conn.length) {
			action = {
				title: "Fehler!!",
				text: "Bitte verknüpfen Sie diese EGroupware-Adresse mit Cao!",
				icon: "warning",
				buttons: {
					cancel: "OK",
					confirm: false
				},
				showLoaderOnConfirm: true,
			};
			swal(action);
		} else {
			$.ajax({
				url: "/egroupware/index.php?menuaction=cao.cao_ui.init&cd=no",
				method: "POST",
				data: "type=ajax&check=address&contact_id=" + contact_id,
				success: function(res) {
					var data = res;
					var action = {
						title: "Sind Sie sicher?",
						text: "Wollen Sie eine neue Rechnung erstellen?",
						icon: "info",
						buttons: {
							cancel: "Nein",
							confirm: {
								text: "Ja",
								closeModal: false
							}
						},
						showLoaderOnConfirm: true,
					};
					if (data.bill) {
						if (data.bill == "exists") {
							delete action.title;
							action.text = "Es existiert bereits eine offene Rechnung des Kunden im CAO!";
							action.buttons.cancel = "Abbrechen";
							action.buttons.confirm = {
								text: "Hinzufügen",
								closeModal: false
							};
							action.buttons.new = {
								text: "Erstellen",
								closeModal: false
							};
							swal(action).then((exists) => {
								if (exists === true) {
									$.ajax({
										url: "/egroupware/index.php?menuaction=cao.cao_ui.init&cd=no",
										method: "POST",
										data: "type=ajax&create=bill&bill_type=exists&contact_id=" + contact_id,
										success: function(res) {
											var data = res;
											if (data.status == "done") {
												swal("Die Positionen wurden hinzugefügt!", {
													icon: "success",
													timer: 2000,
													button: false
												}).then(function() {
													window.location.reload();
												});
											} else {
												swal("Es ist ein Fehler aufgetretten!", {
													icon: "warning",
													timer: 2000,
													button: false
												}).then(function() {
													window.location.reload();
												});
											}
										},
										error: function() {
											console.log('Something went wrong!');
										},
									});
								} else if (exists == "new") {
									$.ajax({
										url: "/egroupware/index.php?menuaction=cao.cao_ui.init&cd=no",
										method: "POST",
										data: "type=ajax&create=bill&bill_type=new&contact_id=" + contact_id,
										success: function(res) {
											var data = res;
											if (data.status == "done") {
												swal("Die Rechnung wurde erfolgreich in CAO erstellt!", {
													icon: "success",
													timer: 2000,
													button: false
												}).then(function() {
													window.location.reload();
												});
											} else {
												swal("Es ist ein Fehler aufgetretten!", {
													icon: "warning",
													timer: 2000,
													button: false
												}).then(function() {
													window.location.reload();
												});
											}
										},
										error: function() {
											console.log('Something went wrong!');
										},
									});
								}
							});
						} else {
							swal(action).then((newBill) => {
								if (newBill) {
									$.ajax({
										url: "/egroupware/index.php?menuaction=cao.cao_ui.init&cd=no",
										method: "POST",
										data: "type=ajax&create=bill&bill_type=new&contact_id=" + contact_id,
										success: function(res) {
											var data = res;
											if (data.status == "done") {
												swal("Die Rechnung wurde erfolgreich in CAO erstellt!", {
													icon: "success",
													timer: 2000,
													button: false
												}).then(function() {
													window.location.reload();
												});
											} else {
												swal("Es ist ein Fehler aufgetretten!", {
													icon: "warning",
													timer: 2000,
													button: false
												}).then(function() {
													window.location.reload();
												});
											}
										},
										error: function() {
											console.log('Something went wrong!');
										},
									});
								}
							});
						}
					}
				},
				error: function() {
					console.log('Something went wrong!');
				},
			});
		}
	});
	$('.no_cao_connection').click(function(e) {
		e.preventDefault();
		var contact_id = $(this).parent().parent().parent().parent()[0].id.replace("address_", "");
		$("#modal_container").click(function(e) {
			e.stopPropagation();
			$(this).removeClass("show_modal");
		});
		$("#modal").click(function(e) {
			e.stopPropagation();
		});
		$.ajax({
			url: "/egroupware/index.php?menuaction=cao.cao_ui.init&cd=no",
			method: "POST",
			data: "type=ajax&save=conn_cao&contact_id=" + contact_id,
			success: function(res) {
				var data = res;
				if (data.html) {
					$("#modal").html(data.html);
					$("#modal_container").addClass("show_modal");
					$("#modal_container button.save_btn_turkis").click(function(e) {
						e.stopPropagation();
						$.ajax({
							url: "/egroupware/index.php?menuaction=cao.cao_ui.init&cd=no",
							method: "POST",
							data: "type=ajax&send=conn&data=" + $(this).attr("data-id"),
							success: function(res) {
								var data = res;
								if (data.status = "ok") {
									swal("Die Adresse wurde verknüpft!", {
										icon: "success",
										timer: 2000,
										button: false
									}).then(function() {
										window.location.reload();
									});
								}
							},
							error: function() {
								console.log('Something went wrong!');
							},
						});
					});
				}
			},
			error: function() {
				console.log('Something went wrong!');
			},
		});
	});
	
	function PopupCenter(url, title, w, h) {
		// Fixes dual-screen position                         Most browsers      Firefox
		var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
		var dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;
		var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
		var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;
		var left = ((width / 2) - (w / 2)) + dualScreenLeft;
		var top = ((height / 2) - (h / 2)) + dualScreenTop;
		var newWindow = window.open(url, title, 'scrollbars=yes, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
		// var win_timer = setInterval(function() {   
		//   if(newWindow.closed) {
		//       window.location.reload();
		//       clearInterval(win_timer);
		//   } 
		// }, 100);
		// Puts focus on the newWindow
		if (window.focus) {
			newWindow.focus();
		}
	}
	var cao_debug = egw.debug;
	var debug_cao_function = function() {
		cao_debug.apply(this, arguments);
		// Get the passed parameters and remove the first entry
		var args = [];
		for (var i = 1; i < arguments.length; i++) {
			args.push(arguments[i]);
		}
		if (args[0] == "Value") {
			console.log(args);
			if (typeof args[1].button.save != 'undefined') {
				window.location.reload();
			}
		}
	}
	egw.debug = debug_cao_function;
	$(window.parent.document).click(function() {
		if ($(window.parent.document).find("iframe[name*=egw_app_iframe_cao]").parent().parent().css("display") == "none") {
			egw.debug = cao_debug;
		} else {
			egw.debug = debug_cao_function;
		}
	});
	$(window.parent.document).find("iframe[name=egw_app_iframe_cao]").on("load", function() {
		var frame1ChildWindow = this.contentWindow;
		$(frame1ChildWindow).bind('unload', function() {
			egw.debug = cao_debug;
		});
	});
	$('.edit span.popup').click(function(e) {
		PopupCenter($(this).attr("data-url"), 'Ed', 700, 500);
	});
});


