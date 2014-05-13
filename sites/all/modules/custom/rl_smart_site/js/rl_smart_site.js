$(function() {


    $('#iphone').click(function() {
      $("#screen-options li").removeClass("active");
      $(this).addClass("active");
      $('#viewport').attr('class', 'iphone');
      $('#device-detail').attr('class', 'iphone-detail');
    });

    $('#iphone-l').click(function() {
      $("#screen-options li").removeClass("active");
      $(this).addClass("active");
      $('#viewport').attr('class', 'iphone-l');
      $('#device-detail').attr('class', 'iphone-l-detail');
    });

    $('#ipad').click(function() {
      $("#screen-options li").removeClass("active");
      $(this).addClass("active");
      $('#viewport').attr('class', 'ipad');
      $('#device-detail').attr('class', 'ipad-detail');
    });

    $('#ipad-l').click(function() {
      $("#screen-options li").removeClass("active");
      $(this).addClass("active");
      $('#viewport').attr('class', 'ipad-l');
      $('#device-detail').attr('class', 'ipad-l-detail');
    });

    $('#desktop').click(function() {
      $("#screen-options li").removeClass("active");
      $(this).addClass("active");
      $('#viewport').attr('class', 'desktop');
      $('#device-detail').attr('class', 'desktop-detail');
    });


    //// Check for url in url
    var givenURL = ["<?php print check_plain($variables['field_smartsite_demo_link'][0]['url']) ?>"];

     $('#getURL').attr('value',givenURL );
      $('#displayframe').attr('src',givenURL);



  });
