<?php
/**
 * ECTBE onboarding wizard markup (admin view).
 *
 * Expected variables (set by ECTBE_Onboarding_Page::get_wizard_markup()):
 * @var string               $dashboard_url
 * @var string               $icons
 * @var array<string,string> $editor_icons
 * @var string               $category_options
 * @var string               $preview_state
 * @var string               $default_editor
 * @var bool                 $show_telemetry
 *
 * @package Events_Widgets_For_Elementor_And_The_Events_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ectbe-onboarding-admin" data-preview-state="<?php echo esc_attr( $preview_state ); ?>" data-editor-selected="<?php echo esc_attr( $default_editor ); ?>">
<div class="ectbe-wizard-shell">

  <!-- ============================================================
       Header — brand (left) + progress steps (center) + Exit (right)
       ============================================================ -->
  <header class="ectbe-wizard-header">
    <div class="ectbe-wizard-header__brand">
      <img src="<?php echo esc_url( $icons ); ?>events-widgets-icon.svg" alt="" class="ectbe-wizard-header__brand-icon">
      <span class="ectbe-wizard-header__brand-name">
        <strong>Events Widgets</strong>
        <em>for Elementor</em>
      </span>
    </div>

    <ol class="ectbe-wizard-header__steps" data-wizard-progress></ol>

    <a href="<?php echo esc_url( $dashboard_url ); ?>" data-wizard-finish class="ectbe-wizard-header__exit">
      <span>Exit Setup</span>
      <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
    </a>
  </header>

  <!-- ============================================================
       Main content — card holds each step with inline nav.
       ============================================================ -->
  <main class="ectbe-wizard-main">
    <div class="ectbe-wizard-card">

      <!-- ============================================================
           STEP 1 — Setup / Mode selection
           ============================================================ -->
      <section class="ectbe-wizard-step is-active" data-step="mode">
        <header class="ectbe-wizard-card__heading">
          <h1 class="ectbe-wizard-card__title">Set Up The Events Calendar with Elementor</h1>
          <p class="ectbe-wizard-card__desc">Choose what you want to create today: an events listing page or a custom single event page template in Elementor.</p>
        </header>

        <div class="ectbe-editor-selector">
          <div class="ectbe-editor-selector__options" data-required-selection="editor">

            <a href="#" role="button" class="ectbe-editor-option" data-value="listing" data-video-label="Events Widgets for Elementor" data-youtube-id="2m74nSrEo0g">
              <img src="<?php echo esc_url( $icons ); ?>events-widgets-icon.svg" alt="" class="ectbe-editor-option__icon">
              <span class="ectbe-editor-option__text">
                <span class="ectbe-editor-option__name">Events Listing</span>
                <span class="ectbe-editor-option__sub">Show events on any Elementor page using List, Grid, Carousel, Calendar and more layouts.</span>
              </span>
              <span class="ectbe-editor-option__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
            </a>

            <a href="#" role="button" class="ectbe-editor-option" data-value="single-page" data-video-label="Event Single Page Builder" data-youtube-id="50FBrcqoB-M">
              <img src="<?php echo esc_url( $icons ); ?>event-single-page-icon.svg" alt="" class="ectbe-editor-option__icon">
              <span class="ectbe-editor-option__text">
                <span class="ectbe-editor-option__name">Single Event Page Template</span>
                <span class="ectbe-editor-option__sub">Design a custom single page template for individual event details pages using Elementor widgets.</span>
              </span>
              <span class="ectbe-editor-option__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
            </a>

          </div>

          <div class="ectbe-editor-selector__video" data-editor-video>
            <img src="" alt="" class="ectbe-editor-selector__video-watermark" data-editor-video-watermark hidden>
            <span class="ectbe-editor-selector__video-waves" aria-hidden="true"></span>
            <a href="#" class="ectbe-editor-selector__video-play" aria-label="Play walkthrough video" data-editor-video-play></a>
            <span class="ectbe-editor-selector__video-label">
              <span class="dashicons dashicons-video-alt3" aria-hidden="true"></span>
              <span data-editor-video-label>Pick a setup to see a walkthrough</span>
            </span>
            <div class="ectbe-editor-selector__video-frame" data-video-frame></div>
          </div>
        </div>

        <div class="ectbe-editor-promo" data-editor-promo hidden></div>

        <div class="ectbe-wizard-card__nav">
          <a href="#" role="button" class="ectbe-btn-ghost" data-wizard-back>
            <span class="dashicons dashicons-arrow-left-alt" aria-hidden="true"></span>
            <span>Back</span>
          </a>
          <div class="ectbe-wizard-card__nav-right" data-nav-right data-nav-mode="default">
            <a href="#" role="button" class="ectbe-btn-disabled" data-wizard-next data-nav-variant="default" aria-disabled="true">
              <span data-wizard-next-label>Continue</span>
              <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
            </a>

            <a href="#" role="button" class="ectbe-btn-accent" data-nav-variant="promo-install" data-promo-install>
              <span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
              <span>Get <span data-addon-name>Single Page Builder</span> Pro</span>
            </a>

            <a href="#" role="button" class="ectbe-btn-primary" data-nav-variant="promo-open" data-promo-open>
              <span>Open <span data-addon-name>Single Page Builder</span> setup</span>
              <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
            </a>

            <a href="#" role="button" class="ectbe-btn-secondary" data-nav-variant="promo-dismiss" data-editor-promo-dismiss>
              <span data-editor-promo-dismiss-label>No, continue with Events Listing</span>
            </a>
          </div>
        </div>
      </section>

      <!-- ============================================================
           STEP 2 — Layout & Events Query (combined)
           ============================================================ -->
      <section class="ectbe-wizard-step" data-step="layout-query">
        <header class="ectbe-wizard-card__heading">
          <h1 class="ectbe-wizard-card__title">Layout &amp; events query</h1>
          <p class="ectbe-wizard-card__desc">Pick a layout and choose which events show up in your Elementor page. Sensible defaults are pre-selected &mdash; tweak as needed.</p>
        </header>

        <div class="ectbe-settings-list">

          <div class="ectbe-settings-row" data-section="layout">
            <div class="ectbe-settings-row__info">
              <span class="ectbe-settings-row__icon" aria-hidden="true">
                <span class="dashicons dashicons-layout"></span>
              </span>
              <div class="ectbe-settings-row__meta">
                <h3 class="ectbe-settings-row__title">Layout</h3>
                <p class="ectbe-settings-row__desc">How events are arranged on the page.</p>
                <a class="ectbe-demo-link ectbe-settings-row__demo" href="https://eventscalendaraddons.com/demos/events-widgets-pro/" target="_blank" rel="noopener" data-utm="demo">
                  <span class="dashicons dashicons-external" aria-hidden="true"></span>
                  <span>View All Layouts Demos</span>
                </a>
              </div>
            </div>
            <div class="ectbe-settings-row__body">
              <div class="ectbe-layout-grid" data-required-selection="layout">
                <a href="#" role="button" class="ectbe-layout-card" data-value="list">
                  <span class="ectbe-layout-card__icon" aria-hidden="true"><span class="dashicons dashicons-list-view"></span></span>
                  <span class="ectbe-layout-card__name">List</span>
                  <span class="ectbe-layout-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ectbe-layout-card" data-value="minimal-list">
                  <span class="ectbe-layout-card__icon" aria-hidden="true"><span class="dashicons dashicons-menu-alt3"></span></span>
                  <span class="ectbe-layout-card__name">Minimal List</span>
                  <span class="ectbe-layout-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ectbe-layout-card" data-value="calendar">
                  <span class="ectbe-layout-card__icon" aria-hidden="true"><span class="dashicons dashicons-calendar-alt"></span></span>
                  <span class="ectbe-layout-card__name">Calendar</span>
                  <span class="ectbe-layout-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ectbe-layout-card" data-value="grid" data-pro="true">
                  <span class="ectbe-layout-card__pro">Pro</span>
                  <span class="ectbe-layout-card__icon" aria-hidden="true"><span class="dashicons dashicons-grid-view"></span></span>
                  <span class="ectbe-layout-card__name">Grid</span>
                  <span class="ectbe-layout-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ectbe-layout-card" data-value="carousel" data-pro="true">
                  <span class="ectbe-layout-card__pro">Pro</span>
                  <span class="ectbe-layout-card__icon" aria-hidden="true"><span class="dashicons dashicons-images-alt2"></span></span>
                  <span class="ectbe-layout-card__name">Carousel</span>
                  <span class="ectbe-layout-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ectbe-layout-card" data-value="loop-builder" data-pro="true">
                  <span class="ectbe-layout-card__pro">Pro</span>
                  <span class="ectbe-layout-card__icon" aria-hidden="true"><span class="dashicons dashicons-controls-repeat"></span></span>
                  <span class="ectbe-layout-card__name">Loop Builder</span>
                  <span class="ectbe-layout-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
              </div>
            </div>
          </div>

          <div class="ectbe-settings-row" data-section="category">
            <div class="ectbe-settings-row__info">
              <span class="ectbe-settings-row__icon" aria-hidden="true">
                <span class="dashicons dashicons-category"></span>
              </span>
              <div class="ectbe-settings-row__meta">
                <h3 class="ectbe-settings-row__title">Events Category</h3>
                <p class="ectbe-settings-row__desc">Show events from all categories or a specific list.</p>
              </div>
            </div>
            <div class="ectbe-settings-row__body">
              <div class="ectbe-multiselect" data-multiselect data-multiselect-name="category">
                <div class="ectbe-multiselect__control" data-multiselect-control tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox">
                  <span class="ectbe-multiselect__chips" data-multiselect-chips></span>
                  <span class="ectbe-multiselect__placeholder" data-multiselect-placeholder>Choose categories</span>
                  <span class="ectbe-multiselect__caret" aria-hidden="true"><span class="dashicons dashicons-arrow-down-alt2"></span></span>
                </div>
                <div class="ectbe-multiselect__dropdown" data-multiselect-dropdown role="listbox" hidden>
                  <?php echo $category_options; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when built ?>
                </div>
                <input type="hidden" data-wizard-input="category" value="all">
              </div>
            </div>
          </div>

          <div class="ectbe-settings-row" data-section="time">
            <div class="ectbe-settings-row__info">
              <span class="ectbe-settings-row__icon" aria-hidden="true">
                <span class="dashicons dashicons-clock"></span>
              </span>
              <div class="ectbe-settings-row__meta">
                <h3 class="ectbe-settings-row__title">Events Status</h3>
                <p class="ectbe-settings-row__desc">Show only upcoming, past or all events.</p>
              </div>
            </div>
            <div class="ectbe-settings-row__body">
              <div class="ectbe-style-row ectbe-style-row--3col" data-required-selection="time">
                <a href="#" role="button" class="ectbe-style-card" data-value="upcoming">
                  <span class="ectbe-style-card__name">Upcoming Events</span>
                  <span class="ectbe-style-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ectbe-style-card" data-value="past">
                  <span class="ectbe-style-card__name">Past Events</span>
                  <span class="ectbe-style-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
                <a href="#" role="button" class="ectbe-style-card" data-value="both">
                  <span class="ectbe-style-card__name">Both / Any</span>
                  <span class="ectbe-style-card__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
                </a>
              </div>
            </div>
          </div>

        </div>

        <div class="ectbe-pro-notice" data-pro-notice hidden>
          <span class="ectbe-pro-notice__icon" aria-hidden="true">
            <span class="dashicons dashicons-star-filled"></span>
          </span>
          <div class="ectbe-pro-notice__body">
            <h4 class="ectbe-pro-notice__title">You've picked <span data-pro-count>1</span> Pro feature<span data-pro-plural>s</span></h4>
            <p class="ectbe-pro-notice__desc" data-pro-notice-desc>Upgrade to Events Widgets Pro to unlock everything you've selected. Or pick free options to keep going.</p>
          </div>
        </div>

        <div class="ectbe-telemetry"<?php echo $show_telemetry ? '' : ' hidden'; ?>>
          <label class="ectbe-telemetry__checkbox-wrap">
            <input type="checkbox" class="ectbe-telemetry__checkbox" data-wizard-telemetry checked>
            <span class="ectbe-telemetry__mark" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
          </label>
          <div class="ectbe-telemetry__body">
            <strong class="ectbe-telemetry__title">Help improve Events Calendar Addons</strong>
            <span class="ectbe-telemetry__desc">Share non-sensitive usage data &mdash; WP version, addon versions, active builder, theme name. No personal data or event content. Change anytime in Settings. <a class="ectbe-telemetry__policy" href="https://my.coolplugins.net/terms/usage-tracking/" target="_blank" rel="noopener" data-utm="policy">View policy<span class="dashicons dashicons-external" aria-hidden="true"></span></a></span>
          </div>
        </div>

        <div class="ectbe-wizard-card__nav">
          <a href="#" role="button" class="ectbe-btn-ghost" data-wizard-back>
            <span class="dashicons dashicons-arrow-left-alt" aria-hidden="true"></span>
            <span>Back</span>
          </a>
          <div class="ectbe-wizard-card__nav-right" data-step2-nav-right data-nav-mode="default">
            <span class="ectbe-wizard-card__nav-hint">Insert Events widget in an Elementor page</span>
            <a href="#" role="button" class="ectbe-btn-primary" data-wizard-next data-step2-nav-variant="default" data-step-primary-cta>
              <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
              <span>Create Draft Page</span>
            </a>
            <a href="https://eventscalendaraddons.com/plugin/events-widgets-pro/" role="button" class="ectbe-btn-accent" data-step2-nav-variant="upgrade" target="_blank" rel="noopener">
              <span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
              <span data-upgrade-label>Upgrade to Pro</span>
            </a>
          </div>
        </div>
      </section>

      <!-- ============================================================
           STEP 3 — Done
           ============================================================ -->
      <section class="ectbe-wizard-step" data-step="success" data-always-valid="true">
        <div class="ectbe-wizard-success">
          <span class="ectbe-wizard-success__icon ectbe-wizard-success__icon--lg" aria-hidden="true">
            <span class="dashicons dashicons-yes"></span>
          </span>
          <h2 class="ectbe-wizard-success__title" data-step5-title>All done!</h2>
          <p class="ectbe-wizard-success__lede" data-step5-lede>Your Elementor page with the Events widget is ready to preview.</p>
        </div>

        <div class="ectbe-review-recap">
          <div class="ectbe-review-panel ectbe-review-panel--builder" data-review-panel="builder-recap">
            <div class="ectbe-review-builder">
              <img class="ectbe-review-builder__logo" data-review-builder-logo src="<?php echo esc_url( $editor_icons['elementor'] ); ?>" alt="">
              <div>
                <strong class="ectbe-review-builder__title" data-review-builder-title>Events widget inserted in Elementor page</strong>
                <p class="ectbe-review-builder__desc" data-review-builder-desc>Preview or open the draft to fine-tune anything.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="ectbe-wizard-success__actions">
          <a href="#" data-draft-preview target="_blank" rel="noopener" class="ectbe-btn-primary">
            <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
            <span>Preview Page</span>
          </a>
          <a href="#" data-draft-edit target="_blank" rel="noopener" class="ectbe-btn-secondary">
            <span class="dashicons dashicons-edit" aria-hidden="true"></span>
            <span>Edit Page in Elementor</span>
          </a>

          <a href="<?php echo esc_url( $dashboard_url ); ?>" data-wizard-finish class="ectbe-btn-success">
            <span>Finish</span>
            <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
          </a>
        </div>

        <div class="ectbe-cross-sell ectbe-cross-sell--bundle" data-cross-sell="bundle">
          <span class="ectbe-cross-sell__icon ectbe-cross-sell__icon--chip" aria-hidden="true">
            <span class="dashicons dashicons-awards"></span>
          </span>
          <div class="ectbe-cross-sell__body">
            <strong class="ectbe-cross-sell__title">The Events Calendar Addons Bundle</strong>
            <span class="ectbe-cross-sell__desc">Get events calendar addons discounted bundle for your event website.</span>
          </div>
          <div class="ectbe-cross-sell__actions">
            <a href="https://eventscalendaraddons.com/pricing/" target="_blank" rel="noopener" class="ectbe-btn-accent" data-utm="get_bundle">
              <span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
              <span>View Addons Bundle</span>
            </a>
            <a href="<?php echo esc_url( $dashboard_url ); ?>" data-wizard-finish class="ectbe-btn-ghost">Not now</a>
          </div>
        </div>
      </section>

    </div>
  </main>

</div>
</div>
