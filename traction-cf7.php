<?php
/**
 * Traction CF7
 *
 *
 * @link
 * @since             0.0.6
 * @package           traction_cf7
 *
 * @wordpress-plugin
 * Plugin Name:       Traction CF7
 * Plugin URI:        https://github.com/traction-app/traction-cf7
 * Description:       Plugin to send contacts from CF7 to Traction Leads
 * Version:           0.0.6
 * Author:            Traction
 * Author URI: 		  	https://traction.to
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

define('PLUGIN_BASE_FILE', plugin_basename( __FILE__ ));

/**
 * Get tags from WPCF7 Mail
 *
 * @param [type] $post
 * @return void
 */
function get_mail_tags($post)
{
    $tags = apply_filters('qs_cf7_collect_mail_tags', $post->scan_form_tags());

    foreach ((array) $tags as $tag) {
        $type = trim($tag['type'], ' *');
        if (empty($type) || empty($tag['name'])) {
            continue;
        } elseif (! empty($args['include'])) {
            if (! in_array($type, $args['include'])) {
                continue;
            }
        } elseif (! empty($args['exclude'])) {
            if (in_array($type, $args['exclude'])) {
                continue;
            }
        }
        $mailtags[] = $tag;
    }

    return $mailtags;
}

/**
 * Adds a new tab on conract form 7 screen
 * @param [type] $panels [description]
 */
function add_integrations_tab($panels)
{
    $integration_panel = array(
        'title'    => __('Integração Traction'),
        'callback' => function ($post) {
            $path = plugin_dir_path(__FILE__);
            set_query_var('post', $post);
            load_template($path . '/template/tab.php');
            return $post;
        }
    );
    $panels["traction-cf7"] = $integration_panel;
    return $panels;
}


/**
 * Saves the API settings
 * @param  [type] $contact_form [description]
 * @return [type]               [description]
 */
function save_integrations_tab($contact_form)
{
    return $contact_form->set_properties([
        'wpcf7_api_data' => isset($_POST["wpcf7-sf"]) ? $_POST["wpcf7-sf"] : ''
    ]);
}


/**
 * Sets the form additional properties
 * @param [type] $properties   [description]
 * @param [type] $contact_form [description]
 */
function set_additional_properties($properties, $contact_form)
{
    $properties["wpcf7_api_data"] = isset($properties["wpcf7_api_data"]) ? $properties["wpcf7_api_data"] : array();
    return $properties;
}


/**
 * Build an array with form fields
 *
 * @param [type] $form
 * @return array
 */
function build_wpcf7_fields($form)
{
    $data = $form->prop('wpcf7_api_data');
    $map_fields = $data['map_fields'];
    $has_mapped_fields = $data['has_mapped_fields'] == 'on';

    $payload = array();
    $tags = get_mail_tags($form);
    foreach ($tags as $tag) {
        $idx = $tag['name'];
        if ($has_mapped_fields) {
            $payload[$map_fields[$idx]] = $_POST[$idx];
        } else {
            $payload[$idx] = $_POST[$idx];
        }
    }

    return array(
        'endpoint' => $data['endpoint'],
        'payload' => $payload
    );
}

/**
 * Get Current Page Full URL
 *
 * @return string
 */
function get_full_url()
{
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $link = "https";
    } else {
        $link = "http";
    }
    $link .= "://";
    $link .= $_SERVER['HTTP_HOST'];
    $link .= $_SERVER['REQUEST_URI'];
    return $link;
}

/**
 * Send Contact Form fields throught a POST request
 *
 * @param [type] $WPCF7_ContactForm
 * @return void
 */
function send_request_to_api($WPCF7_ContactForm)
{
    $data = build_wpcf7_fields($WPCF7_ContactForm);

    wp_remote_post(
        $data['endpoint'],
        array(
                    'method' => 'POST',
                    'sslverify' => false,
                    'headers' => array(
                        'Content-Type' => 'application/json',
                        'Referer' => $_SERVER['HTTP_REFERER']
                    ),
                    'body' => json_encode($data['payload'])
        )
    );
}


/**
 * Check if CF7 plugin is active
 *
 * @return boolean
 */
function is_cf7_active()
{
    $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
    foreach ($active_plugins as $plugin) {
        if (strpos($plugin, 'wp-contact-form-7')) {
            return true;
        }
    }
}


/**
 * Show a warning message if cf7 plugin is not active
 *
 * @return void
 */
function show_cf7_plugin_missing_warning()
{
    $class = 'notice notice-warning is-dismissible';
    $message = __('Atenção! Você precisa do Contact Form 7 ativado para o Traction Leads funcionar!', 'traction-cf7');
    if (!is_cf7_active()) {
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }
}
add_action('admin_notices', 'show_cf7_plugin_missing_warning');


// Add actions and filters
add_filter('wpcf7_editor_panels', 'add_integrations_tab', 1, 1);
add_filter("wpcf7_contact_form_properties", 'set_additional_properties', 10, 2);
add_filter('wpcf7_pre_construct_contact_form_properties', 'set_additional_properties', 10, 2 );
add_action("wpcf7_save_contact_form", 'save_integrations_tab', 10, 1);
add_action('wpcf7_before_send_mail', 'send_request_to_api');

require_once __DIR__ . '/inc/updater.php';