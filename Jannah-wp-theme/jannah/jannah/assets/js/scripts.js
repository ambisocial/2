/* jQuery.flexMenu 1.5.1 https://github.com/352Media/flexMenu */
!function(e){"function"==typeof define&&define.amd?define(["jquery"],e):e(jQuery)}(function(M){var e,n=window.innerWidth,i=[];M(window).resize(function(){clearTimeout(e),e=setTimeout(function(){var e=window.innerWidth;e!==n&&(n=e,M(i).each(function(){M(this).flexMenu({undo:!0}).flexMenu(this.options)}))},200)}),M.fn.flexMenu=function(e){var n,c=M.extend({threshold:2,cutoff:2,linkText:"More",linkTitle:"View More",linkTextAll:"Menu",linkTitleAll:"Open/Close Menu",shouldApply:function(){return!0},showOnHover:!0,popupAbsolute:!0,popupClass:"",undo:!1},e);return this.options=c,0<=(n=M.inArray(this,i))?i.splice(n,1):i.push(this),this.each(function(){var e=M(this),n=e.find("> li"),i=n.length;if(i){var l,o,t,u,f,p,r=s(n)+20,d=!1;function s(e){return e[0].offsetTop}function a(e){return s(e)>=r}if(a(n.last())&&i>c.threshold&&!c.undo&&c.shouldApply()){var h=M('<ul class="flexMenu-popup" style="display:none;'+(c.popupAbsolute?" position: absolute;":"")+'"></ul>');for(c.popupClass&&h.addClass(c.popupClass),p=i;1<p;p--){if(o=a(l=e.find("li:last-child")),p-1<=c.cutoff){M(e.children().get().reverse()).appendTo(h),d=!0;break}if(!o)break;l.appendTo(h)}d?e.append('<li class="flexMenu-viewMore flexMenu-allInPopup"><a href="#" title="'+c.linkTitleAll+'">'+c.linkTextAll+"</a></li>"):e.append('<li class="flexMenu-viewMore"><a href="#" title="'+c.linkTitle+'">'+c.linkText+"</a></li>"),a(t=e.find("> li.flexMenu-viewMore"))&&e.find("> li:nth-last-child(2)").appendTo(h),h.children().each(function(e,n){h.prepend(n)}),t.append(h),e.find("> li.flexMenu-viewMore > a").click(function(e){var n;n=t,M("li.flexMenu-viewMore.active").not(n).find("> ul").hide(),h.toggle(),e.preventDefault()}),c.showOnHover&&"undefined"!=typeof Modernizr&&!Modernizr.touch&&t.hover(function(){h.show()},function(){h.hide()})}else if(c.undo&&e.find("ul.flexMenu-popup")){for(u=(f=e.find("ul.flexMenu-popup")).find("li").length,p=1;p<=u;p++)f.find("> li:first-child").appendTo(e);f.remove(),e.find("> li.flexMenu-viewMore").remove()}}})}});

/* LazyLoad */
!function(e,t){"function"==typeof define&&define.amd?define(function(){return t(e)}):"object"==typeof exports?module.exports=t:e.emergence=t(e)}(this,function(e){"use strict";var t,n,i,o,r,a,l,s={},d=function(){},c=function(){return/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|playbook|silk/i.test(navigator.userAgent)},u=function(e){var t=e.offsetWidth,n=e.offsetHeight,i=0,o=0;do{isNaN(e.offsetTop)||(i+=e.offsetTop),isNaN(e.offsetLeft)||(o+=e.offsetLeft)}while(null!==(e=e.offsetParent));return{width:t,height:n,top:i,left:o}},f=function(e){if(function(e){return null===e.offsetParent}(e))return!1;var t,i,o,s=u(e),d=function(e){var t,n;return e!==window?(t=e.clientWidth,n=e.clientHeight):(t=window.innerWidth||document.documentElement.clientWidth,n=window.innerHeight||document.documentElement.clientHeight),{width:t,height:n}}(n),c=function(e){return e!==window?{x:e.scrollLeft+u(e).left,y:e.scrollTop+u(e).top}:{x:window.pageXOffset||document.documentElement.scrollLeft,y:window.pageYOffset||document.documentElement.scrollTop}}(n),f=s.width,g=s.height,m=s.top;s.left;return t=m+g*r,i=m+g-g*r,o=c.y+a,t<c.y-l+d.height&&i>o},g=function(){t||(clearTimeout(t),t=setTimeout(function(){s.engage(),t=null},i))};return s.init=function(e){var t,s,u=function(e,t){return parseInt(e||t,10)};n=(e=e||{}).container||window,o=void 0===e.handheld||e.handheld,i=u(e.throttle,250),t=e.elemCushion,s=.1,r=parseFloat(t||s),a=u(e.offsetTop,0),u(e.offsetRight,0),l=u(e.offsetBottom,0),u(e.offsetLeft,0),d=e.callback||d,"querySelectorAll"in document?(c()&&o||!c())&&(window.addEventListener?(window.addEventListener("load",g,!1),n.addEventListener("scroll",g,!1),n.addEventListener("resize",g,!1)):(document.attachEvent("onreadystatechange",function(){"complete"===document.readyState&&g()}),n.attachEvent("onscroll",g),n.attachEvent("onresize",g))):console.log("Emergence.js is not supported in this browser.")},s.engage=function(){if(tie.lazyload){if("loading"in HTMLImageElement.prototype);else for(var e=(c=document.querySelectorAll("[data-src]")).length,t=0;t<e;t++)u=c[t],f(u)&&(u.setAttribute("src",u.getAttribute("data-src")),u.removeAttribute("data-src"),d(u,"tie_img_visible"),jQuery.fn.masonry&&jQuery("#masonry-grid").masonry("layout"));var n=(c=document.querySelectorAll("[data-lazy-bg]")).length;for(t=0;t<n;t++)if(u=c[t],f(u)){var i="";null!==u.getAttribute("style")&&(i=u.getAttribute("style")),u.setAttribute("style",i+"background-image:url("+u.getAttribute("data-lazy-bg")+")"),u.removeAttribute("data-lazy-bg"),d(u,"tie_bg_visible")}}if(tie.is_taqyeem_active){var o=(c=document.querySelectorAll("[data-lazy-pie]")).length;for(t=0;t<o;t++)if(u=c[t],f(u)){var r=parseInt(u.getAttribute("data-pct")),a=u.getElementsByClassName("circle_bar")[0],l=(100-r)/100*Math.PI*38;a.setAttribute("style","stroke-dashoffset:"+l+"px"),u.removeAttribute("data-lazy-pie"),d(u,"tie_pie_visible")}var c,u,g=(c=document.querySelectorAll("[data-lazy-percent]")).length;for(t=0;t<g;t++)u=c[t],f(u)&&(u.setAttribute("style","width:"+u.getAttribute("data-rate-val")),u.removeAttribute("data-lazy-percent"),d(u,"tie_rate_visible"))}e||o||g||n||s.disengage()},s.disengage=function(){window.removeEventListener?(n.removeEventListener("scroll",g,!1),n.removeEventListener("resize",g,!1)):(n.detachEvent("onscroll",g),n.detachEvent("onresize",g)),clearTimeout(t)},s});

/**
 * Declaring and initializing global variables
 */
var $doc           = jQuery(document),
		$window        = jQuery(window),
		$html          = jQuery('html'),
		$body          = jQuery('body'),
		$themeHeader   = jQuery('#theme-header'),
		$mainNav       = jQuery('#main-nav'),
		$container     = jQuery('#tie-container'),
		is_RTL         = tie.is_rtl ? true : false,
		intialWidth    = window.innerWidth,
		isDuringAjax   = false,
		scrollBarWidth = false,
		mobileMenu     = false;

/**
 * Magazine box filters flexmenu
 */
function tieFlexMenu(){

	var $flexmenu_elements = jQuery('.is-flex-tabs, .is-flex-tabs-shortcodes .tabs');
	if( $flexmenu_elements.length ){
		$flexmenu_elements.flexMenu({
			threshold   : 0,
			cutoff      : 0,
			linkText    : '<span class="tie-icon-dots-three-horizontal"><span class="screen-reader-text">More</span></span>',
			linkTextAll : '<span class="tie-icon-dots-three-horizontal"><span class="screen-reader-text">More</span></span>',
			linkTitle   : '',
			linkTitleAll: '',
			showOnHover : ( intialWidth > 991 ? true : false )
		});

		$flexmenu_elements.css({'opacity':1});
	}
}


function tieLazyLoad(){
	emergence.init();
}
tieLazyLoad();


/**
 * Tabs
 */
function tieTabs(){
	document.querySelectorAll('.tabs-wrapper').forEach( function(tabsWrapper){

		// Add active for the first tab
		var tabsHead = tabsWrapper.querySelector('.tabs');

		if( typeof tabsHead !== 'undefined' && tabsHead != null ){

			if( tabsWrapper.classList.contains('tabs-vertical') ){
				//--
			}

			var first = false;
			tabsHead.querySelectorAll('li').forEach( function(tabTitle){

				if( ! first ){
					tabTitle.classList.add('active');
					first = true;
				}

				tabTitle.addEventListener( 'click', function(e){

					if( ! tabTitle.classList.contains('active') ){

						tabsHead.querySelector('.active').classList.remove('active');
						tabTitle.classList.add('active');

						// Hide All tabs
						tabsWrapper.querySelectorAll('.tab-content').forEach( function(tabContent){
							tabContent.style.display = 'none';
							tabContent.classList.add('is-not-active');
						});

						var currentTab = tabTitle.querySelector('a').getAttribute('href'),
						    activeTab  = tabsWrapper.querySelector( currentTab );

						activeTab.style.display = 'block';

						var tabContentWrap = activeTab.querySelector('.tab-content-wrap');

						if( tabContentWrap ){
							tabContentWrap.classList.add('tie-animate-slideInUp')
						}

						activeTab.querySelectorAll('.tab-content-elements li').forEach( function(tabContent){
							if( tabContent ){
								tabContent.classList.add('tie-animate-slideInUp' );
							}
						});

						tie_animate_element( activeTab );
					}

					e.preventDefault();
				});
			});
		} // tabsHead check

	});
}
tieTabs();


/*
 * General Scripts
 */
$doc.ready(function(){

	'use strict';

	// Debugging
	performance.mark('TieStart');

	// We load this in the .ready to make sure that the desktop.js (contains modernizr) file is loaded
	tieFlexMenu();

	/**
	 * Ad Blocker
	 */	
	if( tie.ad_blocker_detector ){

		if ( jQuery('.Ad-Container').length > 0 && jQuery('.Ad-Container').outerWidth() === 0 ) {		
			
			if( ! $body.hasClass('is-ajax-loaded-post') && jQuery('#tie-popup-adblock').length > 0 ){
				if( tie.ad_blocker_detector_delay ){
					setTimeout(function() {
						tieBtnOpen('#tie-popup-adblock');
					}, tie.ad_blocker_detector_delay );
				}
				else{
					tieBtnOpen('#tie-popup-adblock');
				}
			}
		}
	}

	// Replace All images 
	adBlockBlock();


	/**
	 * Logged-in user icon
	 */
	$doc.on('click', '.profile-btn', function(){
		return false;
	});


	/**
	 * Light/Dark Mode
	 */
	$doc.on('click', '.change-skin', function(){

		jQuery('#autocomplete-suggestions').hide();

		var defaultSkin = $html.data('skin'),
		    siteSkin    = 'light',
		    mnIsDark    = false,
		    tnIsDark    = false,
		    iframeAddClass    = '',
		    iframeRemoveClass = '';

		if( $html.hasClass( 'dark-skin' ) ){
			siteSkin = 'dark';
		}

    var switchTo = ( siteSkin == 'dark' ) ? 'light' : 'dark';

		if( $themeHeader.hasClass( 'main-nav-default-dark' ) ){
			mnIsDark = true;
		}

		if( $themeHeader.hasClass( 'top-nav-default-dark' ) ){
			tnIsDark = true;
		}

		if( 'undefined' != typeof localStorage ){
			localStorage.setItem( 'tie-skin', switchTo );
		}

		if( defaultSkin == switchTo ){
			$html.removeClass('tie-skin-inverted');
			iframeRemoveClass += ' tie-skin-inverted';
		}
		else{
			$html.addClass('tie-skin-inverted');
			iframeAddClass += ' tie-skin-inverted';

			//var invertedLogo = document.getElementById('tie-logo-inverted-img');
			//if( invertedLogo ){
				//invertedLogo.setAttribute('src', invertedLogo.getAttribute('data-src') );
				//invertedLogo = document.getElementById('tie-logo-inverted-source');
				//invertedLogo.setAttribute('srcset', invertedLogo.getAttribute('data-srcset'));
			//}
		}

		if( switchTo == 'dark' ){
			$html.addClass( 'dark-skin' );
			iframeAddClass += ' dark-skin';

			$themeHeader.addClass( 'main-nav-dark top-nav-dark' );
			$themeHeader.removeClass( 'main-nav-light top-nav-light' );
		}
		else{
			$html.removeClass( 'dark-skin' );
			iframeRemoveClass += ' dark-skin';

			if( ! mnIsDark ){
				$themeHeader.removeClass( 'main-nav-dark' );
				$themeHeader.addClass( 'main-nav-light' );
			}

			if( ! tnIsDark ){
				$themeHeader.removeClass( 'top-nav-dark' );
				$themeHeader.addClass( 'top-nav-light' );
			}
		}

		// Send Message to iFrames
		if( tie.autoload_posts ){
			jQuery('.tie-infinte-post-iframe').each(function(){
				var iFrame = jQuery(this);
				var wn = iFrame[0].contentWindow;
				wn.postMessage({ "removeClass": iframeRemoveClass, "addClass": iframeAddClass });
			});
		}

		return false;
	});


	/**
	 * Masonry
	 */
	if( jQuery.fn.masonry ){

		var $grid = jQuery('#masonry-grid');

		if( $grid.length ){

			var onloadsWrap = jQuery('#media-page-layout');

			$grid.masonry({
				columnWidth     : '.grid-sizer',
				gutter          : '.gutter-sizer',
				itemSelector    : '.post-element',
				percentPosition : true,
				isInitLayout    : false, // v3
				initLayout      : false, // v4
				originLeft      : ! is_RTL,
				isOriginLeft    : ! is_RTL
			}).addClass( 'masonry-loaded' );

			// Run after masonry complete
			$grid.masonry( 'on', 'layoutComplete', function(){
				isDuringAjax = false;
			});

			// Run the masonry
			$grid.masonry();

			// Load images and re fire masonry
			if( jQuery.fn.imagesLoaded ){
				$grid.imagesLoaded().progress( function(){
					$grid.masonry('layout');
				});
			}

			onloadsWrap.find('.loader-overlay').fadeOut().remove();

			var $i = 0;
			onloadsWrap.find('.post-element').each(function(){
				$i++;
				var $element = jQuery(this);
				$element.addClass( 'tie-animate-slideInUp' );
				$element.attr('style', $element.attr('style') + '; animation-delay:'+ $i/10 +'s' )
			});

			jQuery(window).resize(function () {
				onloadsWrap.find('.post-element').removeClass( 'tie-animate-slideInUp' );
			});

		}
	}


	/**
	 * Mobile Sticky Nav
	 */
	function tieSmartSticky(e) {

		var intialWidth = window.innerWidth;

		// Just for Tablet & Mobile
		if ( intialWidth > 991) {
			return;
		}

		var scrollInFirst          = true,
				scrollInterval         = 0,
				scrollPrevious         = 0,
				scrollDirection        = 0,
				loadStickyOffset       = 0,
				loadAdminBar           = false,
				nav_sticky_up          = ( tie.sticky_mobile_behavior == 'upwards') ? true : false,
				nav_sticky_offset_type = 'auto',
				nav_sticky_offset      = 0,
				nav_sticky_offset_full = 0;

		// Determine the Sticky Nav selector based on header layout.
		var $stickyNav = $themeHeader.hasClass('header-layout-1') ? $mainNav : jQuery('.logo-container');

		if( $stickyNav.length ){

			if (nav_sticky_offset_type !== 'size') {

				var calcbar = 0,
						wpadminbar = 0;

				// Check if the admin bar is active
				if( $body.hasClass('admin-bar') ){
					var $wpadminbarElem = jQuery('#wpadminbar');

					if ( $wpadminbarElem.length > 0) {
						calcbar = $wpadminbarElem.outerHeight();
						wpadminbar = calcbar;

						if ('resize' !== e.type) {
							loadAdminBar = wpadminbar;
						}

						if ( 'absolute' === $wpadminbarElem.css('position')) {
							wpadminbar = 0;
							if ('resize' !== e.type) {
								loadAdminBar = 0;
							}
						}
					}
				}


				var $stickyNavWrap = $stickyNav.parent();
				var elOffset = $stickyNav.not('.fixed-nav').offset();

				if( elOffset ){
					nav_sticky_offset_full = $stickyNavWrap.outerHeight() + elOffset.top;

					if (elOffset && !$stickyNav.hasClass('.fixed-nav')) {
						nav_sticky_offset = elOffset.top;
						loadStickyOffset  = elOffset.top;
					} else {
						nav_sticky_offset = loadStickyOffset;
					}


					if (32 === loadAdminBar) {
						if (46 === calcbar) {
							nav_sticky_offset = nav_sticky_offset - wpadminbar + 14;
						} else {
							nav_sticky_offset = nav_sticky_offset - wpadminbar;
						}
					}
					else if (46 === loadAdminBar || 0 === loadAdminBar) {
						if (32 === calcbar) {
								nav_sticky_offset = nav_sticky_offset - wpadminbar - 14;
						} else {
								nav_sticky_offset = nav_sticky_offset - wpadminbar;
						}
					}
				}
			} // Size


			var navHeight = jQuery($stickyNav).outerHeight();
			$stickyNavWrap.data('min-height', nav_sticky_offset_full - navHeight);
			$stickyNavWrap.height(navHeight);

			if ('resize' !== e.type) {

				if (nav_sticky_up) {
					$stickyNavWrap.addClass('sticky-type-slide');
				}

				jQuery(window).scroll(function (e) {
					if (e.originalEvent) {

						var scrollCurrent = jQuery(window).scrollTop();

						if (nav_sticky_up) {
							if (scrollCurrent > nav_sticky_offset_full) {
								$stickyNav.addClass('fixed-nav');
							}
							if (scrollCurrent <= nav_sticky_offset) {
								$stickyNav.removeClass('fixed-nav');
							}
							if (scrollCurrent > scrollPrevious) {
								scrollInterval = 0;
								scrollDirection = 'down';
								$stickyNav.addClass('sticky-down').removeClass('sticky-up');
							} else {
								scrollInterval += scrollPrevious - scrollCurrent;
								scrollDirection = 'up';
								$stickyNav.addClass('sticky-up').removeClass('sticky-down');
							}
							if (scrollInterval > 150 && 'up' === scrollDirection) {
								$stickyNav.addClass('sticky-nav-slide-visible');
								$doc.trigger('sticky-nav-visible');
							} else {
								$stickyNav.removeClass('sticky-nav-slide-visible');
								$doc.trigger('sticky-nav-hide');
							}
							if (scrollCurrent > nav_sticky_offset_full + 150) {
								$stickyNav.addClass('sticky-nav-slide');
							} else {
								$stickyNav.removeClass('sticky-nav-slide');
							}
							if (scrollInFirst && scrollCurrent > nav_sticky_offset_full + 150) {
									$stickyNav.addClass('sticky-nav-slide sticky-nav-slide-visible sticky-up');
									$doc.trigger('sticky-nav-visible');
									scrollInterval = 151;
									scrollDirection = 'up';
									scrollInFirst = false;
							}
						} else {

							if (scrollCurrent > nav_sticky_offset) {
								$stickyNav.addClass('fixed-nav default-behavior-mode');
								$doc.trigger('sticky-nav-visible');
							} else {
								$stickyNav.removeClass('fixed-nav');
								$doc.trigger('sticky-nav-hide');
							}
						}
						scrollPrevious = scrollCurrent;
					}
				});
			} // resize

		} //$stickyNav.length
	}


	if( tie.sticky_mobile ){
		$window.on( 'load', function(e) { tieSmartSticky(e); });
		$window.resize( tieSmartSticky );
	}


	/**
	 * Popup Module
	 */
	var $tiePopup = jQuery('.tie-popup' );

	$doc.on( 'click', '.tie-popup-trigger', function (event){
		event.preventDefault();
		tieBtnOpen('#tie-popup-login');
	});

	// Type To Search
	// Make sure we are not in an iframe to avoid isues with front-end builders such as Elementor
	if ( tie.type_to_search && window.self === window.top ){
		if ( jQuery('#tie-popup-search-desktop').length && ! jQuery('.ql-editor').length ){
			$doc.bind('keydown', function(e){
				if( ! jQuery( '.ql-editor' ).is( ':focus' ) && ! jQuery( 'input, textarea' ).is( ':focus' ) && ! jQuery( '#tie-popup-login' ).is( ':visible' ) && ! e.ctrlKey && ! e.metaKey && e.keyCode >= 65 && e.keyCode <= 90 ){
					$container.removeClass('side-aside-open');
					tieBtnOpen('#tie-popup-search-desktop');
				}
			});
		}
	}

	jQuery('.tie-search-trigger').on( 'click', function (){
		tieBtnOpen('#tie-popup-search-desktop');
		return false;
	});

	jQuery('.tie-search-trigger-mobile').on( 'click', function (){
		tieBtnOpen('#tie-popup-search-mobile');
		return false;
	});

	function tieBtnOpen(windowToOpen){

		jQuery(windowToOpen).show();

		if( windowToOpen == '#tie-popup-search-desktop' || windowToOpen == '#tie-popup-search-mobile' ){
			$tiePopup.find('form input[type="text"]').focus();
		}

		tie_animate_element( jQuery(windowToOpen) );

		if( ! scrollBarWidth ){
			scrollBarWidth = ( 100 - document.getElementById('is-scroller').offsetWidth );
		}

		setTimeout(function(){ $body.addClass('tie-popup-is-opend'); },10);
		jQuery('html').css({'marginRight': scrollBarWidth, 'overflow': 'hidden'});
	}

	// Close popup when clicking the esc keyboard button
	if ( $tiePopup.length ){
		$doc.keyup(function(event){
			if ( event.which == '27' && $body.hasClass('tie-popup-is-opend') && ! jQuery('#tie-popup-adblock').is(':visible') ){
				tie_close_popup();
			}
		});
	}

	// Close Popup when click on the background
	$tiePopup.on('click', function(event){
		if( jQuery( event.target ).is('.tie-popup:not(.is-fixed-popup)') ){
			tie_close_popup();
			return false;
		}
	});

	// Close Popup when click on the close button
	jQuery('.tie-btn-close').on( 'click', function (){
		tie_close_popup( jQuery(this) );
		return false;
	});

	// Popup close function
	function tie_close_popup( elem ){

		if( 'undefined' !== typeof elem ){
			if( elem.data('show-once') ){
				document.cookie = "AdBlockerDismissed=true; expires=Thu, 18 Dec 2100 12:00:00 UTC";
			}
		}

		jQuery.when($tiePopup.fadeOut(500)).done(function(){
			jQuery('html').removeAttr('style');
		});

		jQuery.when(jQuery('#autocomplete-suggestions').fadeOut(50)).done(function(){
			jQuery(this).html('');
		});

		$body.removeClass('tie-popup-is-opend');
		jQuery('.tie-popup-search-input').val('');
	}


	/**
	 * Slideout Sidebar
	 */
	// Reset the menu
	var resetMenu = function(){
		$container.removeClass('side-aside-open');
		$container.off( 'touchstart click', bodyClickFn );
	},

	//
	bodyClickFn = function(evt){
		if( ! $container.hasClass('side-aside-open') ){
			return false;
		}

		if( ! jQuery(evt.target).parents('.side-aside').length ){
			resetMenu();
		}
	},

	// Click on the Menu Button
	el = jQuery('.side-aside-nav-icon, #mobile-menu-icon');
	el.on( 'touchstart click', function(ev){
		
		if( tie.is_side_aside_light ){
			if ( jQuery(this).hasClass('side-aside-nav-icon') ){
				jQuery('.side-aside' ).removeClass( 'dark-skin dark-widgetized-area' );
			}
			else{
				jQuery('.side-aside' ).addClass( 'dark-skin dark-widgetized-area' );
			}
		}

		// Create the Mobile menu
		create_mobile_menu();

		// ----
		$container.addClass('side-aside-open');
		$container.on( 'touchstart click', bodyClickFn );

		jQuery('#autocomplete-suggestions').hide();

		// Replace for mCustomScrollbar
		tie_animate_element( jQuery('.side-aside' ) );

		/*
		if( tie.lazyload ){
			jQuery('.side-aside .lazy-img').lazy({
				bind: 'event'
			});
		}
		*/

		return false;
	});


	// ESC Button close
	$doc.on('keydown', function(e){
		if( e.which == 27 ){
			resetMenu();
		}
	});

	// close when click on close button inside the sidebar
	jQuery('.close-side-aside').on('click',function(e){
		resetMenu();
		return false;
	});


/*
	// close the aside on resize when reaches the breakpoint
	$window.resize(function() {

		var resizeWidth;

		if( intialWidth > 991 ){
			resizeWidth = window.innerWidth;
			if( resizeWidth <= 1100 ){
				intialWidth = resizeWidth;
				resetMenu();
			}
		}
		else{
			resizeWidth = window.innerWidth;
			if( resizeWidth >= 900 ){
				intialWidth = resizeWidth;
				resetMenu();
			}
		}
	});
	*/

		//setTimeout(function(){
			//var g = jQuery('.tdb-infinte-post-iframe').contents().find( "html" ).attr('prefix');
			//alert( g );

		 //},200);

	/**
	 * Scroll To #
	 */
	jQuery('a[href^="#go-to-"]').on('click', function(){

		var hrefId   = jQuery(this).attr('href'),
				target   = '#'+hrefId.slice(7),
				offset   = tie.sticky_desktop ? 100 : 40,
				position = jQuery(target).offset().top - offset;

		jQuery('html, body').animate({ scrollTop: position }, 'slow');

		return false;
	});


	/**
	 * Go to top button
	 */
	 function tieGoToTop(){

		var $topButton   = jQuery('#go-to-top'),
				scrollTimer  = false;

		$window.scroll(function(){

			if( $topButton.length ){

				if( scrollTimer ){
					window.clearTimeout( scrollTimer );
				}

				scrollTimer = window.setTimeout(function(){
					if ( $window.scrollTop() > 100 ){
						$topButton.addClass('show-top-button');
					}
					else {
						$topButton.removeClass('show-top-button');
					}
				}, 100 );
			}
		});

	}
	tieGoToTop();


	/**
	 * User Weather Ajax request
	 */
	$doc.on( 'click', '.tie-weather-user-location', function(){

		var thisButton = jQuery(this);

		// Return if the current request still ctive
		if( thisButton.hasClass('is-loading') ){
			return;
		}

		var theBlock = thisButton.closest('.tie-weather-widget'),
				options  = thisButton.attr('data-options');

		// Ajax Call
		jQuery.ajax({
			url : tie.ajaxurl,
			type: 'post',
			data: {
				action  : 'tie_get_user_weather',
				options : options,
			},
			beforeSend: function(){
				thisButton.addClass('is-loading').find('span').attr( 'class', 'tie-icon-spinner');
			},
			success: function( data ){

				if( jQuery(data).attr('class') == 'theme-notice' ){

					theBlock.append( '<div class="user-weather-error">'+ data + '</div>' );
					theBlock.find('.user-weather-error').delay(3000).fadeOut();
				}
				else{
					theBlock.find('.weather-wrap').remove();
					theBlock.append(data);
				}

				thisButton.removeClass('is-loading').find('span').attr( 'class', 'tie-icon-gps');
			}
		});

		return false;
	});


	/**
	 * Blocks Ajax Pagination
	 */
	$doc.on( 'click', '.block-pagination', function(){

		var pagiButton   = jQuery(this),
				theBlock     = pagiButton.closest('.mag-box'),
				theBlockID   = theBlock.get(0).id,
				theSection   = theBlock.closest('.section-item'),
				theTermID    = theBlock.attr('data-term'),
				currentPage  = theBlock.attr('data-current'),
				theBlockList = theBlock.find('.posts-list-container'),
				theBlockDiv  = theBlock.find('.mag-box-container'),
				options      = jQuery.extend( {}, window[ 'js_'+theBlockID.replace( 'tie-', 'tie_' ) ] ),
				theListClass = 'posts-items',
				isLoadMore   = false,
				sectionWidth = 'single';

		if( currentPage && options ){
			if( theTermID ){
				if( options[ 'tags' ] ){
					options[ 'tags' ] = theTermID;
				}
				else{
					options[ 'id' ] = theTermID;
				}
			}

			// Custom Block List Class
			if( options[ 'ajax_class' ] ){
				theListClass = options[ 'ajax_class' ];
			}

			// Check if the Button Disabled
			if( pagiButton.hasClass( 'pagination-disabled' ) ){
				return false;
			}

			// Check if the button type is Load More
			if( pagiButton.hasClass( 'load-more-button' ) ){
				currentPage++;
				isLoadMore = true;
			}

			// Next page button
			else if( pagiButton.hasClass( 'next-posts' ) ){
				currentPage++;
				theBlock.find( '.prev-posts' ).removeClass( 'pagination-disabled' );
			}

			// Prev page button
			else if( pagiButton.hasClass( 'prev-posts' ) ){
				currentPage--;
				theBlock.find( '.next-posts' ).removeClass( 'pagination-disabled' );
			}

			// Full Width Section
			if( theSection.hasClass( 'full-width' ) ){
				sectionWidth = 'full';
			}

			// Ajax Call
			jQuery.ajax({
				url : tie.ajaxurl,
				type: 'post',
				data: {
					action : 'tie_blocks_load_more',
					block  : options,
					page   : currentPage,
					width  : sectionWidth
				},
				beforeSend: function(){

					// Load More button----------
					if( isLoadMore ){
						pagiButton.html( tie.ajax_loader );
					}
					// Other pagination Types
					else{
						var blockHeight = theBlockDiv.height();
						theBlockDiv.append( tie.ajax_loader ).attr( 'style', 'min-height:' +blockHeight+ 'px' );
						theBlockList.addClass('is-loading');
					}
				},
				success: function( data ){

					data = jQuery.parseJSON(data);

					// Hide next posts button
					if( data['hide_next'] ){
						theBlock.find( '.next-posts').addClass( 'pagination-disabled' );
						if( pagiButton.hasClass( 'show-more-button' ) || isLoadMore ){
							pagiButton.html( data['button'] );
						}
					}
					else if( isLoadMore ){
						pagiButton.html( pagiButton.attr('data-text') );
					}

					// Hide Prev posts button
					if( data[ 'hide_prev' ] ){
						theBlock.find( '.prev-posts').addClass( 'pagination-disabled' );
					}

					// Posts code
					data = data['code'];

					// Load More button append the new items
					if( isLoadMore ){
						var content = ( '<ul class="'+theListClass+' posts-list-container clearfix posts-items-loaded-ajax posts-items-'+currentPage+'">'+ data +'</ul>' );
						content = jQuery( content );
						theBlockDiv.append( content );
					}

					// Other pagination Types
					else{
						var content = ( '<ul class="'+theListClass+' posts-list-container posts-items-'+currentPage+'">'+ data +'</ul>' );
						content = jQuery( content );
						theBlockDiv.html( content );
					}

					var theBlockList_li = theBlock.find( '.posts-items-'+currentPage );

					// Animate the loaded items
					//theBlockList_li.find('li').addClass( 'tie-animate-slideInUp tie-animate-delay' );

					var $i = 0;
					theBlockList_li.find('li').each(function(){
						$i++;
						jQuery(this).addClass( 'tie-animate-slideInUp' ).attr('style', 'animation-delay:'+ $i/10 +'s' );
					});

					tie_animate_element( theBlockList_li );

					theBlockDiv.attr( 'style', '' );
				}
			});

			// Change the next page number
			theBlock.attr( 'data-current', currentPage );
		}
		
		return false;
	});


	/**
	 * Widget Ajax Pagination
	 */
	$doc.on( 'click', '.widget-pagination', function(){

		var pagiButton   = jQuery(this),
				theBlock     = pagiButton.closest('.widget-posts-list-wrapper'),
				theBlockDiv  = theBlock.find('.widget-posts-list-container'),
				currentPage  = theBlockDiv.attr('data-current'),
				theQuery     = theBlockDiv.attr('data-query'),
				theStyle     = theBlockDiv.attr('data-style'),
				isLoadMore   = false;

		// Check if the Button Disabled
		if( pagiButton.hasClass( 'pagination-disabled' ) ){
			return false;
		}

		// Check if the button type is Load More
		if( pagiButton.hasClass( 'load-more-button' ) ){
			currentPage++;
			isLoadMore = true;
		}

		// Next page button
		else if( pagiButton.hasClass( 'next-posts' ) ){
			currentPage++;
			theBlock.find( '.prev-posts' ).removeClass( 'pagination-disabled' );
		}

		// Prev page button
		else if( pagiButton.hasClass( 'prev-posts' ) ){
			currentPage--;
			theBlock.find( '.next-posts' ).removeClass( 'pagination-disabled' );
		}

		// Ajax Call
		jQuery.ajax({
			url : tie.ajaxurl,
			type: 'post',
			data: {
				action : 'tie_widgets_load_more',
				query  : theQuery,
				style  : theStyle,
				page   : currentPage,
			},
			beforeSend: function(){

				// Load More button----------
				if( isLoadMore ){
					pagiButton.html( tie.ajax_loader );
				}
				// Other pagination Types
				else{
					var blockHeight = theBlockDiv.height();
					theBlockDiv.append( tie.ajax_loader ).attr( 'style', 'min-height:' +blockHeight+ 'px' );
					theBlockDiv.addClass('is-loading');
				}
			},
			success: function( data ){

				data = jQuery.parseJSON(data);

				// Hide next posts button
				if( data['hide_next'] ){
					theBlock.find( '.next-posts').addClass( 'pagination-disabled' );
					if( pagiButton.hasClass( 'show-more-button' ) || isLoadMore ){
						pagiButton.html( data['button'] );
					}
				}
				else if( isLoadMore ){
					pagiButton.html( pagiButton.attr('data-text') );
				}

				// Hide Prev posts button
				if( data[ 'hide_prev' ] ){
					theBlock.find( '.prev-posts').addClass( 'pagination-disabled' );
				}

				theBlockDiv.find('.loader-overlay').remove();
				theBlockDiv.removeClass('is-loading');

				// Posts code
				data = data['code'].replace( /class="widget-single-post-item/g, 'class="widget-single-post-item tie-animate-slideInUp posts-items-'+currentPage+' ' );

				// Load More button append the new items
				if( isLoadMore ){
					theBlockDiv.find('.widget-posts-wrapper').append( data );
				}
				// Other pagination Types
				else{
					theBlockDiv.find('.widget-posts-wrapper').html( data );
				}

				var theBlockList_li = theBlock.find( '.posts-items-'+currentPage );

				// Animate the loaded items
				if( isLoadMore ){
					var $i = 0;
					theBlockList_li.each(function(){
						$i++;
						jQuery(this).attr('style', 'animation-delay:'+ $i/10 +'s' );
					});
				}

				tie_animate_element( theBlockList_li );

				theBlockDiv.attr( 'style', '' );
			}
		});

		// Change the next page number
		theBlockDiv.attr( 'data-current', currentPage );

		return false;
	});


	/**
	 * AJAX FILTER FOR BLOCKS
	 */
	$doc.on( 'click', '.block-ajax-term', function(){
		var termButton   = jQuery(this),
				theBlock     = termButton.closest('.mag-box'),
				theTermID    = termButton.attr('data-id'),
				theBlockID   = theBlock.get(0).id,
				theBlockList = theBlock.find('.posts-list-container'),
				theBlockDiv  = theBlock.find('.mag-box-container'),
				options      = jQuery.extend( {}, window[ 'js_'+theBlockID.replace( 'tie-', 'tie_' ) ] ),
				theListClass = 'posts-items';

		if( options ){

			// Set the data attr new values
			theBlock.attr( 'data-current', 1 );

			if( theTermID ){
				if( options[ 'tags' ] ){
					options['tags'] = theTermID;
				}
				else{
					options['id'] = theTermID;
				}
				theBlock.attr( 'data-term', theTermID );
			}
			else{
				theBlock.removeAttr( 'data-term' );
			}

			// Custom Block List Class
			if( options[ 'ajax_class' ] ){
				theListClass = options[ 'ajax_class' ];
			}

			// Ajax Call
			jQuery.ajax({
				url : tie.ajaxurl,
				type: 'post',
				data: {
					action: 'tie_blocks_load_more',
					block : options,
				},
				beforeSend: function(){
					var blockHeight = theBlockDiv.height();
					theBlockDiv.append( tie.ajax_loader ).attr( 'style', 'min-height:' +blockHeight+ 'px' );
					theBlockList.addClass('is-loading')
				},
				success: function( data ){

					data = jQuery.parseJSON(data);

					// Reset the pagination
					theBlock.find( '.block-pagination').removeClass( 'pagination-disabled' );
					var LoadMoreButton = theBlock.find( '.show-more-button' );
					LoadMoreButton.html( LoadMoreButton.attr('data-text') );

					// Active the selected term
					theBlock.find( '.block-ajax-term').removeClass( 'active' );
					termButton.addClass( 'active' );

					// Hide next posts button
					if( data['hide_next'] ){
						theBlock.find( '.next-posts').addClass( 'pagination-disabled' );
						theBlock.find( '.show-more-button' ).html( data['button'] )
					}

					// Hide Prev posts button
					if( data['hide_prev'] ){
						theBlock.find( '.prev-posts').addClass( 'pagination-disabled' );
					}

					// Posts code
					data = data['code'];

					var content = ( '<ul class="'+theListClass+' ajax-content posts-list-container">'+data+"</ul>" );
					content = jQuery( content );
					theBlockDiv.html( content );

					// Animate the loaded items
					//theBlockDiv.find( 'li' ).addClass( 'tie-animate-slideInUp tie-animate-delay' );

					var $i = 0;
					theBlockDiv.find( 'li' ).each(function(){
						$i++;
						jQuery(this).addClass( 'tie-animate-slideInUp' ).attr('style', 'animation-delay:'+ $i/10 +'s' );
					});

					tie_animate_element( theBlockDiv );

					theBlockDiv.attr( 'style', '' );
				}
			});

		}

		return false;
	});


	/**
	 * Mobile Menus
	 */
	function create_mobile_menu(){

		if( ! tie.mobile_menu_active || mobileMenu ){
			return false;
		}

		var $mobileMenu = jQuery('#mobile-menu'),
				mobileItems = '';

		if( $mobileMenu.hasClass( 'has-custom-menu' ) ){

			var $mobileMenuCustom = jQuery('#mobile-custom-menu');

			$mobileMenuCustom.find( 'div.mega-menu-content' ).remove();
			$mobileMenuCustom.find( 'li.menu-item-has-children:not(.hide-mega-headings)' ).append( '<span class="mobile-arrows tie-icon-chevron-down"></span>' );
		}
		else{

			var $mainNavMenu = $mainNav.find('div.main-menu > ul');

			// Main Nav
			if( $mainNavMenu.length ){
				var mobileItems = $mainNavMenu.clone();

				mobileItems.find( '.mega-menu-content' ).remove();
				mobileItems.removeAttr('id').find( 'li' ).removeAttr('id');
				mobileItems.find( 'li.menu-item-has-children:not(.hide-mega-headings)' ).append( '<span class="mobile-arrows tie-icon-chevron-down"></span>' );
				$mobileMenu.append( mobileItems );

				/* if the mobile menu has only one element, show it's sub content menu */
				var mobileItemsLis = mobileItems.find('> li');
				if( mobileItemsLis.length == 1 ){
					mobileItemsLis.find('> .mobile-arrows').toggleClass('is-open');
					mobileItemsLis.find('> ul').show();
				}
			}

			// Top Nav
			if( tie.mobile_menu_top ){
				var $topNav = jQuery('#top-nav div.top-menu > ul');

				if( $topNav.length ){
					var mobileItemsTop = $topNav.clone();

					mobileItemsTop.removeAttr('id').find( 'li' ).removeAttr('id');
					mobileItemsTop.find( 'li.menu-item-has-children' ).append( '<span class="mobile-arrows tie-icon-chevron-down"></span>' );
					$mobileMenu.append( mobileItemsTop );
				}
			}
		}

		// Open, Close behavior
		if( ! tie.mobile_menu_parent ){
			jQuery('li.menu-item-has-children > a, li.menu-item-has-children > .mobile-arrows', '#mobile-menu' ).click(function(){
				jQuery(this).parent().find('ul').first().slideToggle(300);
				jQuery(this).parent().find('> .mobile-arrows').toggleClass('is-open');
				return false;
			});
		}
		else{
			jQuery('li.menu-item-has-children .mobile-arrows', '#mobile-menu' ).click(function(){
				jQuery(this).toggleClass('is-open').closest('.menu-item').find('ul').first().slideToggle(300);
				return false;
			});
		}

		//
		mobileMenu = true;
	}


	// Debugging
	performance.mark('TieEnd');
	performance.measure( 'TieLabs Custom JS', 'TieStart', 'TieEnd' );

	//console.table(performance.getEntriesByType("mark"));
	//console.table(performance.getEntriesByType("measure"));

});


/**
 * ANIMATE ELEMENTS
 * This function used to animate theme elements
 * Used multiple times in this files to fire the animation for intial and Ajax content
 */
function tie_animate_element( $itemContainer ){

	if( ! $itemContainer  ){
		return;
	}

	$itemContainer = jQuery( $itemContainer );

	// Reviews
	tie_animate_reviews( $itemContainer );

	// LazyLoad
	if( tie.lazyload ){

		// Images
		$itemContainer.find( '[data-src]' ).each(function(){
			var elem = jQuery(this);
			elem.attr('src', elem.data('src') );
			elem.removeAttr('data-src');
		});

		// BG
		$itemContainer.find( '[data-lazy-bg]' ).each(function(){
			var elem = jQuery(this);
			elem.attr('style', 'background-image:url(' + elem.data('lazy-bg') + ')' );
			elem.removeAttr('data-lazy-bg');
		});
	}

	adBlockBlock( $itemContainer );

}


/**
 * Animate Reviews
 */
function tie_animate_reviews( $itemContainer ){

	// Reviews
	if( tie.is_taqyeem_active ){

		// Pie
		$itemContainer.find( '[data-lazy-pie]' ).each(function(){
			var elem    = jQuery(this),
					pctVal  = parseInt( elem.data('pct') ),
					$circle = elem.find('.circle_bar'),
					pctNew  = ((100-pctVal)/100) * Math.PI*(19*2); // 19 == $circle.getAttribute('r')

			$circle.attr('style', 'stroke-dashoffset:'+ pctNew +'px' );
			elem.removeAttr('data-lazy-pie');
		});

		// Star
		$itemContainer.find( '[data-lazy-percent]' ).each(function(){
			var elem = jQuery(this);
			elem.attr('style', 'width:'+ elem.data('rate-val') );
			elem.removeAttr('data-lazy-percent');
		});
	}
}

// Block Images
function adBlockBlock( $itemContainer ) {
	
	if( tie.ad_blocker_disallow_images_placeholder ){

		if ( jQuery('.Ad-Container').length > 0 && jQuery('.Ad-Container').outerWidth() === 0 ) {		

			if( tie.ad_blocker_disallow_images_post ){
				$itemContainer = jQuery('#the-post .entry-content');
			}
			else if( ! $itemContainer ){
				$itemContainer = $body;
			}
			
			$itemContainer.find('img:not(.tie-logo-img)').attr({
				src: tie.ad_blocker_disallow_images_placeholder,
				srcset: tie.ad_blocker_disallow_images_placeholder,
			});
			
			$itemContainer.find('.grid-item, .big-thumb-left-box-inner, .main-slider:not(.tie-grid-slider) .slide, .post-thumb[style]').attr( 'style', 'background-image: url("'+ tie.ad_blocker_disallow_images_placeholder +'"); background-position: center;' );
		}
	}
}


/*! tieFitVids */
!function(t){"use strict";t.fn.tieFitVids=function(e){var i={customSelector:null,ignore:null};e&&t.extend(i,e);var r=['iframe[src*="player.vimeo.com"]','iframe[src*="player.twitch.tv"]','iframe[src*="youtube.com"]','iframe[src*="youtube-nocookie.com"]','iframe[src*="maps.google.com"]','iframe[src*="google.com/maps"]','iframe[src*="dailymotion.com"]','iframe[src*="twitter.com/i/videos"]',"object","embed"];if(r=r.join(","),i.customSelector&&r.push(i.customSelector),document.querySelectorAll(r).length){var a=".tie-ignore-fitvid, .wmvp-video-player, #buddypress";return i.ignore&&(a=a+", "+i.ignore),this.each(function(){t(this).find(r).each(function(){var e=t(this);if(!(e.parents(a).length>0||("embed"===this.tagName.toLowerCase()||"object"===this.tagName.toLowerCase())&&e.parent("object").length||e.parent(".tie-fluid-width-video-wrapper").length)){e.css("height")||e.css("width")||!isNaN(e.attr("height"))&&!isNaN(e.attr("width"))||(e.attr("height",9),e.attr("width",16));var i=("object"===this.tagName.toLowerCase()||e.attr("height")&&!isNaN(parseInt(e.attr("height"),10))?parseInt(e.attr("height"),10):e.height())/(isNaN(parseInt(e.attr("width"),10))?e.width():parseInt(e.attr("width"),10));e.removeAttr("height").removeAttr("width"),e.wrap('<div class="tie-fluid-width-video-wrapper"></div>').parent(".tie-fluid-width-video-wrapper").css("padding-top",100*i+"%")}})})}}}(window.jQuery);

/**
 * Responsive Videos
 * use .tie-ignore-fitvid to manually exclude videos for example in the post editor.
 */
$container.tieFitVids();
