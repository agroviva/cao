$ = jQuery;

// Create IE + others compatible event handler
var eventMethod = window.addEventListener ? "addEventListener" : "attachEvent";
var eventer = window[eventMethod];
var messageEvent = eventMethod == "attachEvent" ? "onmessage" : "message";

// Listen to message from child window
eventer(messageEvent,function(e) {
	if (e.data.dir_path) {
  		console.log('parent received message!:  ',e.data);
  		$.ajax({
			url: "/egroupware/index.php?menuaction=cao.cao_ui.init&cd=no",
			method: "POST",
			data: "type="+TYPE+"&action=SetDir&dir_path="+e.data.dir_path,
			success: function(res){
				var data = res;
				console.log(data);
				window.location.reload();
			},
			error: function(){
				console.log('Something went wrong!');
			},
		});
		$("#modal_container").removeClass("show_modal");
	}
},false);

function GoShowTimesheet(ts_id){
	$("#t_"+ts_id).parent().parent().parent().find(".reply-list").show();
	$('html, body').animate({
        scrollTop: $("#t_"+ts_id).offset().top
    }, 2000);
}


$(".notification.warning").click(function(){
	$(".dropdown-cheader").toggle();
});
$('.pos_toggle').click(function(e){
	e.preventDefault();
	$(this).parent().parent().parent().parent().find(".reply-list").toggle('blind', {}, 500 );
});
$('.holder .btn').click(function(e){
	e.preventDefault();
	var filename = $(this).parent().parent().parent().parent().parent()[0].id.replace("file_", "");
	console.log(filename);

	
  	new Settings("ART_SCAN");

    if (Settings.ArtScan == undefined || Settings.ArtScan.unconnected.length) {
    	swal({
    	  title: "Einige Artikel wurden im CAO nicht gefunden!",
    	  text: "Sie können fortsetzen, wenn alle Artikel verküpft sind!",
	      icon: "warning",
	      timer: 3000,
	      button: false
	    }).then(function(){
	    	var table = "";
	    	table += '<table class="table-fill">\n<thead>\n<tr>';
	    	table += '<th>KDNR_VOM_LIEFERANTEN</th>';
	    	table += '<th>ARTIKEL</th>';
	    	table += '</tr>\n</thead>';

	    	table += '<tbody class="table-hover">';

	    	for (var i = 0; i < Settings.ArtScan.unconnected.length; i++) {
	    		table += '<tr>';
	    		table += '<td>'+Settings.ArtScan.unconnected[i]+'</td>\n';
	    		table += '<td><ul class="input-list"><li class="input"><input type="text" placeholder="Suchen..." id="artikel" style="outline: none;"></li></ul><div id="'+Settings.ArtScan.unconnected[i]+'" class="artikel"></div></td>';
	    		table += '</tr>';

	    	}
	 
			table += '</tbody>\n</table>';

			$("#modal").html(table);
			$("#modal_container").addClass("show_modal");
			$("#modal_container").click(function(e){
				e.stopPropagation();
				$(this).removeClass("show_modal");
			});
			$("#modal").click(function(e){
				e.stopPropagation();
			});

			//setup before functions
			var typingTimer;                //timer identifier
			var doneTypingInterval = 1000;  //time in ms, 5 second for example
			var $input = $("input#artikel");

			//on keyup, start the countdown
			$input.keyup(function(){
			  clearTimeout(typingTimer);
			  typingTimer = setTimeout(doneTyping(this), doneTypingInterval);
			});

			//on keydown, clear the countdown 
			$input.keydown(function(){
			  clearTimeout(typingTimer);
			});

			//user is "finished typing," do something
			function doneTyping (elem) {
				var artikel = $(elem).parent().parent().parent().find(".artikel");
			  	var value = ($(elem).val()).toUpperCase();
			  	if (value.length < 3) {artikel.html("");return false;}

			  	var ArtikelName = "";
			  	artikel.html("");
			  	for (var i = 0; i < Settings.ArtScan.ARTIKEL.length; i++) {
			  		ArtikelName = (Settings.ArtScan.ARTIKEL[i]["LANGNAME"]+" "+Settings.ArtScan.ARTIKEL[i]["MATCHCODE"]).toUpperCase();
			  		if (ArtikelName.search(value) !== -1) {

			  			text = "<div id=\""+Settings.ArtScan.ARTIKEL[i]["REC_ID"]+"\" class=\"artikel\" onclick=\"addHerstArtNum(this)\"><p>"+Settings.ArtScan.ARTIKEL[i]["LANGNAME"]+"</p><span class=\"delete\">×</span></div>";
						if (i == 0) {
							artikel.html(text);
						} else {
							artikel.append(text);
						}
			  		}
			  	}
			}
	    });
    	return false;
    }

	var action = {
		title: "Sind Sie sicher?",
		text: ASK,
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

	swal(action).then((Sucsedded) => {
	  if (Sucsedded) {
	  	var source = new EventSource("/egroupware/index.php?menuaction=cao.cao_ui.init&cd=no&type=ajax&create="+NAME+"&filename="+filename);
	  	NProgress.start();
		source.onmessage = function(event) {
			try {
				var data = JSON.parse(event.data);
			} catch(e){
				source.close();
				throw Error(e);
			}

			if (data.status == "done") {
				source.close();
				NProgress.done();
					NProgress.remove();
				swal(SUCCESS, {
			      icon: "success",
			      timer: 2000,
			      button: false
			    }).then(function(){
				  window.location.reload();
				});
			} else if(data.status == "onprogress"){
				NProgress.set(data.progress / 100);
			} else {
				swal("Es ist ein Fehler aufgetretten!", {
			      icon: "warning",
			      timer: 2000,
			      button: false
			    }).then(function(){
				  window.location.reload();
				});
			}
		};
	  	
	}});
});	

function addHerstArtNum(elem){
	var HERST_ARTNUM = $(elem).parent()[0].id;
	$.ajax({
		url: "/egroupware/index.php?menuaction=cao.cao_ui.init&cd=no",
		method: "POST",
		data: "type=ajax&add=HERST_ARTNUM&HERST_ARTNUM="+HERST_ARTNUM+"&REC_ID="+elem.id,
		success: function(data){
			$(elem).toggleClass("active");
		},
		error: function(){
			console.log('Something went wrong!');
		},
	});
}

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
var debug_cao_function = function(){
	cao_debug.apply(this, arguments);
	// Get the passed parameters and remove the first entry
	var args = [];
	for (var i = 1; i < arguments.length; i++)
	{
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
$(window.parent.document).click(function(){
	if ($(window.parent.document).find("iframe[name*=egw_app_iframe_cao]").parent().parent().css("display") == "none"){
		egw.debug = cao_debug;
	} else {
		egw.debug = debug_cao_function;
	}
});
$(window.parent.document).find("iframe[name=egw_app_iframe_cao]").on("load", function() {
	var frame1ChildWindow = this.contentWindow;
    $( frame1ChildWindow ).bind('unload', function() {
  		egw.debug = cao_debug;
    });
});
$('.edit span.popup').click(function(e){
	PopupCenter($(this).attr("data-url"), 'Ed', 700, 500);
});