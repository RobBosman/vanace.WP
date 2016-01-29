<?php

/**
 * Ace Course Progress widget
 * @uses WP_Widget
 * @author Rob Bosman
 */

namespace nl\bransom\wordpress;

if (!function_exists('add_action')) {
  wp_die('Unallowed to access this file directly.', 'Direct Access Forbidden', array('response' => '403'));
}

// Register AceCourseProgressWidget
add_action('widgets_init', function() {
  return register_widget('nl\bransom\wordpress\AceCourseProgressWidget');
});

class AceCourseProgressWidget extends \WP_Widget {

  private $is_post_on_its_own_page = TRUE;

  /**
   * Register the widget with WordPress.
   */
  public function __construct() {
    parent::__construct(
            'ace-course-progres-widget', // Base ID
            __('Ace Course Progress', ACE_COURSE_TEXT_DOMAIN), // Name
            array('description' => __('Displays the user\'s progress on Ace Course exercises', ACE_COURSE_TEXT_DOMAIN))
    );
    add_action('the_post', array(&$this, 'patch_post_article_html'));
  }

  public function patch_post_article_html($post) {
    if ($post->post_type == 'page') {
      $this->is_post_on_its_own_page = FALSE;
    }
    return $post;
  }

  /**
   * Front-end display of widget.
   *
   * @see WP_Widget::widget()
   *
   * @param array $args     Widget arguments.
   * @param array $instance Saved values from database.
   */
  public function widget($args, $instance) {
    // Only show the widget if it is on a specific Page, not when just showing a single post.
    if (!$this->is_post_on_its_own_page) {
      return;
    }

    echo $args['before_widget'];

    if (!empty($instance['title'])) {
      $categories = !empty($instance['categories']) ? $instance['categories'] : array();
      $category_slug_first = '';
      $category_ids = array();
      foreach ($categories as $category) {
        if ($category_slug_first == '') {
          $category_slug_first = strtolower(str_replace(' ', '-', $category));
        }
        $category_ids[] = get_cat_ID($category);
      }

      $ace_user = new AceCourseUser();
      echo "<div class='$category_slug_first " . $ace_user->get_css_classes() . "'>";
      echo $args['before_title'] . $instance['title'] . $args['after_title'];

      $query_args = array(
          'post_type' => 'post',
          'post_status' => 'publish',
          'category__in' => $category_ids);
      $exercise_posts = new \WP_Query($query_args);

      global $post;
      foreach ($exercise_posts->posts as $exercise_post) {
        $post = $exercise_post;
        setup_postdata($post);

        include 'ace-course-progress.template.php';

        wp_reset_postdata();
      }
      echo "</div>";
    }
    echo $args['after_widget'];
  }

  /**
   * Back-end widget form.
   *
   * @see WP_Widget::form()
   *
   * @param array $instance Previously saved values from database.
   */
  public function form($instance) {
    $title = !empty($instance['title']) ? $instance['title'] : "";
    $title_label = __('Title:', ACE_COURSE_TEXT_DOMAIN);
    $title_field_name = $this->get_field_name('title');
    $title_value = esc_attr($title);

    $categories = !empty($instance['categories']) ? $instance['categories'] : array();
    $categories_label = __('Categories:', ACE_COURSE_TEXT_DOMAIN);
    $selectable_categories = get_categories(array(
        'type' => 'post',
        'child_of' => 0,
        'parent' => '',
        'hide_empty' => 1,
        'hierarchical' => 1,
        'exclude' => '',
        'include' => '',
        'number' => '',
        'taxonomy' => 'category',
        'pad_counts' => false
    ));
    $categories_field_name = $this->get_field_name('categories');
    $categories_selection = '<table>';
    $i = 0;
    foreach ($selectable_categories as $selectable_category) {
      $category_field_name = $categories_field_name . "[$i]";
      $checked = (in_array($selectable_category->name, $categories) ? " checked='checked'" : "");
      $categories_selection .= "<tr><td>&nbsp;</td><td>";
      $categories_selection .= "<input type='checkbox' name='$category_field_name'" . $checked
              . " value='$selectable_category->name'/>$selectable_category->name";
      $categories_selection .= "</td></tr>";
      $i++;
    }
    $categories_selection .= '</table>';

    $todo_visual = !empty($instance['todo_visual']) ? $instance['todo_visual'] : 'TODO-20.png';
    $todo_visual_label = __('TODO visual URL:', ACE_COURSE_TEXT_DOMAIN);
    $todo_visual_field_name = $this->get_field_name('todo_visual');
    $todo_visual_value = esc_attr($todo_visual);

    $done_visual = !empty($instance['done_visual']) ? $instance['done_visual'] : 'DONE-20.png';
    $done_visual_label = __('DONE visual URL:', ACE_COURSE_TEXT_DOMAIN);
    $done_visual_field_name = $this->get_field_name('done_visual');
    $done_visual_value = esc_attr($done_visual);

    if (!empty($title_value)) {
      // Very dirty trick to append some text to the widget's title.
      echo <<<EOT1
<style type="text/css">
    div[id$='$this->id'] .in-widget-title::after { content: ' - $title_value'; }
</style>
EOT1;
    }

    echo <<<EOT2
<table width="100%">
    <tr>
        <td colspan="2">$title_label<br/>
            <input type="text" class="widefat" name="$title_field_name" value="$title_value" />
        </td>
    </tr>
    <tr>
        <td valign='top'>$categories_label</td>
        <td width='100%'>$categories_selection</td>
    </tr>
    <tr>
        <td colspan="2">$todo_visual_label<br/>
            <input type="text" class="widefat" name="$todo_visual_field_name" value="$todo_visual_value" />
        </td>
    </tr>
    <tr>
        <td colspan="2">$done_visual_label<br/>
            <input type="text" class="widefat" name="$done_visual_field_name" value="$done_visual_value" />
        </td>
    </tr>
</table>
EOT2;
  }

  /**
   * Sanitize widget form values as they are saved.
   *
   * @see WP_Widget::update()
   *
   * @param array $new_instance Values just sent to be saved.
   * @param array $old_instance Previously saved values from database.
   *
   * @return array Updated safe values to be saved.
   */
  public function update($new_instance, $old_instance) {
    $instance = array();
    $instance['title'] = !empty($new_instance['title']) ? strip_tags($new_instance['title']) : '';
    $instance['categories'] = !empty($new_instance['categories']) ? $new_instance['categories'] : array();
    $instance['todo_visual'] = !empty($new_instance['todo_visual']) ? strip_tags($new_instance['todo_visual']) : '';
    $instance['done_visual'] = !empty($new_instance['done_visual']) ? strip_tags($new_instance['done_visual']) : '';
    return $instance;
  }
}