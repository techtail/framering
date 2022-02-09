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
     * Initializes the Framering framework
     * Called upon WordPress initialization
     */
    public function init() {
        // Register the default plugin fields
        foreach(self::$default_fields as $field_class) {
            $this->register_field("\\Framering\\Core\\Fields\\" . $field_class);
        }

        // Hook the WP actions
        \add_action("init", [$this, "wp_init"]);

        // Hook the admin actions
        \add_action("admin_menu", [$this, "admin_menu_init"]);

        // Hook the metabox register
        \add_action("add_meta_boxes", [$this, "register_metaboxes"]);
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

    /**
     * Called when WordPress is initialized
     *
     * @return void
     */
    public function wp_init() {
        // Allow other plugins to initialize
        \do_action("framering/init", $this);
    }

    /**
     * Called when the WordPress menu is initialized
     *
     * @return void
     */
    public function admin_menu_init() {
        $action = \add_submenu_page("tools.php", \esc_html__("Framering", "framering"), \esc_html__("Framering", "framering"), "manage_options", "framering-tools", ["\\Framering\\Views\\Admin\\DebugTools", "render"]);
        
        \wp_enqueue_script("framering-admin", \plugins_url("framering/dist/index.js", "framering"), ["jquery"], false);
        
        \add_action("load-" . $action, function() {
            \add_filter("admin_body_class", fn($class) => $class .= " is-framering-headed-content", 10, 1);
        });
    }

    /**
     * Called when WordPress is registering the metaboxes
     *
     * @return void
     */
    public function register_metaboxes() {
        // Iterate over all components
        foreach(self::$components as $component) {
            // If the component rules matches
            if ($component->check()) {
                $context = "advanced";

                switch($component->style->position) {
                    case "normal":
                        $context = "advanced";
                    break;

                    case "lateral":
                        $context = "side";
                    break;
                }
                
                \add_meta_box($component->id, $component->title, function() use ($component) {
                    return Core\Form::render_fields($component->get_fields());
                }, null, $context);
            }
        }
    }
}