<?php

/**
 * Ace Course plugin
 * @author Rob Bosman
 */

namespace nl\bransom\wordpress;

if (!function_exists('add_action')) {
  wp_die('Unallowed to access this file directly.', 'Direct Access Forbidden', array('response' => '403'));
}

// Instantiate the class.
$aceCoursePlugin = new AceCoursePlugin();

class AceCoursePlugin {

  public function __construct() {
    // Register the plugin.
    add_action('plugins_loaded', function() { $this; });
    add_action('login_form', array(&$this, 'login_form'));
    add_action('login_enqueue_scripts', array(&$this, 'register_styles'));
    add_action('wp_enqueue_scripts', array(&$this, 'register_styles'));
    // Define the text domain.
    load_plugin_textdomain(ACE_COURSE_TEXT_DOMAIN, false, ACE_COURSE_PLUGIN_DIR . '/languages/');
    // Add settings to the 'Comments' metabox.
    add_action('admin_init', array(&$this, 'init_admin_settings'));
  }

  public function init_admin_settings() {
    $hooks = array('post', 'page');
    foreach ($hooks as $hook) {
      add_meta_box('ace_course_settings_meta', __('Ace Course', ACE_COURSE_TEXT_DOMAIN),
              array(&$this, 'add_settings_metabox'), $hook, 'normal', 'high');
    }
  }

  public function login_form() {
    echo <<<EOT1
<div id="ace-login-button" onclick="document.getElementById('loginform').submit();"></div>
EOT1;
  }

  public function register_styles() {
    wp_register_style('ace-course-style', plugins_url('/assets/style.css', realpath(dirname(__FILE__))));
    wp_enqueue_style('ace-course-style');
  }

  public function add_settings_metabox($post) {
    $checked = AceCourseExercise::is_exercise($post->ID) ? 'checked="checked" ' : '';
    if ($post->post_type == 'page') {
      $label = __('Only show the user\'s own comments on this page', ACE_COURSE_TEXT_DOMAIN);
    } else { // 'post'
      $label = __('Treat comments as answers to an <strong>Ace Course exercise</strong>; only show the user\'s own comments', ACE_COURSE_TEXT_DOMAIN);
    }
    $exercise_id = AceCourseExercise::TAG_EXERCISE;
    $nonce_id = AceCourseCore::TAG_NONCE;
    $nonce = AceCourseCore::get_nonce();

    echo <<<EOT2
<div>
    <label for="$exercise_id"><input type="checkbox" id="$exercise_id" name="$exercise_id" value="1" $checked />$label</label>
    <input type="hidden" name="$nonce_id" value="$nonce" />
</div>
EOT2;
  }
}