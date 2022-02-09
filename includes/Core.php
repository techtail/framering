<?php
/**
 * Framering - Core loader
 * @author Matheus Giovani <matheus@ad3com.com.br>
 * @author TechTail <falecom@techtail.net>
 */

namespace Framering;

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
     * @var array
     */
    public static $fields = [];

    /**
     * An array of registered components
     *
     * @var \Framering\Core\Component[]
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
     * Initializes the Framering framework
     * Called upon WordPress initialization
     */
    public function init() {
        // Register the default plugin fields
        foreach(self::$default_fields as $field_class) {
            $this->register_field("\\Framering\\Core\\Fields\\" . $field_class);
        }

        // Allow other plugins to initialize
        \do_action("framering/init", __NAMESPACE__ . "\\Core");

        // Hook the admin actions
        \add_action("admin_init", [$this, "admin_init"]);
        \add_action("admin_menu", [$this, "admin_menu_init"]);
    }

    public function get_components() {
        return self::$components;
    }

    public function get_fields() {
        return self::$fields;
    }

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

    /**
     * Called when the admin_init hook is called
     *
     * @return void
     */
    public function admin_init() {
        
    }

    public function admin_menu_init() {
        \add_submenu_page("tools.php", \esc_html__("Framering", "framering"), \esc_html__("Framering", "framering"), "manage_options", "framering-tools", ["\\Framering\\Views\\Admin\\DebugTools", "render"]);
    }
}