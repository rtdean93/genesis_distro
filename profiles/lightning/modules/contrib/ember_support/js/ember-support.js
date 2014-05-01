(function ($) {

//Show the IPE menu when the mouse is actively in the window

$(window).on('mousemove', function () {
  $('#panels-ipe-control-container').fadeIn('show');
});

//hide the menu when the mouse leaves the window.

$(window).on('mouseleave', function () {
  $('#panels-ipe-control-container').fadeOut('show');
});

})(jQuery);