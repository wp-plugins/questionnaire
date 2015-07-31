<?php
/**
 * wp-nsutil.php
 * Author: Hiroyoshi Kurohara(Microgadget,inc.)
 * Author EMail: kurohara@yk.rim.or.jp
 * License: GPLv2 or Lator
 */
namespace questionnaire;

function ns_($str = '') {
  return __NAMESPACE__ . $str;
}

function ns_name($str) {
  return ns_("\\" . $str);
}

function ns_tag($str) {
  return ns_('_' . $str);
}

function add_ns_action($action_name, $function_name, $priority = 10, $accepted_args = 1) {
  return add_action($action_name, ns_name($function_name), $priority, $accepted_args);
}

function ns_add_ns_action($action_name, $function_name, $priority = 10, $accepted_args = 1) {
  return add_ns_action(ns_tag($action_name), $function_name, $priority, $accepted_args);
}

function ns_add_action($action_name, $function_name, $priority = 10, $accepted_args = 1) {
  return add_action(ns_tag($action_name), $function_name, $priority, $accepted_args);
}

function add_ns_filter($hook, $function_to_add, $priority = 10, $accepted_args = 1) {
  return add_filter($hook, ns_name($function_to_add), $priority, $accepted_args);
}

function remove_ns_filter($hook, $function_to_remove, $priority = 10, $accepted_args = 1) {
  return remove_filter($hook, ns_name($function_to_remove), $priority, $accepted_args);
}

function ns_add_filter($hook, $function_to_add, $priority = 10, $accepted_args = 1) {
  return add_filter(ns_tag($hook), $function_to_add, $priority, $accepted_args);
}

function ns_add_ns_filter($hook, $function_to_add, $priority = 10, $accepted_args = 1) {
  return add_ns_filter(ns_tag($hook), $function_to_add, $priority, $accepted_args);
}

function ns_apply_filters( $tag, $value ) {
  $argarr = func_get_args();
  $argarr[0] = ns_tag($argarr[0]);
  return call_user_func_array('apply_filters', $argarr);
}

function ns_function_exists($funcname) {
  return function_exists(ns_name($funcname));
}

function ns_do_action($action_name) {
  $argarr = func_get_args();
  $argarr[0] = ns_tag($argarr[0]);
  return call_user_func_array('do_action', $argarr);
}


