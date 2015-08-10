<?php
/*
Plugin Name: questionnaire
Plugin URI: http://www.microgadget-inc.com/labo/wordpress/questionnaire/
Description: Application for collecting questionnaires.
Version: 1.0.2
Author: Hiroyoshi Kurohara(Microgadget,inc.)
Author URI: http://www.microgadget-inc.com/
License: GPLv2 or later
*/

namespace questionnaire;

define('QUESTIONNAIRE_NONCE', 'questionnaire-');

include('wp-nsutil.php');

include('aggregated.php');

add_ns_action('init', 'register_questionnaire');

add_ns_action('plugins_loaded', 'questionnaire_loaded');

function questionnaire_loaded() {
  load_plugin_textdomain('questionnaire', FALSE, basename( dirname( __FILE__ ) ) . '/languages/');
}

/**
 *
 */
function register_questionnaire() {
  $labels = array(
    'name'          => __( 'Questionnaire', ns_() ),
    'singular_name' => __( 'Questionnaire', ns_() ),
    'add_new'       => __( 'Add New', ns_() ),
    'add_new_item'  => __( 'Add New Questionnaire', ns_() ),
    'edit_item'     => __( 'Edit Questionnaire', ns_() ),
    'new_item'      => __( 'New Questionnaire', ns_() ),
    'view_item'     => __( 'View Questionnaire', ns_() ),
    'search_items'  => __( 'Search Questionnaire', ns_() ),
    'not_found'     => __( 'No Questionnaire found', ns_() ),
    'not_found_in_trash' => __( 'No Questionnaire found in trash', ns_() ),
    'parent_item_colon' => __( 'Parent Questionnaire:', ns_() ),
    'menu_name'     => __( 'Questionnaire', ns_() )
  );

  $args = array(
    'labels'              => $labels,
    'hierarchical'        => true,
    'description'         => __( 'For Collecting Questionnaires especially for Absent/Present', ns_()),
    'supports'            => array( 'title', 'editor', 'comments', 'custom-fields' ),
    'public'              => true,
    'show_ui'             => true,
    'show_in_menu'        => true,
    'show_in_nav_menus'   => true,
    'publicly_queryable'  => true,
    'exclude_from_search'  => false,
    'has_archive'         => true,
    'query_var'           => true,
    'can_export'          => true,
    'rewrite'             => true,
    'capability_type'     => 'post'
  );
  register_post_type( 'questionnaire', $args );

  set_comment_filter();

  add_ns_action('add_meta_boxes_questionnaire', 'meta_box');

  add_ns_action('save_post', 'save_post');

  add_ns_action('the_content', 'the_content');

  add_ns_action('wp_ajax_questionnaire_postanswer', 'ajax_process_answer');

  add_ns_action('wp_ajax_nopriv_questionnaire_postanswer', 'ajax_process_answer');

  add_ns_action('wp_ajax_questionnaire_aggregated_content', 'ajax_aggregated_content');

  add_ns_action('wp_ajax_questionnaire_aggregated_csv', 'ajax_aggregated_csv');

  add_ns_action('wp_ajax_questionnaire_clear_answers', 'ajax_clear_answers');

  add_ns_filter('get_comments_number', 'get_comments_number');

}

function set_comment_filter() {
  add_ns_filter('pre_get_comments', 'comments_prequery');
}

function clear_comment_filter() {
  remove_ns_filter('pre_get_comments', 'comments_prequery');
}

function do_output_debug($buffer) {
  error_log($buffer);
}

function get_comments_number($counted) {

  $post = $GLOBALS['post'];
  if ($post->post_type === 'questionnaire') {
    clear_comment_filter();
    $comments = get_comments(array(
          'post_id' => $post->ID,
          'type' => 'questionnaire_answer'
        )
    );
    set_comment_filter();
    return $counted - count($comments);
  } else {
    return $counted;
  }
}

function comments_filter($comments) {
  if ($GLOBALS['post']->post_type === 'questionnaire') {
    $result = array();
    foreach ($comments as $comment) {
      if ($comment->comment_type !== 'questionnaire_answer') {
        array_push($result, $comment);
      }
    }
    return $result;
  } else {
    return $comments;
  }
}

function comments_prequery(&$comments_query) {
  $comments_query->query_vars['type__not_in'] = "questionnaire_answer";
}

function ajax_process_answer() {

  if (! wp_verify_nonce($_POST['nonce'], QUESTIONNAIRE_NONCE . $_POST['postid']) ) {
    echo json_encode(array('success' => false));
    die();
  }
  $remoteaddr = $_SERVER['REMOTE_ADDR'];
  $useragent = $_SERVER['HTTP_USER_AGENT'];
  $user = wp_get_current_user();
  $userid = $user->ID;
  $comments_query = array('post_id' => $_POST['postid'], 'type' => 'questionnaire_answer');
  if ($userid !== 0) {
    $author = $user->user_login;
    $email = '';
    $comments_query['user_id'] = $userid;
  } else {
    $author = $_POST['author'];
    $email = $_POST['email'];
    if (trim($email) === '' || trim($author) === '') {
      echo json_encode(array('success' => false, 'msg' => __('Required field is empty', ns_())));
      die();
    }
    $comments_query['author__in'] = $author;
    $comments_query['author_email'] = $email;
  }

  clear_comment_filter();
  $comments = get_comments($comments_query);
  set_comment_filter();

  $psd_comment = NULL;
  if (count($comments) > 0) {
    $psd_comment = $comments[0];
  }

  if ($psd_comment == NULL) {
    wp_new_comment(array(
        'comment_post_ID' => $_POST['postid'],
        'comment_author' => $author,
        'comment_author_email' => $email, 
        'comment_author_url' => '',
        'comment_content' => $_POST['frmdata'],
        'comment_type' => 'questionnaire_answer', 
        'comment_parent' => 0,
        'user_id' => $userid,
        'comment_author_IP' => $remoteaddr,
        'comment_agent' => $useragent,
      )
    );
  } else {
    wp_update_comment(array(
        'comment_ID' => $psd_comment->comment_ID,
        'comment_post_ID' => $psd_comment->comment_post_ID,
        'comment_content' => $_POST['frmdata'],
        'comment_author_IP' => $remoteaddr,
        'comment_agent' => $useragent,
        'comment_author_email' => $email, 
      )
    );
  }

  echo json_encode(array('success' => true));
  die();
}

function ajax_clear_answers() {

  if (array_key_exists('postid', $_POST)) {

    $postid = $_POST['postid'];
    $nonce = $_POST['nonce'];

    if (wp_verify_nonce($nonce, QUESTIONNAIRE_NONCE . $postid)) {

      $comments_query = array('post_id' => $_POST['postid'], 'type' => 'questionnaire_answer');
  
      clear_comment_filter();
      $comments = get_comments($comments_query);
  
      foreach ( $comments as $comment ) {
        wp_delete_comment($comment->comment_ID, true);
      }
  
      $comments_query['count'] = true;
      $ccount = get_comments($comments_query);
  
      set_comment_filter();
  
      echo json_encode(array('success' => true, 'count' => $ccount));

      die();
    }
  }
  echo json_encode(array('success' => false));

  die();
}

function get_answer_count($postid) {
  $comments_query = array(
                      'post_id' => $postid, 
                      'type' => 'questionnaire_answer',
                      'count' => true,
          );

  clear_comment_filter();
  $ccount = get_comments($comments_query);
  set_comment_filter();

  return $ccount;
}

function has_key_and_true($key, $hash_obj) {
  return (array_key_exists($key, $hash_obj) && $hash_obj[$key]);
}

function check_if_issuer($user) {
  if (has_key_and_true('edit_posts', $user->allcaps) &&
      has_key_and_true('edit_published_posts', $user->allcaps) &&
      has_key_and_true('publish_posts', $user->allcaps) && 
      has_key_and_true('edit_pages', $user->allcaps) &&
      has_key_and_true('edit_others_pages', $user->allcaps) &&
      has_key_and_true('edit_published_pages', $user->allcaps) &&
      has_key_and_true('moderate_comments', $user->allcaps) ) {
    return true;
  } else {
    return false;
  }
}

function js_localize_data($array_data) {
  $js_data = array(
    'txtSubmit'             => __('Submit', ns_()),
    'txtPleaseAnswer'       => __('Please Answer', ns_()),
    'txtFormDesignerTitle'  => __('Form Designer', ns_()),
    'txtFormSampleTitle'    => __('Form Sample', ns_()),
    'txtAddItem'            => __('Add Item', ns_()),
    'txtShowSample'         => __('Show Sample', ns_()),
    'txtAnswerCommitted'    => __('Successfully Committed!', ns_()),
    'txtServerError'        => __('Server Error!', ns_()),
    'txtIsPublic'           => __('Is public questionnaire?', ns_()),
    'txtYourName'           => __('Your Name', ns_()),
    'txtYourMailAddress'    => __('Your EMail Address', ns_()),
    'txtIdentityLabel'      => __('Please identify yourself', ns_()),
    'txtAnswerCount'        => __('Current count of Aswers: ', ns_()),
    'txtClearAnswers'       => __('Delete all answers', ns_()),
    'txtThankYou'           => __('Thank you for answering this questionnaire!', ns_()),
    'txtOK'                 => __('Dismiss', ns_()),
  );

  return array_merge($js_data, $array_data);
}

function get_questionnaire_meta($post) {
  if ($post->__isset('questionnaire_metajson')) {
    $jsonstr = $post->__get('questionnaire_metajson');
    $jsonstr = str_replace('&lt;', '&amp;lt;', $jsonstr);
    $jsonstr = str_replace('&gt;', '&amp;gt;', $jsonstr);
  } else {
    $jsonstr = "";
  }

  return $jsonstr;
}

function the_content($content) {
  $post = $GLOBALS['post'];

  if ($post->post_type === 'questionnaire' &&
    $GLOBALS['wp_query']->post_count === 1) {

    if ($post->__isset('questionnaire_ispublic') && $post->__get('questionnaire_ispublic') === 'on') {
      $ispublic = true;
    } else {
      $ispublic = false;
    }
    $current_user = wp_get_current_user();
    if ($current_user->ID !== 0) {
      $isloggedin = true;
      $ispublic = false;
    } else {
      $isloggedin = false;
    }

    $jsonstr = get_questionnaire_meta($post);

    clear_comment_filter();
    $frmcomments = get_comments(array(
          'post_id' => $post->ID,
          'type' => 'questionnaire_answer',
          'author__in' => array($current_user->ID)
        )
    );
    set_comment_filter();

    if (count($frmcomments) > 0) {
      $frmvalue = $frmcomments[0]->comment_content;
    } else {
      $frmvalue = "[]";
    }

    $aggregated = "";
    if (check_if_issuer($current_user)) {
      $aggregated = aggregated_data($post);
    }

    $jsdata = js_localize_data(array( 
      'postid' => $post->ID,
      'showForm' => 1, 
      'metajsonstr' => $jsonstr, 
      'frmvalue' => $frmvalue,
      'admin_ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce(QUESTIONNAIRE_NONCE . $post->ID)
    ));

    wp_enqueue_script('questionnaire_formcomposer', plugins_url('formcompose.js', __FILE__));
    wp_localize_script('questionnaire_formcomposer', 'questionnaire_data', $jsdata);
    wp_enqueue_style('questionnaire_formcomposer_style', plugins_url('formcompose.css', __FILE__));
    wp_enqueue_style('questionnaire_icomoon_style', plugins_url('icomoon/style.css', __FILE__));

    $html = "";

    if ( $isloggedin || $ispublic ) {

      $html = <<<EOF_META
        <div class="questionnaire_answersheet">
          <div class="title">{$jsdata['txtPleaseAnswer']}</div>
          <div class="questionnaire_actForm"></div>
          <!-- not public
          <div class="questionnaire_authorinfo">
          <hr class="questionnaire_authinfo">
          <fieldset class="questionnaire_authorinfo"><legend>{$jsdata['txtIdentityLabel']}</legend>
          <table><tr><td>
          <span class="publiclabel">{$jsdata['txtYourName']}</span></td><td><input required type="text" class="publicname" name="questionnaire_name" id="questionnaire_name" maxlength="64"></td><tr><td>
          <span class="publiclabel">{$jsdata['txtYourMailAddress']}</span></td><td><input required type="email" class="publicmail" name="questionnaire_mail" id="questionnaire_mail"></td></tr></table>
          </fieldset>
          </div>
               not public -->
          <table class="doAnswer"><tr><td>
          <button type="button" id="questionnaire_doAnswerBtn">{$jsdata['txtSubmit']}</button>
          </td></tr></table>
          <div class="questionnaire_dialog" style="display:none;position:absolute">
            <table style="border:none" align="right">
              <tr>
                <td><div class="message">{$jsdata['txtThankYou']}</div></td>
              </tr>
              <tr>
                <td><button class="small ackbtn" type="button">{$jsdata['txtOK']}</button></td>
              <tr>
            </table>
          </div>
        </div>
EOF_META;
      if ($ispublic) {
        $html = str_replace("<!-- not public", "", $html);
        $html = str_replace("not public -->", "", $html);
      }
      $content = $content . $aggregated . $html;
    }
  }

  return $content;
}

function meta_box() {
  add_meta_box(ns_('custom_property'), __('Questionnaire Sheet', ns_()), ns_name('render_meta_box'));
}

function save_post($post_id) {
  global $post;
  if (array_key_exists('questionnaire_metajson', $_POST)) {
    $metajson = $_POST['questionnaire_metajson'];
    update_post_meta($post->ID, "questionnaire_metajson", $metajson);
    if (array_key_exists('questionnaire_ispublic', $_POST)) {
      update_post_meta($post->ID, "questionnaire_ispublic", $_POST['questionnaire_ispublic']);
    }
  }
}

function render_meta_box($post) {
  wp_enqueue_script('questionnaire_formcomposer', plugins_url('formcompose.js', __FILE__));
  $ldata = js_localize_data(array( 
        'admin_ajax_url' => admin_url('admin-ajax.php'), 
        'postid' => $post->ID,
        'nonce' => wp_create_nonce(QUESTIONNAIRE_NONCE . $post->ID)
  ));
  wp_localize_script('questionnaire_formcomposer', 'questionnaire_data', $ldata);
  wp_enqueue_style('questionnaire_formcomposer_style', plugins_url('formcompose.css', __FILE__));
  wp_enqueue_style('questionnaire_icomoon_style', plugins_url('icomoon/style.css', __FILE__));

  $jsonstr = get_questionnaire_meta($post);

  if ($post->__isset('questionnaire_ispublic') && $post->__get('questionnaire_ispublic') === "on") {
    $ispublic = "checked";
  } else {
    $ispublic = "";
  }
  $answercount = get_answer_count($post->ID);
?>
    <div class="questionnaire_propsheet">
      <input type="hidden" name="questionnaire_metajson" id="questionnaire_metajson" value="<?= $jsonstr ?>">
      <div class="title"><?= $ldata['txtFormDesignerTitle'] ?></div>
      <div class="chkIsPublic"><input type="checkbox" id="chkIsPublic" name="questionnaire_ispublic" <?= $ispublic ?> ><label for="chkIsPublic"><?= $ldata['txtIsPublic'] ?></label></div><br>
      <button type="button" id="questionnaire_addItem"><?= $ldata['txtAddItem'] ?></button>
      <div class="questionnaire_composeForm">
      </div>
    </div>
    <div class="questionnaire_answersheet">
      <div class="title"><?= $ldata['txtFormSampleTitle'] ?></div>
      <div class="questionnaire_actForm">
      </div>
    </div>
    <div class="questionnaire_answerinfo">
      <table style="border:none">
        <tr>
          <td><span><?= $ldata['txtAnswerCount'] ?></span><span><?= $answercount ?></span></td>
          <td><button type="button" id="questionnaire_clearAnswers"><?= $ldata['txtClearAnswers'] ?></button></td>
        </tr>
      </table>
    </div>
<?php
}

