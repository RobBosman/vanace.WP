<?php

/*
  Plugin Name: Ace Course
  Plugin URI: http://www.google.com/
  Description: <strong>Ace Course</strong> plugin featuring 'exercise posts'. Comments to such posts (the 'answers') are visible only for the user who wrote them. A Course Progress widget displays the user's progress.
  Version: 3.1.1
  Author: Rob Bosman
  Author URI: http://www.google.com
  Text Domain: ace-course
  Domain Path: /languages
 */

namespace nl\bransom\wordpress;

if (!function_exists('add_action')) {
  wp_die('It is not allowed to access this file directly.', 'Direct Access Forbidden', array('response' => '403'));
}

if (!defined('ACE_COURSE')) {
  define('ACE_COURSE', plugin_basename(__FILE__));
  define('ACE_COURSE_TEXT_DOMAIN', 'ace-course');
  define('ACE_COURSE_PLUGIN_URL', plugin_dir_url(__FILE__));
  define('ACE_COURSE_PLUGIN_DIR', dirname(constant('ACE_COURSE')));
}

require_once plugin_dir_path(__FILE__) . 'includes' . DIRECTORY_SEPARATOR . 'AceCourseUser.class.php';
require_once plugin_dir_path(__FILE__) . 'includes' . DIRECTORY_SEPARATOR . 'AceCourseExercise.class.php';
require_once plugin_dir_path(__FILE__) . 'includes' . DIRECTORY_SEPARATOR . 'AceCourseCore.class.php';
require_once plugin_dir_path(__FILE__) . 'includes' . DIRECTORY_SEPARATOR . 'AceCoursePlugin.class.php';
require_once plugin_dir_path(__FILE__) . 'widgets' . DIRECTORY_SEPARATOR . 'AceCourseProgressWidget.class.php';