// @codekit-prepend "jquery.fitvids.js"
// @codekit-prepend "jquery.sidr.min.js"
// @codekit-prepend "slick.min.js"

jQuery(function($){

	// FitVids
	$('.entry-content').fitVids();

	// Home Slider
	$('.featured-posts .slick-slider').slick({
		arrows: false,
		dots: true,
		slidesToShow: 3,
		slidesToScroll: 3,
		responsive: [
			{
				breakpoint: 500,
				settings: {
					slidesToShow: 1,
					slidesToScroll: 1
				}
			}
		]
	});

	// Custom carousel nav
	$('.carousel-prev').click(function(e){ e.preventDefault(); $(this).parent().find('.slick-slider').slick('slickPrev'); } );
	$('.carousel-next').click(function(e){ e.preventDefault(); $(this).parent().find('.slick-slider').slick('slickNext'); } );

	// Property slider
	$('.property-gallery .slick-slider').slick({
		arrows: false,
		dots: true
	});

	// Ad widgets, count how many
	$('.post-summary.sponsor').each(function(){
		children = $(this).children().length;
		$(this).addClass('count-' + children );
	});

	// Mobile Menu
	$('.mobile-menu-toggle').sidr({
		name: 'sidr-mobile-menu',
		side: 'right',
		renaming: false,
		displace: false,
	});
	$('.menu-item-has-children').prepend('<span class="submenu-toggle">' );
	$('.menu-item-has-children.sidr-class-current-menu-item').addClass('submenu-active');
	$('.menu-item-has-children > .submenu-toggle').click(function(e){
		$(this).parent().toggleClass('submenu-active');
		e.preventDefault();
	});
	$('.sidr a').click(function(){
		$.sidr('close', 'sidr-mobile-menu');
	});
	$('.sidr-menu-close').click(function(e){
		e.preventDefault();
	});
	$(document).on( 'mouseup touchend', (function (e){
		var container = $("#sidr-mobile-menu");
		if (!container.is(e.target) && container.has(e.target).length === 0) {
			$.sidr('close', 'sidr-mobile-menu');
		}
	}));

	// Mobile search toggle
	$('.nav-mobile .search-toggle').click(function(e){
		e.preventDefault();
		$(this).toggleClass('active');
		$('.mobile-search').toggleClass('active').find('.search-field').focus();
	});

	// Desktop search toggle
	$('.nav-primary .search-toggle').click(function(e){
		e.preventDefault();
		$(this).toggleClass('active');
		$('.nav-primary .search-form').toggleClass('active').find('.search-field').focus();
	});

	// Share summary toggle
	$('.share-summary-toggle').click(function(e){
		e.preventDefault();
		$(this).parent().toggleClass('active');
	});

	// Smooth scrolling anchor links
	function ea_scroll( hash ) {
		var target = $( hash );
		target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
		if (target.length) {
			var top_offset = 0;
			if ( $('.site-header').css('position') == 'fixed' ) {
				top_offset = $('.site-header').height();
			}
			if( $('body').hasClass('admin-bar') ) {
				top_offset = top_offset + $('#wpadminbar').height();
			}
			 $('html,body').animate({
				 scrollTop: target.offset().top - top_offset
			}, 1000);
			return false;
		}
	}
	// -- Smooth scroll on pageload
	if( window.location.hash ) {
		ea_scroll( window.location.hash );
	}
	// -- Smooth scroll on click
	$('a[href*="#"]:not([href="#"]):not(.no-scroll)').click(function() {
		if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') || location.hostname == this.hostname) {
			ea_scroll( this.hash );
		}
	});


});
