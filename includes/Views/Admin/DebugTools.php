<?php
namespace Framering\Views\Admin;

use Framering\Core;

class DebugTools {
    public static function render() {
        $instance = Core::Instance();
    ?>
        <div style="text-align: center; margin: 0 0 1rem; padding: 1rem; background: #fff; border-bottom: 1px solid #dcdcde;">
            <h2>Framering</h2>
        </div>

        <hr class="wp-header-end" />

        <h3>Registered components</h3>
        <?php
            foreach($instance->get_components() as $component) {
                var_dump($component);
            }
        ?>

        <hr/>

        <h3>Registered fields</h3>
        <?php
            foreach($instance->get_fields() as $field) {
                $field = new $field();
                var_dump($field);
            }
        ?>
    <?php
    }
}