<?php
/**
 * codeconv.php
 * Author: Hiroyoshi Kurohara(Microgadget,inc.)
 * Author EMail: kurohara@yk.rim.or.jp
 * License: GPLv2 or Lator
 */

namespace questionnaire;

class codeconv_filter extends \php_user_filter {
  var $tocode;
  var $converting;

  function doconv(&$out, &$bucket) {
    if (strlen($this->converting) > 0) {
      $bucket->data = mb_convert_encoding($this->converting, $this->tocode);
      stream_bucket_append($out, $bucket);
      $this->converting = "";
    }
  }

  function filter($in, $out, &$consumed, $closing) {
    while ($bucket = stream_bucket_make_writeable($in)) {
      $nlpos = strpos($bucket->data, "\n");
      if ($nlpos === FALSE) {
        $this->converting = $this->converging . $bucket->data;
      } else {
        $this->converting = $this->converting . substr($bucket->data, 0, $nlpos + 1);
        $this->doconv($out, $bucket);
        $this->converting = substr($bucket->data, $nlpos + 1);
      }
      $consumed += $bucket->datalen;
    }
    if ($closing) {
      $this->doconv($out, $bucket);
    }
    return PSFS_PASS_ON;
  }

  function onCreate() {
    $this->tocode = str_replace("codeconvto.", "", $this->filtername);
    return true;
  }

}

stream_filter_register("codeconvto.*", "questionnaire\codeconv_filter") 
  or die("Failed to register filter");


