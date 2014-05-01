(function ($) {

  /**
   * Gets the current breakpoint.
   */
  $.fn.checkBreakpoint = function() {
    var size = this.width(),
        breakpoints = {
          'small' : 400,
          'medium' : 760,
          'large' : 980,
          'extra-large' : 1024
        },
        status = new Object,
        state = new Object;

    // Cycle through the breakpoint definitions and find biggest one that is
    // less than the window size.
    for (var name in breakpoints) {
      if (breakpoints[name] < size) {
        state = {
          'size' : size,
          'name' : name,
          'break' : breakpoints[name]
        };
      }
    }

    // Build out the rest of the object with reference data.
    status.breakpoints = breakpoints;
    status.state = state;

    return status;
  }

  /**
   * Make sure a video fits within its parental boundaries.
   */
  function videoCheck(container, video) {
    var containerWidth = container.width(),
        videoWidth = video.attr('width');

    if (containerWidth <= videoWidth && !container.hasClass('video-responsive')) {
      container.addClass('video-responsive').css('padding-bottom', video.data('ratio') + '%');
    }
    else if (containerWidth > videoWidth && container.hasClass('video-responsive')) {
      container.removeClass('video-responsive').css('padding-bottom', '');
    }
  }

  /**
   * Make videos fit the parent container.
   */
  Drupal.behaviors.demonstratieVideo = {
    attach: function (context, settings) {
      $('.file-video video, .file-video iframe, .file-video embed, .file-video object', context).once('demonstratieVideo', function () {
        var video = $(this).addClass('video-embed').data('ratio', $(this).attr('height') / $(this).attr('width') * 100),
            parent = video.parent().addClass('video-wrapper');

        videoCheck(parent, video);

        $(window).resize(function () {
          videoCheck(parent, video);
        });
      });
    }
  };

})(jQuery);
