<?php

/**
 * Ace Course core functionality
 * @author Rob Bosman
 */

namespace nl\bransom\wordpress;

if (!function_exists('add_action')) {
  wp_die('Unallowed to access this file directly.', 'Direct Access Forbidden', array('response' => '403'));
}

// Instantiate the class.
new AceCourseCore();

class AceCourseCore {

  const TAG_NONCE = 'ace_nonce';
  
  private $ace_user;

  public function __construct() {
    $this->ace_user = new AceCourseUser();
    
    add_action('save_post', array(&$this, 'update_post'));
    add_action('edit_post', array(&$this, 'update_post'));
    add_action('publish_post', array(&$this, 'update_post'));
    add_filter('comments_array', array(&$this, 'filter_comments'), 1000, 2);
    add_filter('comments_filter', array(&$this, 'filter_comments'));
    add_filter('the_comments', array(&$this, 'filter_comments'));
    add_filter('get_comment', array(&$this, 'filter_comment'));
    add_filter('get_comments_number', array(&$this, 'count_filtered_comments'));
    add_filter('comment_class', array(&$this, 'filter_comment_css_class'));
    add_action('post_class', array(&$this, 'add_exercise_css_classes'));
  }

  public static function get_nonce() {
    // Returns the nonce that we use to defend ourself against guessing attacks. 
    $nonce = get_option(self::TAG_NONCE);
    if (is_null($nonce) || strlen($nonce) == 0) {
      $nonce = crc32(time()
              . $_SERVER['QUERY_STRING']
              . $_SERVER['REMOTE_ADDR'] // Gebruik hier NIET de functie filter_input(), zie https://bugs.php.net/bug.php?id=49184.
              . $_SERVER['SCRIPT_FILENAME']);
      update_option(self::TAG_NONCE, $nonce);
    }
    return $nonce;
  }

  public function update_post($post_id) {
    if (current_user_can('edit_post', $post_id) && filter_input(INPUT_POST, self::TAG_NONCE) == self::get_nonce()) {
      AceCourseExercise::make_exercise($post_id);
    }
    return $post_id;
  }

  public function filter_comments($comments, $postID = NULL) {
    global $post;
    if ($postID == NULL) {
      $postID = $post->ID;
    }
    // If the current post is an exercise, then remove comments that may not be seen by the current user.
    if (AceCourseExercise::is_exercise($postID)) {
      $filteredComments = array();
      foreach ($comments as $comment) {
        if ($this->ace_user->is_comment_visible($comment)) {
          $filteredComments[] = $comment;
        }
      }
      $post->comment_count = count($filteredComments);
      $comments = $filteredComments;
    }

    // Sort all comments.
    usort($comments, array(&$this, 'compare_comments_by_date'));

    return $comments;
  }

  public function filter_comment($comment) {
    // If the current post is an exercise, then remove comments that may not be seen by the current user.
    if (AceCourseExercise::is_exercise($comment->comment_post_ID)) {
      if ($this->ace_user->is_comment_visible($comment)) {
        return $comment;
      }
    }
    return FALSE;
  }

  public function compare_comments_by_date($commentA, $commentB) {
    // Compare the comments by post date, then by comment date, both oldest first.
    if ($commentA == $commentB) {
      return 0;
    }
    $postA = get_post($commentA->comment_post_ID);
    $postB = get_post($commentB->comment_post_ID);
    // Compare post dates.
    if ($postA->post_date < $postB->post_date) {
      return -1;
    } else if ($postA->post_date > $postB->post_date) {
      return 1;
    } else if ($commentA->comment_date < $commentB->comment_date) {
      return -1;
    } else if ($commentA->comment_date > $commentB->comment_date) {
      return 1;
    } else {
      return 0;
    }
  }

  public function count_filtered_comments($count) {
    global $post;
    if ($count > 0 && AceCourseExercise::is_exercise($post->ID)) {
      $comments = get_comments(array('post_id' => $post->ID));
      $filteredCommets = $this->filter_comments($comments);
      $count = count($filteredCommets);
    }
    return $count;
  }

  public function filter_comment_css_class($classes) {
    $this->ace_user->add_css_classes($classes);
    return $classes;
  }

  public function add_exercise_css_classes($classes) {
    if (in_array('type-post', $classes)) {
      global $post;
      $ace_exercise = AceCourseExercise::wrap($post->ID);
      if ($ace_exercise != NULL) {
        $this->ace_user->add_css_classes($classes);
        $ace_exercise->add_css_classes($classes);
      }
    }
    return $classes;
  }
}