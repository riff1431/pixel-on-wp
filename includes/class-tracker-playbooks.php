<?php
/**
 * Playbook Presets Engine Class.
 *
 * Contains categorized playbook presets for 1-click apply setup.
 *
 * @package PixelOnWP\Includes
 * @since 1.2.0
 */

namespace PixelOnWP\Includes;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

class PixelOnWP_Tracker_Playbooks
{
  /**
   * Retrieve all playbook presets categorized by business model and builders.
   *
   * @return array
   */
  public static function get_playbooks(): array
  {
    $all_platforms = ['facebook', 'tiktok', 'ga4', 'google_ads', 'pinterest', 'reddit'];

    return [
      'E-Commerce' => [
        [
          'name'         => 'WooCommerce Single Add To Cart',
          'trigger_type' => 'click',
          'selector'     => '.single_add_to_cart_button',
          'event_name'   => 'AddToCart',
          'platforms'    => $all_platforms,
          'parameters'   => [
            ['key' => 'currency', 'value_type' => 'static', 'value_source' => 'USD'],
            ['key' => 'value', 'value_type' => 'attribute', 'value_source' => 'value']
          ]
        ],
        [
          'name'         => 'WooCommerce AJAX Add To Cart',
          'trigger_type' => 'click',
          'selector'     => '.ajax_add_to_cart',
          'event_name'   => 'AddToCart',
          'platforms'    => $all_platforms,
          'parameters'   => [
            ['key' => 'currency', 'value_type' => 'static', 'value_source' => 'USD'],
            ['key' => 'value', 'value_type' => 'attribute', 'value_source' => 'data-product_sku']
          ]
        ],
        [
          'name'         => 'EDD Purchase Button Click',
          'trigger_type' => 'click',
          'selector'     => '.edd-add-to-cart',
          'event_name'   => 'AddToCart',
          'platforms'    => $all_platforms,
          'parameters'   => [
            ['key' => 'currency', 'value_type' => 'static', 'value_source' => 'USD']
          ]
        ],
        [
          'name'         => 'Checkout Initiated',
          'trigger_type' => 'click',
          'selector'     => '.checkout_button, .wc-forward[href*="checkout"], button[name="checkout"]',
          'event_name'   => 'InitiateCheckout',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ],
        [
          'name'         => 'Cart Drawer / View Cart Click',
          'trigger_type' => 'click',
          'selector'     => '.widget_shopping_cart_content, a.added_to_cart, .cart-contents',
          'event_name'   => 'ViewCart',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ],
        [
          'name'         => 'Wishlist Add',
          'trigger_type' => 'click',
          'selector'     => '.yith-wcwl-add-to-cart a, .add_to_wishlist, .tinvwl_add_to_wishlist_button',
          'event_name'   => 'AddToWishlist',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ],
        [
          'name'         => 'PayPal / Express Checkout',
          'trigger_type' => 'click',
          'selector'     => '#paypal-button-container, .payment_method_paypal, .stripe-button-el',
          'event_name'   => 'AddPaymentInfo',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ],
        [
          'name'         => 'Search Executed',
          'trigger_type' => 'submit',
          'selector'     => 'form.woocommerce-product-search, form.search-form',
          'event_name'   => 'Search',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ]
      ],
      'Lead-Gen & Forms' => [
        [
          'name'         => 'Generic Form Submission',
          'trigger_type' => 'submit',
          'selector'     => 'form',
          'event_name'   => 'Lead',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ],
        [
          'name'         => 'Contact Form 7 Submit',
          'trigger_type' => 'submit',
          'selector'     => '.wpcf7-form',
          'event_name'   => 'Contact',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ],
        [
          'name'         => 'WPForms Form Submit',
          'trigger_type' => 'submit',
          'selector'     => '.wpforms-form',
          'event_name'   => 'Lead',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ],
        [
          'name'         => 'Gravity Forms Submit',
          'trigger_type' => 'submit',
          'selector'     => '.gform_wrapper form, .gform_button',
          'event_name'   => 'Lead',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ],
        [
          'name'         => 'Elementor Form Submit',
          'trigger_type' => 'submit',
          'selector'     => '.elementor-form button[type="submit"], .elementor-form',
          'event_name'   => 'Lead',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ],
        [
          'name'         => 'Fluent Forms Submit',
          'trigger_type' => 'submit',
          'selector'     => '.frm-fluent-form',
          'event_name'   => 'Lead',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ],
        [
          'name'         => 'Ninja Forms Submit',
          'trigger_type' => 'click',
          'selector'     => '.nf-form-content input[type="button"], .nf-form-content button',
          'event_name'   => 'Lead',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ],
        [
          'name'         => 'Calendly / Meeting Embed Click',
          'trigger_type' => 'click',
          'selector'     => 'iframe[src*="calendly.com"], a[href*="calendly.com"]',
          'event_name'   => 'Schedule',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ],
        [
          'name'         => 'WhatsApp / Live Chat Click',
          'trigger_type' => 'click',
          'selector'     => 'a[href*="wa.me"], a[href*="api.whatsapp.com"], .joinchat, #tawk-header',
          'event_name'   => 'Contact',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ],
        [
          'name'         => 'Click Phone Link',
          'trigger_type' => 'click',
          'selector'     => 'a[href^="tel:"]',
          'event_name'   => 'Contact',
          'platforms'    => $all_platforms,
          'parameters'   => [
            ['key' => 'link_text', 'value_type' => 'innerText', 'value_source' => '']
          ]
        ],
        [
          'name'         => 'Click Email Link',
          'trigger_type' => 'click',
          'selector'     => 'a[href^="mailto:"]',
          'event_name'   => 'Contact',
          'platforms'    => $all_platforms,
          'parameters'   => [
            ['key' => 'link_text', 'value_type' => 'innerText', 'value_source' => '']
          ]
        ]
      ],
      'Media & Engagement' => [
        [
          'name'         => 'PDF / File Download',
          'trigger_type' => 'click',
          'selector'     => 'a[href$=".pdf"], a[href$=".zip"], a[href$=".docx"]',
          'event_name'   => 'FileDownload',
          'platforms'    => $all_platforms,
          'parameters'   => [
            ['key' => 'file_name', 'value_type' => 'attribute', 'value_source' => 'href']
          ]
        ],
        [
          'name'         => 'Embedded Video Play',
          'trigger_type' => 'click',
          'selector'     => 'iframe[src*="youtube.com"], iframe[src*="vimeo.com"], video',
          'event_name'   => 'VideoStart',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ],
        [
          'name'         => 'Copy Code / Coupon Click',
          'trigger_type' => 'click',
          'selector'     => '.coupon-code, .copy-trigger, [data-clipboard-text]',
          'event_name'   => 'Custom_Coupon_Click',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ]
      ],
      'Affiliate & Outbound' => [
        [
          'name'         => 'External Link Click',
          'trigger_type' => 'click',
          'selector'     => 'a[rel*="sponsored"], a[rel*="nofollow"], a[class*="affiliate"]',
          'event_name'   => 'Custom_Affiliate_Click',
          'platforms'    => $all_platforms,
          'parameters'   => [
            ['key' => 'outbound_url', 'value_type' => 'attribute', 'value_source' => 'href']
          ]
        ],
        [
          'name'         => 'ThirstyAffiliates / Pretty Links',
          'trigger_type' => 'click',
          'selector'     => 'a[href*="/refer/"], a[href*="/go/"], a[href*="/out/"]',
          'event_name'   => 'Custom_Affiliate_Redirect',
          'platforms'    => $all_platforms,
          'parameters'   => [
            ['key' => 'target_link', 'value_type' => 'attribute', 'value_source' => 'href']
          ]
        ]
      ],
      'LMS & Membership' => [
        [
          'name'         => 'LifterLMS / MemberPress Join',
          'trigger_type' => 'click',
          'selector'     => '.llms-button-primary, .mepr-submit',
          'event_name'   => 'CompleteRegistration',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ],
        [
          'name'         => 'Course Quiz Submission',
          'trigger_type' => 'click',
          'selector'     => '.tutor-quiz-submit-button, .learndash_quiz_front',
          'event_name'   => 'SubmitQuiz',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ],
        [
          'name'         => 'TutorLMS Lesson Completed',
          'trigger_type' => 'click',
          'selector'     => '.tutor-btn-complete',
          'event_name'   => 'CompleteRegistration',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ],
        [
          'name'         => 'LearnDash Mark Complete',
          'trigger_type' => 'click',
          'selector'     => '.learndash_mark_complete_button',
          'event_name'   => 'CompleteRegistration',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ]
      ],
      'Theme-Specific' => [
        [
          'name'         => 'Elementor Form Submit',
          'trigger_type' => 'submit',
          'selector'     => '.elementor-form',
          'event_name'   => 'Lead',
          'platforms'    => $all_platforms,
          'parameters'   => []
        ],
        [
          'name'         => 'Divi Button Click',
          'trigger_type' => 'click',
          'selector'     => '.et_pb_button',
          'event_name'   => 'Custom_Click',
          'platforms'    => $all_platforms,
          'parameters'   => [
            ['key' => 'button_label', 'value_type' => 'innerText', 'value_source' => '']
          ]
        ],
        [
          'name'         => 'Gutenberg Button Link Click',
          'trigger_type' => 'click',
          'selector'     => '.wp-block-button__link',
          'event_name'   => 'Custom_Click',
          'platforms'    => $all_platforms,
          'parameters'   => [
            ['key' => 'label', 'value_type' => 'innerText', 'value_source' => '']
          ]
        ]
      ]
    ];
  }
}
