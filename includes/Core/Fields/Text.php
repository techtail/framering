<?php
namespace Framering\Core\Fields;

use Framering\Model\Field;

class Text extends Field {
    public static function get_settings() {
        return [
            "type" => "text"
        ];
    }

    public function get_editor_field() {
        return array_merge(parent::get_editor_field(), [
            "type" => "text"
        ]);
    }
}