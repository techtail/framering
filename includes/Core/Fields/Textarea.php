<?php
namespace Framering\Core\Fields;

use Framering\Model\Field;

class Textarea extends Field {
    public static function get_settings() {
        return (object) [
            "type" => "textarea"
        ];
    }

    public function get_editor_field() {
        return array_merge(parent::get_editor_field(), [
            "type" => "text"
        ]);
    }
}