/**
 * PixelOnWP - Admin Dashboard JavaScript
 *
 * Handles interactive tabs, AJAX settings saves, and dashboard notifications.
 *
 * @package PixelOnWP
 * @since 1.0.0
 */

(function ($) {
  'use strict';

  $(document).ready(function () {
    // Handle tab switching in dashboard/settings
    $('.wpt-tab-link').on('click', function (e) {
      e.preventDefault();
      var targetTab = $(this).attr('href');

      $('.wpt-tab-link').removeClass('active');
      $(this).addClass('active');

      $('.wpt-tab-pane').hide();
      $(targetTab).fadeIn(200);
    });

    // Handle AJAX form submissions for settings
    $('#wpt-settings-form').on('submit', function (e) {
      e.preventDefault();

      var $form = $(this);
      var $submitBtn = $form.find('button[type="submit"]');
      var originalText = $submitBtn.text();

      $submitBtn.prop('disabled', true).text('Saving...');

      var formData = $form.serialize();

      $.ajax({
        url: pixelonwp_admin_vars.ajax_url,
        type: 'POST',
        data: formData + '&action=pixelonwp_save_settings&nonce=' + pixelonwp_admin_vars.nonce,
        success: function (response) {
          if (response.success) {
            showNotice('Settings saved successfully.', 'success');
          } else {
            showNotice(response.data.message || 'Error saving settings.', 'error');
          }
        },
        error: function () {
          showNotice('A network error occurred. Please try again.', 'error');
        },
        complete: function () {
          $submitBtn.prop('disabled', false).text(originalText);
        }
      });
    });

    function showNotice(message, type) {
      var $notice = $('<div class="wpt-notice wpt-notice-' + type + '">' + message + '</div>');
      $('.wpt-wrap').prepend($notice);
      setTimeout(function () {
        $notice.fadeOut(300, function () {
          $(this).remove();
        });
      }, 4000);
    }
  });

})(jQuery);