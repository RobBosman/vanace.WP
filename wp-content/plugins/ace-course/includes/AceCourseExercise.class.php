<?php

/**
 * Status-enum of Ace Course exercises
 * @author Rob Bosman
 */

namespace nl\bransom\wordpress;

class AceCourseExercise {
  
  const TAG_EXERCISE = '_ace_course_exercise';
  const TODO = 0;
  const DONE = 1;
  
  public static function is_exercise($post_id) {
    return (bool) get_post_meta($post_id, self::TAG_EXERCISE, true);
  }

  public static function make_exercise($post_id) {
    $is_exercise = (filter_input(INPUT_POST, AceCourseExercise::TAG_EXERCISE) == '1') ? 1 : 0;
    delete_post_meta($post_id, AceCourseExercise::TAG_EXERCISE);
    add_post_meta($post_id, AceCourseExercise::TAG_EXERCISE, $is_exercise);
  }
  
  public static function wrap($post_id) {
    return self::is_exercise($post_id) ? new AceCourseExercise($post_id) : NULL;
  }
  
  private $status;

  private function __construct($post_id) {
    $current_user = wp_get_current_user();
    $comment_count = get_comments(array(
        'post_id' => $post_id,
        'user_id' => $current_user->ID,
        'count' => true));
    $this->status = $comment_count > 0 ? self::DONE : self::TODO;
  }

  public function get_status() {
    return $this->status;
  }

  public function add_css_classes(array &$classes) {
    $classes[] = 'ace-exercise';
	  if ($this->status == self::TODO) {
      $classes[] = 'ace-exercise-todo';
    } else if ($this->status == self::DONE) {
      $classes[] = 'ace-exercise-done';
    }
  }

  public function get_css_classes() {
    $classes = array();
    $this->add_css_classes($classes);
	  return implode(' ', $classes);
  }
}
