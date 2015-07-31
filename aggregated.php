<?php
/**
 * aggregated.php
 * Author: Hiroyoshi Kurohara(Microgadget,inc.)
 * Author EMail : kurohara@yk.rim.or.jp
 * License: GPLv2 or Lator.
 *
 */
namespace questionnaire;

require_once(ABSPATH .'wp-includes/pluggable.php');

require_once("codeconv.php");

function init_metadata() {
  return array(
    'itemid' => 0,
    'valueid' => 0,
    'prevoption' => "",
    'cellindex' => 0,
    'header' => array(),
    'cellmeta' => array()
  );
}

function prepare_aggregate_meta_callback($option, $text, &$data) {

  $itemid = $data['itemid'];
  $itemname = "";
  $valueid = $data['valueid'];
  $prevoption = $data['prevoption'];
  $cellindex = $data['cellindex'];

  if ($prevoption != $option || $option == 'TEXT') {
    $valueid = 0;
    ++$itemid;
  }
  switch ($option) {
  case 'LBL':
    array_push($data['header'], array('label' => $text, 'selections' => array() ));
    return;
  case 'TEXT':
    $itemname = 'message_' . $itemid;
    break;
  case 'CHK':
    $itemname = 'check_' . $itemid;
    break;
  case 'OPT':
    $itemname = 'select_' . $itemid;
    break;
  case 'RAD':
    $itemname = 'radio_' . $itemid;
    break;
  default:
    break;
  }
  $field = array( 'text' => $text, 'option' => $option, 'name' => $itemname); // no selection label, just message.
  array_push($data['header'][count($data['header']) - 1]['selections'], $field);
  $data['itemid'] = $itemid;
  $data['prevoption'] = $option;
  $data['cellmeta'][ $itemname . "_" . $valueid ] = $cellindex;
  $data['valueid'] = $valueid + 1;
  $data['cellindex'] = $cellindex + 1;
}

function prepare_aggregate_data($frmdata, $metadata) {

  $cellmeta = $metadata['cellmeta'];
  $cellarray = array_fill(0, count($cellmeta), "");
  $fieldIndex = 0;
  foreach($frmdata as $item) {
    if (preg_match('/message.*/', $item->name) > 0) {
      $cellkey = $item->name . "_0";
      $cellvalue = $item->value;
      $cellvalue = str_replace('<', '&lt;', $cellvalue);
      $cellvalue = str_replace('>', '&gt;', $cellvalue);
    } else {
      $cellkey = $item->name . "_" . $item->value;
      $cellvalue = '<span class="icon-checkmark2"></span>';
    }
    $cellarray[ $cellmeta[ $cellkey ] ] = $cellvalue;
  }

  return $cellarray;
}

function iterate_metaobj($metaobj, $func, &$data) {
  for ($i = 0;$i < count($metaobj);) {
    $option = "";
    $text = "";
    for ($j = 0;$j < 2;++$j) {
      if ($metaobj[$i + $j]->name === 'text') { $text = $metaobj[$i + $j]->value; }
      if ($metaobj[$i + $j]->name === 'select') { $option = $metaobj[$i + $j]->value; }
    }
    $i += 2;
    $func($option, $text, $data);
  }
}

function aggregated_table_header($metadata) {
?>
  <tr>
    <th rowspan=2><?php _e('User Name', ns_()); ?></th>
    <?php foreach ($metadata['header'] as $head) : ?>
    <th colspan="<?= count($head['selections']) ?>"><?= $head['label'] ?></th>
    <?php endforeach; ?>
  </tr>
  <tr>
    <?php foreach ($metadata['header'] as $head) : ?>
      <?php foreach ($head['selections'] as $label) : ?>
        <th><?= $label['text'] ?></th>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </tr>
<?php
}

function aggregated_table_data($frmcomments, $metadata) {
  foreach ($frmcomments as $comment) {
    wp_set_comment_status($comment->comment_ID, 'approve');
    $cellarray = prepare_aggregate_data(json_decode($comment->comment_content), $metadata);
?>
  <tr>
    <td><?= $comment->comment_author ?></td>
    <?php foreach ($cellarray as $cell) : ?>
    <td>
      <?= $cell ?>
    </td>
    <?php endforeach; ?>
  </tr>
<?php
  }
}

function aggregated_ans_pagenav($start, $current, $numlink, $last) {
  $prevliststart = $start - $numlink;
  if ($prevliststart < 0) {
    $prevliststart = 1;
  }
  $nextliststart = $start + $numlink;
  if ($nextliststart >= $last) {
    $nextliststart = $start;
  }
  if ($start == $last) {
    return;
  }
?>
  <?php if ($current > 1) : ?>
    <a class="previndex" start="<?= $start ?>" current="<?= $current - 1 ?>">&lt;</a>
  <?php endif; ?>
  <?php if ($start > 1) : ?>
    <a class="prevlist" start="<?= $prevliststart ?>" current="<?= $current ?>">...</a>
  <?php endif; ?>
  <?php for ($i = $start;$i < $numlink + $start && $i <= $last;++$i) : ?>
    <?php if ($i != $current) : ?>
      <a class="pageindex" start="<?= $start ?>" current="<?= $i ?>"><?= $i ?></a>
    <?php else : ?>
      <span class="pageindex_current"><?= $i ?></span>
    <?php endif; ?>
  <?php endfor; ?>
  <?php if ($start < $nextliststart) : ?>
    <a class="nextlist" start="<?= $nextliststart ?>" current="<?= $current ?>">...</a>
  <?php endif; ?>
  <?php if ($current < $start + $numlink && $current < $last) : ?>
    <a class="nextindex" start="<?= $start ?>" current="<?= $current + 1 ?>">&gt;</a>
  <?php endif; ?>
<?php
}

function ajax_aggregated_content() {
  $current_user = wp_get_current_user();
  if (! check_if_issuer($current_user)) {
    die();
  }

  $postid = $_POST['postid'];
  if (!wp_verify_nonce($_POST['nonce'], QUESTIONNAIRE_NONCE . $postid)) {
    die();
  }

  $numanswer = 10;
  $numpages = 10;
  $start = $_POST['start'];
  $current = $_POST['current'];
  if ($current < 1) $current = 1;
  $metajsonstr = get_post_meta($postid, 'questionnaire_metajson', true);
  $metaobj = json_decode($metajsonstr);
  $metadata = init_metadata();
  iterate_metaobj($metaobj, "questionnaire\prepare_aggregate_meta_callback", $metadata);

  clear_comment_filter();
  $cnt_all = get_comments(array(
          'post_id' => $postid,
          'type' => 'questionnaire_answer',
          'count' => true,
      )
  );

  $frmcomments = get_comments(array(
          'post_id' => $postid,
          'type' => 'questionnaire_answer',
          'number' => $numanswer,
          'offset' => ($current - 1) * $numanswer,
        )
    );
  set_comment_filter();

  $last = intval($cnt_all / $numanswer) + 1;

?>
  <?php _e('Answer list', ns_()); ?><br>
  <table class="aggregated">
    <?php aggregated_table_header($metadata); ?>
    <?php aggregated_table_data($frmcomments, $metadata); ?>
  </table>
  <div class="ans_pagenav">
    <?php aggregated_ans_pagenav($start, $current, $numpages, $last); ?>
  </div>
  <?php if ($cnt_all > 0) : ?>
  <div class="ans_download">
  <a href="<?= admin_url('admin-ajax.php') . '?action=questionnaire_aggregated_csv&postid=' . $postid . '&nonce=' . wp_create_nonce(QUESTIONNAIRE_NONCE . $postid) ?>">
  <?php _e('Download answer list as CSV file', ns_()); ?>
  </a>
  </div>
  <?php endif; ?>
<?php

  die();
}

function aggregated_data($post) {
  wp_enqueue_script('questionnaire_aggregated_data', plugins_url('aggregated.js', __FILE__));

  ob_start();
?>
<div class="aggregated">
  <?php _e('Answer list', ns_()); ?><br>
  <table class="aggregated">
  </table>
</div>
<?php
  $table = ob_get_contents();
  ob_end_clean();
  return $table;
}


function aggregated_data_array($metadata, $comments) {
  $result = array();

  // header

  $rec1 = array();
  $rec2 = array();

  array_push($rec1, __('Name', ns_()));
  array_push($rec2, __('Name', ns_()));

  array_push($rec1, __('EMail', ns_()));
  array_push($rec2, __('EMail', ns_()));

  foreach ($metadata['header'] as $head) {
    array_push($rec1, $head['label']);
    $ncol = count($head['selections']);
    for ($i = 1; $i < $ncol;++$i) {
      array_push($rec1, '');
    }
  }

  foreach ($metadata['header'] as $head) {
    foreach ($head['selections'] as $label) {
      array_push($rec2, $label['text']);
    }
  }
  array_push($result, $rec1);
  array_push($result, $rec2);

  // data body

  foreach ($comments as $comment) {
    $cellarray = prepare_aggregate_data(json_decode($comment->comment_content), $metadata);
    $rec = array();

    array_push($rec, $comment->comment_author);
    array_push($rec, $comment->comment_author_email);

    foreach ($cellarray as $cell) {
      if ($cell === "") {
        array_push($rec, "");
      } else {
        array_push($rec, "1");
      }
    }
    array_push($result, $rec);
  }

  return $result;
}

function ajax_aggregated_csv() {
  $current_user = wp_get_current_user();
  if (! check_if_issuer($current_user)) {
    echo 'invalid request';
    die();
  }

  $postid = $_REQUEST['postid'];
  if (!wp_verify_nonce($_REQUEST['nonce'], QUESTIONNAIRE_NONCE . $postid)) {
    echo 'invalid request';
    die();
  }
  $metajsonstr = get_post_meta($postid, 'questionnaire_metajson', true);
  $metaobj = json_decode($metajsonstr);
  $metadata = init_metadata();
  iterate_metaobj($metaobj, "questionnaire\prepare_aggregate_meta_callback", $metadata);

  clear_comment_filter();
  $frmcomments = get_comments(array(
          'post_id' => $postid,
          'type' => 'questionnaire_answer',
        )
    );
  set_comment_filter();

  $data = aggregated_data_array($metadata, $frmcomments);

  header("Content-type: text/csv; charset=utf-8");

  $stdout = fopen("php://output", "w");
  stream_filter_append($stdout, "codeconvto.SJIS");

  foreach ($data as $record) {
    fputcsv($stdout, $record);
  }
  fclose($stdout);

  die();
}

