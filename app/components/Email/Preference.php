<?php

namespace Email;


class Preference
{
  private $name;
  private $label;
  private $merge;
  private $options;

  function get_name() {
    return $this->name;
  }

  function get_label() {
    return $this->label;
  }

  function get_merge() {
    return $this->merge;
  }

  function get_options() {
    return $this->options;
  }

  function get_options_checked($wrap) {
    return array_filter(array_map(function($option) use($wrap) {
      return ($option['checked']) ? $wrap . $option['value'] . $wrap : false;
    }, $this->options));
  }

  function set_options($options, $userPrefs) {
    if (is_string($userPrefs)) {
      $userPrefs = preg_split("/[;,]/", $userPrefs);
    }

    $this->options = array_map(function($option) use($userPrefs) {
        $value = array_key_exists('value', $option) ? $option['value'] : $option['name'];
      return array("name" => $option['name'],
        "description" => $option['description'],
        "value" => $value,
        "checked" => (($userPrefs[0] === '') ? true : in_array($value, $userPrefs)));
    }, $options);
  }

  function __construct($name, $label, $merge, $options, $userPrefs) {
    $this->name     = $name;
    $this->label    = $label;
    $this->merge    = $merge;
    $this->set_options($options, $userPrefs);
  }
}