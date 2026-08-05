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
    return [
      'E-Commerce' => [
        [
          'name'         => 'WooCommerce Single Add To Cart',
          'trigger_type' => 'click',
          'selector'     => '.single_add_to_cart_button',
          'event_name'   => 'AddToCart',
          'platforms'    => ['facebook', 'tiktok', 'ga4'],
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
          'platforms'    => ['facebook', 'tiktok', 'ga4'],
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
          'platforms'    => ['facebook', 'tiktok', 'ga4'],
          'parameters'   => [
            ['key' => 'currency', 'value_type' => 'static', 'value_source' => 'USD']
          ]
        ]
      ],
      'Lead-Gen' => [
        [
          'name'         => 'Generic Form Submission',
          'trigger_type' => 'submit',
          'selector'     => 'form',
          'event_name'   => 'Lead',
          'platforms'    => ['facebook', 'tiktok', 'google_ads', 'ga4'],
          'parameters'   => []
        ],
        [
          'name'         => 'Contact Form 7 Submit',
          'trigger_type' => 'submit',
          'selector'     => '.wpcf7-form',
          'event_name'   => 'Contact',
          'platforms'    => ['facebook', 'ga4'],
          'parameters'   => []
        ],
        [
          'name'         => 'WPForms Form Submit',
          'trigger_type' => 'submit',
          'selector'     => '.wpforms-form',
          'event_name'   => 'Lead',
          'platforms'    => ['facebook', 'tiktok', 'ga4'],
          'parameters'   => []
        ],
        [
          'name'         => 'Click Phone Link',
          'trigger_type' => 'click',
          'selector'     => 'a[href^="tel:"]',
          'event_name'   => 'Contact',
          'platforms'    => ['facebook', 'ga4'],
          'parameters'   => [
            ['key' => 'link_text', 'value_type' => 'innerText', 'value_source' => '']
          ]
        ],
        [
          'name'         => 'Click Email Link',
          'trigger_type' => 'click',
          'selector'     => 'a[href^="mailto:"]',
          'event_name'   => 'Contact',
          'platforms'    => ['facebook', 'ga4'],
          'parameters'   => [
            ['key' => 'link_text', 'value_type' => 'innerText', 'value_source' => '']
          ]
        ]
      ],
      'LMS' => [
        [
          'name'         => 'TutorLMS Lesson Completed',
          'trigger_type' => 'click',
          'selector'     => '.tutor-btn-complete',
          'event_name'   => 'CompleteRegistration',
          'platforms'    => ['facebook', 'ga4'],
          'parameters'   => []
        ],
        [
          'name'         => 'LearnDash Mark Complete',
          'trigger_type' => 'click',
          'selector'     => '.learndash_mark_complete_button',
          'event_name'   => 'CompleteRegistration',
          'platforms'    => ['facebook', 'ga4'],
          'parameters'   => []
        ]
      ],
      'Theme-Specific' => [
        [
          'name'         => 'Elementor Form Submit',
          'trigger_type' => 'submit',
          'selector'     => '.elementor-form',
          'event_name'   => 'Lead',
          'platforms'    => ['facebook', 'tiktok', 'google_ads', 'ga4'],
          'parameters'   => []
        ],
        [
          'name'         => 'Divi Button Click',
          'trigger_type' => 'click',
          'selector'     => '.et_pb_button',
          'event_name'   => 'Custom_Click',
          'platforms'    => ['facebook', 'ga4'],
          'parameters'   => [
            ['key' => 'button_label', 'value_type' => 'innerText', 'value_source' => '']
          ]
        ],
        [
          'name'         => 'Gutenberg Button Link Click',
          'trigger_type' => 'click',
          'selector'     => '.wp-block-button__link',
          'event_name'   => 'Custom_Click',
          'platforms'    => ['facebook', 'ga4'],
          'parameters'   => [
            ['key' => 'label', 'value_type' => 'innerText', 'value_source' => '']
          ]
        ]
      ]
    ];
  }
}
