<?php

namespace nl\bransom\wordpress;
global $post;

$permalink = get_permalink($post->ID);
$exercise_classes = '';
$image_url = $instance['todo_visual'];
$ace_exercise = AceCourseExercise::wrap($post->ID);
if ($ace_exercise != NULL) {
  $exercise_classes .= $ace_exercise->get_css_classes();
  if ($ace_exercise->get_status() == AceCourseExercise::DONE) {
    $image_url = $instance['done_visual'];
  }
}
if (strpos($image_url, '/') === FALSE) {
  $image_url = ACE_COURSE_PLUGIN_URL . "assets/" . $image_url;
}

echo <<<EOT
<div class="$exercise_classes">
  <a href="$permalink">$post->post_title<img src="$image_url" align="right" style="vertical-align: sub; width: 20px;" /></a>
</div>
EOT;
?>