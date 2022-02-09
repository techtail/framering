<?php
namespace Framering\Views\Admin;

use Framering\Core;

class DebugTools {
    public static function render() {
        $instance = Core::Instance();
    ?>
        <div class="framering-card">
            <div class="header">
                <h2>Framering</h2>
            </div>

            <div class="body">
                <h3>Registered components</h3>
                <?php
                    foreach($instance->get_components() as $component) {
                ?>
                    <div class="framering-card">
                        <?php
                            echo "<strong>" . $component->get_title() . "</strong><br/>";
                        ?>
                    </div> 
                <?php
                    }
                ?>

                <hr/>

                <h3>Registered fields</h3>
                <?php
                    foreach($instance->get_fields() as $field) {
                ?>
                    <div class="framering-card">
                        <?php
                            echo "<strong>" . $field::get_settings()->type . "</strong><br/>";
                            $field = new $field();
                            var_dump($field);
                        ?>
                    </div>
                <?php
                    }
                ?>
            </div>
        </div>
    <?php
    }
}