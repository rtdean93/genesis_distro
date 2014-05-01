(function ($) {

  /**
   * Gets the main title for the item.
   */
  function getTitle(item) {
    var headerSibling = item.prev('h1, h2, h3, h4, h5, h6'),
        headerInside = item.find('h1, h2, h3, h4, h5, h6');

    if (headerSibling.length > 0) {
      return $(headerSibling[0]).text().toLowerCase();
    }
    else if (headerInside.length > 0) {
      return $(headerInside[0]).text().toLowerCase();
    }
    else {
      return null;
    }
  }

  /**
   * Generate the trigger text.
   */
  function triggerText(name) {
    var params = new Object;

    if (name) {
      params['!title'] = name;
      return Drupal.t('Expand the !title', params)
    }
    else {
      return Drupal.t('Expand');
    }
  }

  /**
   * Collapse expanded menus based on the trigger.
   */
  function menuCollapse(trigger) {
    var menuId = trigger.data('menu'),
        menu = $('.collapsible-menu-' + menuId);

    menu.addClass('menu-closed').removeClass('menu-active');
    trigger.removeClass('trigger-active');
  }

  /**
   * Helper function for expanding and collapsing the menu.
   */
  function menuState(menu, trigger, responsive) {
    if (responsive == true) {
      var breakpoint = $(window).checkBreakpoint(),
          stateName = breakpoint.state.name;

      // Hide the content if the screen is the right size.
      if (stateName != 'medium' && stateName != 'large' && stateName != 'extra-large') {
        trigger.removeClass('element-invisible');
        menu.removeClass('menu-active').addClass('menu-closed');
      }
      else {
        trigger.addClass('element-invisible').removeClass('trigger-active');
        menu.removeClass('menu-closed menu-active');
      }
    }

    // Enable the trigger.
    trigger.click(function () {
      if (trigger.hasClass('trigger-active')) {
        trigger.removeClass('trigger-active');
        menu.removeClass('menu-active').addClass('menu-closed');
      }
      else {
        var activeTriggers = $('.trigger-active');

        if (activeTriggers) {
          menuCollapse(activeTriggers);
        }

        trigger.addClass('trigger-active');
        menu.removeClass('menu-closed').addClass('menu-active');
      }

      return false;
    });
  }

  /**
   * Add toggles to expand and collapse blocks.
   */
  Drupal.behaviors.demonstratieMenu = {
    attach: function (context, settings) {
      var counter = 1;

      $('.site-navigation').once('demonstratieMenu', function () {
        var triggerHolder = $('<div>').attr({
              'class' : 'trigger-container'
            });

        triggerHolder.prependTo('.site-navigation');
      });

      $('.main-menu, .secondary-menu').once('demonstratieMenu', function () {
        var menu = $(this).data('trigger', counter).addClass('collapsible-menu-' + counter),
            menuTitle = getTitle(menu),
            triggerLabel = triggerText(menuTitle),
            trigger = $('<a>').data('menu', counter).attr({
              'class' : 'trigger-' + counter + ' trigger-' + menuTitle.split(' ').join('-').toLowerCase() + ' menu-trigger',
              'href' : '#',
              'title' : triggerLabel
            }).text(triggerLabel),
            triggerHolder = $('.trigger-container');

        // Initialize the menus and triggers.
        triggerHolder.append(trigger);
        menuState(menu, trigger, true);

        // Watch for resizing and reevaluate the menu state if it changes.
        $(window).resize(function() {
          menuState(menu, trigger, true);
        });

        counter = counter + 1;
      });
    }
  };

  /**
   * Show header blocks when an icon is clicked.
   */
  Drupal.behaviors.demonstratieHeaderBlock = {
    attach: function (context, settings) {
      $('.header-search .block', context).once('demonstatieHeaderBlock', function () {
        var triggerHolder = $('div.trigger-container'),
            block = $(this).addClass('collapsible-menu-' + $(this).attr('id') + ' collapsible-block menu-closed'),
            menuTitle = getTitle(block),
            triggerLabel = triggerText(menuTitle),
            trigger = $('<a>').data('menu', block.attr('id')).attr({
              'class' : 'trigger-' + block.attr('id') + ' menu-trigger',
              'href' : '#',
              'title' : triggerLabel
            }).text(triggerLabel);

        // Initialize the blocks and triggers.
        triggerHolder.append(trigger);
        menuState(block, trigger);
      });
    }
  };

})(jQuery);
