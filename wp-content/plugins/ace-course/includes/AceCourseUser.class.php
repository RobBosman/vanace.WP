<?php

/**
 * Defines Ace Course users
 * @author Rob Bosman
 */

namespace nl\bransom\wordpress;

class AceCourseUser {
  
  const CORE_TEACHER = 0;
  const TEACHER = 1;
  const TRAINEE = 2;
  
  private static $ROLE_MAPPING = array(
      "administrator" => self::CORE_TEACHER,
      "sub-admin"     => self::CORE_TEACHER,
      "docent"        => self::TEACHER,
      NULL            => self::TRAINEE
      );
  
  private $user;
  private $ace_role;
  private $bp_group_ids; // BuddyPress group ID's; lazy fetch

  public function __construct($user = NULL) {
	  $this->user = $user;
	  if ($this->user != NULL || function_exists("wp_get_current_user")) {
      $this->init();
    } else {
      add_action('plugins_loaded', array(&$this, 'init'));
    }
  }

  public function init() {
	  if ($this->user == NULL) {
      $this->user = wp_get_current_user();
    }
    
    $wp_role = isset($this->user->roles[0]) ? $this->user->roles[0] : NULL;
    if (isset(self::$ROLE_MAPPING[$wp_role])) {
      $this->ace_role = self::$ROLE_MAPPING[$wp_role];
    } else {
      $this->ace_role = self::TRAINEE;
    }
  }
  
  public function is_teacher() {
    return $this->ace_role == self::CORE_TEACHER || $this->ace_role == self::TEACHER;
  }
  
  public function is_comment_visible($comment) {
    if ($this->ace_role == self::CORE_TEACHER) {
      return TRUE;
    }
    
    $comment_user_id = is_array($comment) ? $comment['user_id'] : $comment->user_id;
    if ($this->user->ID == $comment_user_id) {
      return TRUE; // own comment
    }
    
    if ($this->is_teacher()) {
      if (function_exists ("groups_get_groups")) {
        // Check if teacher shares one or more groups with comment owner.
        $common_bp_groups = groups_get_groups(array(
          'user_id'           => $comment_user_id,
          'include'           => $this->get_bp_group_ids(),
          'show_hidden'       => TRUE,
          'populate_extras'   => FALSE,
          'update_meta_cache' => FALSE,
        ));
        return $common_bp_groups['total'] > 0;
      } else {
        return TRUE; // BuddyPress not installed
      }
    }
    
    return FALSE;
  }

  public function add_css_classes(array &$classes) {
    if ($this->ace_role == self::CORE_TEACHER) {
      $classes[] = 'ace-core-teacher';
      $classes[] = 'ace-teacher';
    } else if ($this->ace_role == self::TEACHER) {
      $classes[] = 'ace-teacher';
    } else if ($this->ace_role == self::TRAINEE) {
      $classes[] = 'ace-trainee';
    }
  }

  public function get_css_classes() {
    $classes = array();
    $this->add_css_classes($classes);
	  return implode(' ', $classes);
  }

  private function get_bp_group_ids() {
    if ($this->bp_group_ids == NULL) {
      if (function_exists ("groups_get_user_groups")) {
        $this->bp_group_ids = array_shift(groups_get_user_groups($this->user->ID));
      } else {
        $this->bp_group_ids = array(); // BuddyPress not installed
      }
    }
	  return $this->bp_group_ids;
  }
}
