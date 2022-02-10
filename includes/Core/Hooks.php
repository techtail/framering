<?php
namespace Framering\Core;

use Framering\Core;

class Hooks {
    /**
     * An array containing arrays of data to be saved after a hook
     *
     * @var array[]
     */
    public $to_save = [];

    public function __construct($instance) {
        $this->core = $instance;
    }

    /**
     * Called when WordPress is initialized
     *
     * @return void
     */
    public function wp_init() {
        // Allow other plugins to initialize
        \do_action("framering/init", $this->core);
    }

    public function admin_init() {
        // Iterate over all components
        foreach(Core::$components as $component) {
            // If the component rules matches
            if ($component->check()) {
                // Create the form for it
                $component->create_form();
            }
        }
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
        foreach(Core::$components as $component) {
            // If the component has a created form
            if ($component->has_form()) {
                $context = "advanced";

                switch($component->style->position) {
                    case "normal":
                        $context = "advanced";
                    break;

                    case "lateral":
                        $context = "side";
                    break;
                }
                
                // Add the metabox to it
                \add_meta_box($component->id, $component->title, function() use ($component) {
                    return $component->get_form()->render();
                }, null, $context);
            }
        }
    }

    /**
     * Called before the post data is inserted into the database
     *
     * @param array $post_data
     * @return array
     */
    public function insert_post_data($post_data) {
        // If has framering data to parse
        if (isset($_POST["framering"])) {
            // Iterate over all components
            foreach(Core::$components as $component) {
                // Ignore if has no created form
                if (!$component->has_form()) {
                    continue;
                }

                $data = $_POST["framering"];
                $parsed = $component->get_form()->process($data);
                $parsed = $parsed[$component->get_name()];

                // Check if any error ocurred
                if (\is_wp_error($parsed)) {
                    \wp_send_json_error($parsed, 403);
                    exit;
                }

                if (!empty($parsed)) {
                    $save = [];

                    $prefix = FRAMERING_PREFIX . "field_";

                    if (!empty($component->id)) {
                        $prefix .= $component->id . "_";
                    }

                    foreach($parsed as $key => $value) {
                        $save[$prefix . $key] = $value === null ? null : \maybe_serialize($value);
                    }

                    $this->to_save[] = $save;
                }
            }
        }

        return $post_data;
    }

    /**
     * Called when a post is saved
     *
     * @param int $post_id The related post ID
     * @param \WP_Post $post The related post
     * @return void
     */
    public function save_post($post_id, $post) {
        foreach($this->to_save as $save) {
            foreach($save as $key => $value) {
                if ($value === null) {
                    \delete_post_meta($post_id, $key);
                } else {
                    \update_post_meta($post_id, $key, $value);
                }
            }
        }
    }
}