<?php
/**
 * @file
 * Markup for theme_demonstratie_layout_single().
 */
?>

<div class="<?php print $classes; ?>"<?php print $attributes; ?>>
  <div<?php print $content_attributes; ?>>
    <?php foreach($content as $region => $region_content): ?>
      <?php if (!empty($region_content)): ?>
        <div<?php print ${$region . '_attributes'}; ?>>
          <?php print $region_content; ?>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>
