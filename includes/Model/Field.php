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

    /**
     * The field parent field, if any
     *
     * @var ?Field
     */
    protected $parent;

    /**
     * The component related to this field
     *
     * @var ?Component
     */
    protected $component;

    /**
     * The field value
     *
     * @var string
     */
    protected $value = null;

    /**
     * The post related to this field
     *
     * @var \WP_Post
     */
    protected $post;

    public function __construct($attributes = [], $component = null) {
        if (!empty($attributes)) {
            $this->attributes = $attributes;
        }

        if (!empty($attributes["parent"])) {
            $this->parent = $attributes["parent"];
        }

        if (!empty($component)) {
            $this->component = $component;
        }
    }

    /**
     * Retrieve the field form name
     *
     * @return string
     */
    public function get_name() {
        $name = "";

        if (!empty($this->component)) {
            $name = $this->component->get_name() . "[";
        }

        if (!empty($this->parent)) {
            $name = $this->parent->get_name() . "[" . $this->attributes["name"] . "]";
        } else {
            $name .= $this->attributes["name"];
        }

        if (!empty($this->component)) {
            $name .= "]";
        }

        return $name;
    }

    /**
     * Retrieves the key for this field
     *
     * @return void
     */
    protected function get_key() {
        $key = FRAMERING_PREFIX . "field_";

        // If has a component, append the component name to it
        if (!empty($this->component)) {
            $key .= $this->component->get_name() . "_";
        }
        
        if (!empty($this->parent)) {
            $key .= $this->parent->get_key() . "_" . $this->attributes["name"];
        } else {
            $key .= $this->attributes["name"];
        }
        
        return $key;
    }

    /**
     * Retrieves the post related to this field.
     * If no post was given, will retrieve the current post being edited / displayed.
     *
     * @return \WP_Post
     */
    protected function get_post() {
        if (empty($this->post)) {
            $this->post = \get_post();

            if (empty($this->post) && isset($_GET["post"])) {
                $this->post = \get_post($_GET["post"]);
            }
        }

        return $this->post;
    }

    /**
     * Retrieves the field stored value
     *
     * @return string|string[]
     */
    public function get_value() {
        if ($this->value === null) {
            $this->value = \get_post_meta($this->get_post()->ID, $this->get_key(), true);


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
     * Retrieves the field parent field
     *
     * @return ?Field
     */
    public function get_parent() {
        return $this->parent;
    }

    /**
     * Checks if the field has a parent field
     *
     * @return boolean
     */
    public function has_parent() {
        return !empty($this->parent);
    }

    /**
     * Renders the field editor
     *
     * @return object
     */
    public function get_editor_field() {
        return array_merge($this->attributes, [
            "type" => "text",
            "name" => $this->get_name(),
            "value" => $this->get_value()
        ]);
    }
}