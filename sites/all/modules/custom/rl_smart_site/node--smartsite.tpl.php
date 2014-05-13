 <head>
	<script src="http://code.jquery.com/jquery-1.3b1.js" type="text/javascript">
	</script>
	<script type="text/javascript">

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
	</script>
</head>








<?php
  $responsive = $node->field_responsive['und'][0]['value'];
?>


<body>

      <div>
   
     
        <ul id="screen-options">
          <li id="desktop" class="active">Desktop</li>
          <?php
            if ($responsive ==1){  ?>
          <li id="ipad">iPad</li>
              <li id="ipad-l" >iPad landscape</li>
          <li id="iphone">iPhone</li>
          
             
            <li id="iphone-l" >iPhone landscape</li>

        
          
          
          <?php
          };
          ?>
        </ul>

       <!--    <a class="colorbox-inline button-try" href="#" rel="sales@reachlocal.com">Try ReachEdge&trade;</a> -->

      </div>
  


<?php
	
	if ($responsive == 1){
?>

	<div id="viewport" class="desktop">
		<iframe id="displayframe" name="displayframe" height="480" width="320" src=""></iframe>

	</div>
	<div id="device-detail" class="desktop-detail">device detail</div>

<?php
	}
	elseif($responsive ==0){
?>
<div id="viewport_full" class="desktop_full">
	<iframe src="<?php print check_plain($variables['field_smartsite_demo_link'][0]['url']) ?>" id="displayframe_full" name="displayframe_full"> </iframe>
</div>

<?php
	};
	?>
</body>
