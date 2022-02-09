<?php
/**
 * Framering - Component class
 * @author Matheus Giovani <matheus@ad3com.com.br>
 * @author TechTail <falecom@techtail.net>
 */

namespace Framering;

/**
 * Represents a component
 * Can be extended and reused
 */
class Component {
    /**
     * The component title
     *
     * @var string
     */
    protected $title;

    /**
     * The array of fields
     *
     * @var array[]
     */
    protected $fields = [];

    /**
     * The rules that applies to this component
     * Can either be an array containing simple instructions
     * or a rule condition set containing a condition and a group of rules
     *
     * @var array[]|RuleCondition[]
     */
    protected $rules = [];

    public function __construct($data = []) {
        if (empty($data["title"])) {
            throw new \Exception("All components must have a name.");
        }

        if (empty($data["rules"])) {
            throw new \Exception("All components must have at least one rule.");
        }

        $this->title = $data["title"];
        $this->rules = $data["rules"];

        // Add all fields to the component
        if (is_array($data["fields"])) {
            foreach($data["fields"] as $field) {
                $this->add_field($field);
            }
        }
    }

    public function add_field(array $field) {
        $this->fields[] = $field;
    }

    /**
     * Checks if the components rules matches with the current context
     *
     * @return boolean
     */
    public function check() {
        foreach($this->rules as $rule) {
            $condition = "AND";
            $conditions = [];

            // If it's a rule condition
            if ($rule instanceof RuleCondition) {
                $condition = $rule->condition;
                $conditions = $rule->conditions;
            } else
            // If it's a simple AND rule array
            if (is_array($rule)) {
                $condition = "AND";
                $conditions = $rule;
            } else {
                throw new \Exception("Rule must be an array or a RuleCondition instance.");
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

    private function check_single($key, $check) {
        switch($key) {
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
    }
}