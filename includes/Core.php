<?php
/**
 * Framering - Core loader
 * @author Matheus Giovani <matheus@ad3com.com.br>
 * @author TechTail <falecom@techtail.net>
 */

namespace Framering;

use Framering\Core\Hooks;

final class Core {
    /**
     * The core cache, used to cache objects
     *
     * @var array
     */
    public static $cache = [];

    /**
     * An array of registered fields
     *
     * @var Model\Field[]
     */
    public static $fields = [];

    /**
     * An array of registered components
     *
     * @var Component[]
     */
    public static $components = [];

    /**
     * The default plugin fields
     *
     * @var array
     */
    private static $default_fields = ["Text"];

    public static function Instance() {
        static $instance = null;

        if (empty($instance)) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * The WP hook manager instance
     *
     * @var Hooks
     */
    public $hooks;

    /**
     * Initializes the Framering framework
     * Called upon WordPress initialization
     */
    public function init() {
        $this->hooks = new Hooks($this);

        // Register the default plugin fields
        foreach(self::$default_fields as $field_class) {
            $this->register_field("\\Framering\\Core\\Fields\\" . $field_class);
        }

        // Hook the WP actions
        \add_action("init", [$this->hooks, "wp_init"]);
        \add_action("admin_init", [$this->hooks, "admin_init"]);

        // Hook the admin actions
        \add_action("admin_menu", [$this->hooks, "admin_menu_init"]);

        // Hook the metabox register
        \add_action("add_meta_boxes", [$this->hooks, "register_metaboxes"]);

        // Filters the pre-saving post action
        \add_filter("wp_insert_post_data", [$this->hooks, "insert_post_data"], 10, 1);

        // Hook the post saving action
        \add_action("save_post", [$this->hooks, "save_post"], 10, 2);
    }

    /**
     * Retrieves all registered components
     *
     * @return Component[]
     */
    public function get_components() {
        return self::$components;
    }

    /**
     * Retrieves all registered fields
     *
     * @return Model\Field[]
     */
    public function get_fields() {
        return self::$fields;
    }

    /**
     * Retrieves a single registered field
     *
     * @param string $field The field type
     * @return Model\Field
     */
    public function get_field($field) {
        return self::$fields[$field];
    }

    /**
     * Registers a new field
     *
     * @param \Framering\Model\Field $field The field to be registered
     * @return void
     */
    public function register_field($field) {
        self::$fields[$field::get_settings()->type] = $field;
    }

    /**
     * Registers a component
     *
     * @param Component $component The component to be registered
     * @return void
     */
    public function register_component($component) {
        self::$components[] = $component;
    }
}