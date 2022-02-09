<?php
/**
 * Framering - Field model
 * 
 * @author Matheus Giovani <matheus@ad3com.com.br>
 */

namespace Framering\Model;

/**
 * Represents a field
 * Can be extended
 */
class Field {
    /**
     * Retrieves the field settings in an object
     *
     * @return object
     */
    public static function get_settings() {
        return (object) [
            /**
             * The field type
             * Used to identify the field when in the database
             */
            "type" => "field"
        ];
    }
    
    /**
     * The field attributes
     *
     * @var string
     */
    protected $attributes = [
        /**
         * The field title that will be displayed in the editor
         * 
         * @var string
         */
        "title" => null,

        /**
         * The field description that will be displayed in the editor
         *
         * @var string
         */
        "description" => null
    ];

    public function __construct($attributes = []) {
        if (!empty($attributes)) {
            $this->attributes = $attributes;
        }
    }

    /**
     * Retrieves the key for this field
     *
     * @return void
     */
    protected function get_key() {
        return FRAMERING_PREFIX . "field[" . $this->name . "]";
    }

    /**
     * Retrieves the field stored value
     *
     * @return string|string[]
     */
    public function get_value() {
        if ($this->value === null) {
            $this->value = \get_post_meta($this->post, $this->get_key(), true);
            $this->value = \maybe_unserialize($this->value);
        }

        return $this->value;
    }

    /**
     * Sets the field value
     *
     * @param mixed $new_value The new unserialized value to be saved
     * @param boolean $save If the value can be saved, defaults to true
     * @return void
     */
    public function set_value($new_value, $save = true) {
        $this->value = $new_value;

        if ($save) {
            \update_post_meta($this->post, $this->get_key(), \maybe_serialize($this->value));
        }
    }

    /**
     * Renders the field editor
     *
     * @return object
     */
    public function get_editor_field() {
        return [
            "type" => "text",
            "name" => $this->get_key(),
            "title" => $this->title,
            "description" => $this->description
        ];
    }
}