  <div class="<?php print $classes; ?>"<?php print $attributes; ?>>
    <header class="site-header content-container">
      <div class="header-inner clearfix">

        <div class="site-information clearfix">
          <div class="site-meta clearfix">
            <?php if (!empty($title)): ?>
              <span<?php print $page_title_attributes; ?>><?php print $site_name; ?></span>
            <?php else: ?>
              <h1<?php print $page_title_attributes; ?>><?php print $site_name; ?></h1>
            <?php endif; ?>

            <?php if (!empty($page['logo_image'])): ?>
              <span class="logo-wrapper"><?php print render($page['logo_image']); ?></span>
            <?php endif; ?>

            <?php if (!empty($site_slogan)): ?>
              <p class="site-slogan"><?php print $site_slogan; ?></p>
            <?php endif; ?>
          </div>

          <?php if ($main_menu || $secondary_menu): ?>
            <nav class="site-navigation clearfix">
              <?php print render($page['main_menu']); ?>
              <?php print render($page['secondary_menu']); ?>
            </nav>
          <?php endif; ?>
        </div>

      </div>

      <?php if ($page['search'] || $page['header_first'] || $page['header_second']): ?>
        <div class="header-search content-container">
          <div class="header-search-inner clearfix">
            <?php print render($page['search']); ?>
            <?php print render($page['header_first']); ?>
            <?php print render($page['header_second']); ?>
          </div>
        </div>
      <?php endif; ?>
    </header>

    <?php if (!empty($page['feature'])): ?>
      <section class="feature clearfix">
        <div class="feature-inner content-container">
          <?php print render($page['feature']); ?>
        </div>
      </section>
    <?php endif; ?>

    <section class="content">
      <div class="content-inner content-container clearfix">

        <?php if (!empty($title)): ?>
          <h1 class="page-title" ?><?php print $title; ?></h1>
        <?php endif; ?>

        <?php print $messages; ?>
        <?php print render($page['highlighted']); ?>
        <?php print render($page['help']); ?>
        <?php print render($tabs); ?>
        <?php print render($page['content']); ?>

      </div>
    </section>

    <?php if (!empty($page['sidebar_first']) || !empty($page['sidebar_second'])): ?>
      <section class="content-container">
        <?php print render($page['sidebar_first']); ?>
        <?php print render($page['sidebar_second']); ?>
      </section>
    <?php endif; ?>

    <footer class="site-footer">
      <div class="footer-inner content-container clearfix">

        <?php if (!empty($page['footer_first'])): ?>
          <div class="footer-first">
            <?php print render($page['footer_first']); ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($page['footer_second'])): ?>
          <div class="footer-second">
            <?php print render($page['footer_second']); ?>
          </div>
        <?php endif; ?>

      </div>
    </footer>
  </div>
