<?php
/**
 * Framering - Component class
 * @author Matheus Giovani <matheus@ad3com.com.br>
 * @author TechTail <falecom@techtail.net>
 */

namespace Framering;

use Framering\Core\Form;

/**
 * Represents a component
 * Can be extended and reused
 */
class Component {
    /**
     * The component ID
     *
     * @var string
     */
    public $id;

    /**
     * The component title
     *
     * @var string
     */
    public $title;

    /**
     * The array of fields
     *
     * @var array[]
     */
    public $fields = [];

    /**
     * The ruleset that applies to this component
     *
     * @var array[]|RuleCondition[]
     */
    public $rules = [];

    /**
     * The component style
     *
     * @var object
     */
    public $style;

    /**
     * The component name
     *
     * @var ?string
     */
    public $name;

    /**
     * The component form instance, if any
     *
     * @var Form
     */
    protected $form;

    public function __construct($data = [
        /**
         * The component ID
         * If none is given, an ID will be generated based in the component title
         */
        "id" => null,

        /**
         * The component name
         * A named component will have all fields saved with the name prefix
         * and will need this prefix to retrieve the fields, eg.: \get_field("componentname.fieldname")
         */
        "name" => null,

        /**
         * The component title
         */
        "title" => null,

        /**
         * The component description
         */
        "description" => null,

        /**
         * The component ruleset
         * Can either be an associative array containing simple instructions
         * or an array of rule condition instances.
         * 
         * All rules will be treated as `AND`, except for the ones
         * inside rule condition instances.
         */
        "rules" => [],

        /**
         * An array containing fields to be added to the component
         * @var Model\Field[]
         */
        "fields" => [],

        /**
         * The component settings
         */
        "settings" => [
            /**
             * The component style, either "metabox" or "borderless"
             */
            "style" => "metabox",

            /**
             * The component position
             * Can be one of the following:
             * - top: before the content editor
             * - normal: after the content editor
             * - lateral: in the post editor sidebar
             */
            "position" => "normal"
        ]
    ]) {
        if (empty($data["title"])) {
            throw new \Exception("All components must have a name.");
        }

        if (empty($data["rules"])) {
            throw new \Exception("All components must have at least one rule.");
        }

        if (empty($data["id"])) {
            $this->id = \sanitize_title_with_dashes($data["title"]);
        } else {
            $this->id = $data["id"];
        }

        if (!empty($data["name"])) {
            $this->name = $data["name"];
        }

        $this->title = $data["title"];
        $this->rules = $data["rules"];
        $this->style = (object) $data["settings"]["style"] || [
            "style" => "metabox",
            "position" => "normal"
        ];

        // Add all fields to the component
        if (is_array($data["fields"])) {
            foreach($data["fields"] as $field) {
                $this->add_field($field);
            }
        }
    }

    /**
     * Retrieves the component title
     *
     * @return string
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * Retrieves the component rules
     *
     * @return array[]|RuleCondition[]
     */
    public function get_rules() {
        return $this->rules;
    }

    /**
     * Adds a field to the component
     *
     * @param array $field The field data to be added
     * @return void
     */
    public function add_field($field) {
        $this->fields[] = $field;
    }

    /**
     * Retrieves all fields registered into this component
     *
     * @return array
     */
    public function get_fields() {
        return $this->fields;
    }

    /**
     * Checks if the components rules matches with the current context
     *
     * @return boolean
     */
    public function check() {
        foreach($this->rules as $key => $rule) {
            $condition = "AND";
            $conditions = [];

            // If it's a rule condition
            if ($rule instanceof RuleCondition) {
                $condition = $rule->condition;
                $conditions = $rule->conditions;
            } else
            // Check if a valid value was given
            if (!is_array($rule) && !is_object($rule)) {
                $condition = "AND";
                $conditions = [$rule];
            } else {
                throw new \Exception("Rule must not be an array or an object; can either be a string, boolean, number or RuleCondition instance.");
            }

            // Iterate over all conditions
            foreach($conditions as $key => $condition) {
                $value = $this->check_single($key, $condition);

                // If the rule condition is OR and the condition is met
                // break it
                if ($condition === "OR" && $value) {
                    break;
                } else
                // If the rule condition is AND and the condition was not met,
                // quit it
                if ($condition === "AND" && !$value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Checks a single rule
     *
     * @param string $property The rule property to be checked
     * @param string|boolean|int|float $check The value to be checked agains
     * @return boolean
     */
    private function check_single($property, $check) {
        switch($property) {
            // If it's checking for homepage
            case "is_homepage":
            case "is_home":
                if (\is_home() !== $check) {
                    return false;
                }
            break;

            // If it's checking for posts or pages
            case "is_post":
                $check = "post";
            case "is_page":
                $check = "page";

            // If it's checking for a post type
            case "is_post":
            case "is_page":
            case "post_type":
                if (\get_post_type() !== $check) {
                    return false;
                }
            break;

            // If it's checking it's a given post type archive page
            case "is_archive":
                if (is_bool($check)) {
                    if (\is_archive() !== $check) {
                        return false;
                    }
                } else {
                    if (!\is_archive($check)) {
                        return false;
                    }
                }
            break;
        }

        return true;
    }

    /**
     * Create a form instance for the component
     *
     * @return void
     */
    public function create_form() {
        $this->form = new Form([
            "id" => $this->name,
            "fields" => $this->get_fields()
        ]);
    }

    /**
     * Checks if this component has a created form
     *
     * @return boolean
     */
    public function has_form() {
        return !empty($this->form);
    }

    /**
     * Retrieves the form related to this component
     *
     * @return Core\Form
     */
    public function get_form() {
        return $this->form;
    }
}