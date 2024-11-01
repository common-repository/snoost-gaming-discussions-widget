<?php 

/**
	 * Plugin Name: Snoost: Gaming Discussions Widget
	 * Description: Get the latest discussions and threads from the gaming community in the official Snoost forums.
	 * Version: 0.1.2
	 * Author: Snoost
	 * Author URI: https://www.snoost.com/
	 * License: GPL2
 */

$widgetName = 'snoost_forum_gaming_widget';

function snoost_forum_gaming_widget_load() { register_widget('snoost_forum_gaming_widget'); }
add_action('widgets_init', 'snoost_forum_gaming_widget_load');

class snoost_forum_gaming_widget extends WP_Widget {

  function __construct() {
    parent::__construct(
      'snoost_forum_gaming_widget', 
      __('Snoost Gaming Discussions', 'snoost_forum_gaming_widget_title'), 
      ['description' => __('Get the latest gaming discussions and forum posts from the Snoost gaming community.', 'snoost_forum_gaming_widget_title')]
    );
  }
  private function loadVariables($instance) {
    if(isset($instance['title'])) { $instance['title'] = $instance['title']; }
    else { $instance['title'] = __('Gaming discussions', 'snoost_forum_gaming_widget_title'); }

    if(isset($instance['createnew'])) { $instance['createnew'] = $instance['createnew']; }
    else { $instance['createnew'] = __(1, 'snoost_forum_gaming_widget_createnew'); }

    if(isset($instance['count'])) { $instance['count'] = $instance['count']; }
    else { $instance['count'] = __(5, 'snoost_forum_gaming_widget_count'); }

    return $instance;
  }

  private function getForumPosts() {
    $forumPosts = get_option('snoost_gaming_forum_posts');
    $forumPosts = json_decode($forumPosts, true);

    if(json_last_error() == JSON_ERROR_NONE) {
      if($forumPosts['lastUpdated'] > (time()-60*10)) { return $forumPosts['posts']; }
    }

    $posts = wp_remote_retrieve_body(wp_remote_get('https://www.snoost.com/community/gaming/?api', ['timeout' => '5', 'redirection' => 2]));
    $posts = json_decode($posts, true);
    if(json_last_error() != JSON_ERROR_NONE) { return $forumPosts['posts']; }

    $forumPosts = ['lastUpdated' => time(), 'posts' => $posts];
    update_option('snoost_gaming_forum_posts', json_encode($forumPosts));
    return $forumPosts['posts'];
  }

  public function widget($args, $instance) {
    $instance = $this->loadVariables($instance);
    apply_filters('widget_title', $instance['title']);

    $forumPosts = $this->getForumPosts();

    echo $args['before_widget'];
    echo $args['before_title'].$instance['title'].$args['after_title'];
    echo '<ul>'; $i = 0;
    foreach($forumPosts as $p) { $i++;
      if($i > $instance['count']) { break; }
      echo '<li class="page-item cat-item"><a href="'.$p['link'].'" target="_blank">'.$p['subject'].'</a></li>';
    }
    if($instance['createnew'] == 1) {
      echo '<li class="page-item cat-item"><a href="https://www.snoost.com/community/gaming/" target="_blank" style="font-weight: bold;">Create new discussion</a></li>';
    }
    echo '</ul>';

    echo $args['after_widget'];
  }

  public function form($instance) {
    $instance = $this->loadVariables($instance);

    echo '
<p>Show the latest gaming discussions from the Snoost forum.</p>
<p>
  <label for="'.$this->get_field_id('title').'">Title:</label>
  <input class="widefat" id="'.$this->get_field_id('title').'" name="'.$this->get_field_name('title').'" type="text" value="'.esc_attr($instance['title']).'" />
</p>
<p>
  <label for="'.$this->get_field_id('createnew').'">Show "Create new" button:</label>
  <input id="'.$this->get_field_id('createnew').'" name="'.$this->get_field_name('createnew').'" type="checkbox" value="1"'.($instance['createnew'] == 1 ? ' checked' : '').' />
</p>
<p>
  <label for="'.$this->get_field_id('count').'">Number to show:</label>
  <input id="'.$this->get_field_id('count').'" name="'.$this->get_field_name('count').'" type="text" value="'.esc_attr($instance['count']).'" />
</p>';
  }

  public function update($new_instance, $old_instance) {
    $instance = [];
    $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
    $instance['createnew'] = (!empty($new_instance['createnew'])) ? strip_tags($new_instance['createnew']) : '';
    $instance['count'] = (!empty($new_instance['count'])) ? strip_tags($new_instance['count']) : '';

    return $instance;
  }

}

?>