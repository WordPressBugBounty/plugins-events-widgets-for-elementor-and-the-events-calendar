jQuery(document).ready(function ($) {
  $(document).on("click", ".already_rated_btn", function (e) {
      e.preventDefault();

      var $this = $(this);
      var wrapper = $this.closest("[data-plugin-slug]");

      if (!wrapper.length) return;

      var ajaxURL      = wrapper.data("ajax-url");
      var ajaxCallback = wrapper.data("ajax-callback");
      var slug         = wrapper.data("plugin-slug");
      var id           = wrapper.attr("id");
      var wp_nonce     = wrapper.data("wp-nonce");

      $.post(ajaxURL, {
          action: ajaxCallback,
          slug: slug,
          id: id,
          _nonce: wp_nonce
      }, function () {
        wrapper.slideUp('fast', function () {
            $(this).remove(); // completely remove from DOM
        });
      });
  });
});
