<?php

namespace Email;


class Preference
{
  private $name;
  private $label;
  private $merge;
  private $values;

  function get_name() {
    return $this->name;
  }

  function get_label() {
    return $this->label;
  }

  function get_merge() {
    return $this->merge;
  }

  function get_values() {
    return $this->values;
  }

  function get_values_checked($wrap) {
    return array_filter(array_map(function($value) use($wrap) {
      return ($value['checked']) ? $wrap . $value['name'] . $wrap : false;
    }, $this->values));
  }

  function get_value_names() {
    return array_map(function($value) {
      return $value['name'];
    }, $this->values);
  }

  function set_values($values, $userPrefs) {
    if (is_string($userPrefs)) {
      $userPrefs = preg_split("/[;,]/", $userPrefs);
    }

    $this->values = array_map(function($value) use($userPrefs) {
      return array("name" => $value,
        "checked" => (($userPrefs === false) ? true : in_array($value, $userPrefs)));
    }, $values);
  }

  function __construct($name, $label, $merge, $values, $userPrefs) {
    $this->name     = $name;
    $this->label    = $label;
    $this->merge    = $merge;
    $this->set_values($values, $userPrefs);
  }
}