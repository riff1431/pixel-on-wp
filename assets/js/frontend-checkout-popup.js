jQuery(document).ready(function($) {
  // Listen for WooCommerce checkout errors
  $(document.body).on('checkout_error', function() {
    var fraudData = $('.pp-fraud-checkout-block-data');
    if (fraudData.length > 0) {
      var message = fraudData.data('message');
      var whatsapp = fraudData.data('whatsapp');
      var title = fraudData.data('title') || 'Checkout Blocked';
      var showWa = fraudData.data('show-wa') == 1;
      var showCall = fraudData.data('show-call') == 1;
      
      showFraudPopup(title, message, whatsapp, showWa, showCall);
    }
  });

  function showFraudPopup(title, message, whatsapp, showWa, showCall) {
    if ($('#pp-fraud-popup-overlay').length === 0) {
      var btnHTML = '';
      if (whatsapp) {
        if (showWa) {
          btnHTML += '<a href="https://wa.me/' + whatsapp + '" target="_blank" class="pp-fraud-popup-btn">';
          btnHTML += '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>';
          btnHTML += 'Contact on WhatsApp</a>';
        }
        if (showCall) {
          var phoneOnly = whatsapp.replace('880', '0');
          btnHTML += '<a href="tel:' + phoneOnly + '" class="pp-fraud-popup-btn phone">';
          btnHTML += '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/></svg>';
          btnHTML += 'Call Support</a>';
        }
      }

      var html = `
        <div id="pp-fraud-popup-overlay" class="pp-fraud-popup-overlay">
          <div class="pp-fraud-popup-box">
            <button class="pp-fraud-popup-close">&times;</button>
            <div class="pp-fraud-popup-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <div class="pp-fraud-popup-title">${title}</div>
            <div class="pp-fraud-popup-message">${message}</div>
            ${btnHTML}
          </div>
        </div>
      `;
      $('body').append(html);

      $('#pp-fraud-popup-overlay .pp-fraud-popup-close, #pp-fraud-popup-overlay').on('click', function(e) {
        if (e.target === this) {
          $('#pp-fraud-popup-overlay').removeClass('pp-active');
        }
      });
    }

    // Use a tiny timeout to allow the browser to render the DOM node before adding the active class for the transition
    setTimeout(function() {
      $('#pp-fraud-popup-overlay').addClass('pp-active');
    }, 10);
  }
});
