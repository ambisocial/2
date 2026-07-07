var containerID     = tie_side_e3lan.is_boxed ? 'tie-wrapper' : 'side-stream-container',
    containerElem   = document.getElementById(containerID),
		objAdDivRight   = document.getElementById('side-stream-right'),
    objAdDivLeft    = document.getElementById('side-stream-left'),
		body            = document.querySelector('body'),
    html            = document.querySelector('html'),
    mainContentW    = containerElem.offsetWidth,
    sideMargin      = parseInt( tie_side_e3lan.side_margin),
    marginTop       = parseInt( tie_side_e3lan.margin_top),
    marginTopScroll = parseInt(tie_side_e3lan.margin_top_scroll),
		LeftBannerW     = parseInt( tie_side_e3lan.left_ad_width);


function FloatTopDiv() {
	startLX = ((document.body.clientWidth - mainContentW )/2) - (LeftBannerW+sideMargin),  startLY = marginTop; 
	startRX = ((document.body.clientWidth - mainContentW )/2) + (mainContentW+sideMargin), startRY = marginTop; 

	var d = document;
  var scrollTopDevvn = window.pageYOffset || d.documentElement.scrollTop || d.body.scrollTop || 0;

	function set_position( divID, xP, yP ){
		divID.style.left = xP + 'px';
		divID.style.top = yP + 'px';
	}

	if (scrollTopDevvn >= Math.abs(marginTop-marginTopScroll)){
		startLY = marginTopScroll;
		startRY = marginTopScroll;
		objAdDivLeft.style.position = 'fixed';
		objAdDivRight.style.position = 'fixed';
	}
	else {
		startLY = marginTop;
		startRY = marginTop;
		objAdDivLeft.style.position = 'absolute';
		objAdDivRight.style.position = 'absolute';
	};

	set_position( objAdDivLeft,  startLX, startLY );
	set_position( objAdDivRight, startRX, startRY );
} 


function ShowAdDiv(){ 
	objAdDivRight.style.display = objAdDivLeft.style.display = 'block'; 
	body.style.overflowX = html.style.overflowX = 'hidden';	
	FloatTopDiv(); 
}
ShowAdDiv();


window.addEventListener('resize', function () {
    FloatTopDiv();
});
window.addEventListener('scroll', function () {
    FloatTopDiv();
});



