<?php
namespace Framering;

class RuleCondition {
    public static function OR(array $conditions) {
        return new RuleCondition("OR", $conditions);
    }

    public static function AND(array $conditions) {
        return new RuleCondition("AND", $conditions);
    }

    /**
     * The condition type
     *
     * @var string
     */
    public $condition;

    /**
     * The conditions array
     *
     * @var string[]
     */
    public $conditions;

    public function __construct($condition, $conditions = []) {
        $this->condition = $condition;
        $this->conditions = $conditions;
    }
}