<?php
/**
 * Framering - Form Helper
 * @author Matheus Giovani <matheus@ad3com.com.br>
 */

namespace Framering\Core;

/**
 * Checks if it's an associative array
 *
 * @param array $array The array to be checked
 * @return boolean
 */
function is_associative_array(array $array) {
    foreach(array_keys($array) as $item) {
        if (is_string($item)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Checks if it's a valid color
 *
 * @param string $string The color to be checked
 * @return boolean
 */
function is_color(string $string) {
    return preg_match("/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/im", $string) !== false;
}

final class Form {
    private static function render_field($data = null) {
		static $field_count = 0;

		// Check if any data is given
		if (is_null($data)) {
			return false;
		}

		// Set data defaults
		// This needs to contain all possible input values
		$data = (object) array_merge([
			"type" => "text",
			"name" => null,
			"placeholder" => null,
			"value" => null,
			"required" => false,
			"maxlength" => null,
			"class" => "form-control",
			"form" => false,
			"checked" => null,
			"readonly" => false,
			"disabled" => false,
			"id" => null,
			"default" => null,
			"options" => [],
			"attributes" => [],
			"step" => 1,
			"max" => null,
			"min" => null,
			"title" => null,
			"selected" => false,
			"return" => false,
			"multiple" => false,
			"tabindex" => $field_count,
			"pattern" => null,
			"mapToFront" => null,
			"accept" => null,
			"autocomplete" => null,
			// For buttons and textareas
			"text" => null
		], (array) $data);

		// Check if it's a HTML field
		if ($data->type === "html") {
			echo is_callable($data->content) ? call_user_func($data->content) : $data->content;
			return $data;
		} else
		// Check if type is divider
		if ($data->type === "divider") {
			echo "<hr/>";
			return $data;
		} else
		// Check if it's an editor
		if ($data->type === "editor") {
			// Enqueue the editor
			\wp_enqueue_editor();

			// Render the editor
			return \wp_editor($data->value, str_replace(["[", "]"], "", $data->name), [
				"textarea_name" => $data->name
			]);
		} else
		// Check if it's a CKEditor
		if ($data->type === "ckeditor") {
			// Set the data attribute to it
			$data->attributes["data-type"] = "ckeditor";

			// Set the settings for it
			$data->attributes["data-settings"] = json_encode(
				array_merge([
					"name" => $data->name
				], isset($data->ckeditor) ? $data->ckeditor : [])
			);

			// Set the type to textarea
			$data->type = "textarea";
		} else
        // Check if it's a repeater
        if ($data->type === "repeater") {
            $result = "<script type=\"text/framering-repeater\" data-for=\"{$data->name}\">";
                $result .= self::render_fields($data->fields, ["return" => true]);
            $result .= "</script>";

            $result .= "<div class=\"framering-repeater\" data-name=\"{$data->name}\">";
                $result .= "<div class=\"items\"></div>";
                $result .= "<div class=\"actions\">";
                    $result .= "<button class=\"button button-primary\" data-role=\"repeater-add-new\">";
                        $result .= \esc_html__("Add new", "framering");
                    $result .= "</button>";
                $result .= "</div>";
            $result .= "</div>";

            if ($data->return) {
                return $result;
            }

            echo $result;
            return;
        }

		// Check if options is callable
		if (is_callable($data->options)) {
			// Get the options array
			$data->options = call_user_func($data->options, $data);
		}

		// Check if any value is given
		if ($data->value === null && isset($_POST[$data->name])) {
			// Get given value
			$data->value = esc_attr($_POST[$data->name]);
		}

		// This will let us know if the field needs a tag
		// different than the "input" tag
		$data->needsTag = ($data->type === "textarea" || $data->type === "select" || $data->type === "button");

		// This will let us know if we need to close the field tag
		// and the status of the closure
		$data->isClosed = $data->needsTag ? false : true;

		// Check if field is a select
		if ($data->type === "select") {
			// Check if input has classes
			if ($data->class !== null) {
				// Add custom select class
				$data->class .= " custom-select";
			}

			if ($data->placeholder === null) {
				$data->placeholder = html_entity_decode(__("&mdash; Select &mdash;"));
			}

			// Set data-selected attribute
			$data->attributes["data-selected"] = \wp_json_encode($data->value);
		}

		// Check if field has an ID
		if ($data->id === null) {
			$data->id = "form{$data->name}";
		}

		// Check if it's a checkbox and has no value
		if ($data->type === "checkbox" && empty($data->value)) {
			$data->value = "1";
		}

		// Check if it's a date picker
		if ($data->type === "date") {
			// Fall back to the text input with a mask
			$data->type = "text";
			$data->attributes["data-mask"] = "99/99/9999";
		}

		// Check if it's a multiple select
		if ($data->type === "select" && $data->multiple === true) {
			// Fix the name
			$data->name .= "[]";
		}

		// Check if the field is disabled or readonly or it's a hidden field
		if ($data->readonly || $data->disabled || $data->type === "hidden") {
			// Set the tabindex to -1
			$data->tabindex = -1;
		} else {
			// Increase the field counter
			$field_count++;
			$data->tabindex = $field_count;
		}

		/**
		 * Start rendering the input
		 */

		// If it's a hidden field, remove the default class from it
		if ($data->type === "hidden" && $data->class === "form-control") {
			$data->class = null;
		}

		// Create the field
		$field = new HtmlElement($data->needsTag ? $data->type : "input", [
			"type" => !$data->needsTag ? $data->type : null,
			"class" => $data->class,
			"name" => $data->name,
			"id" => $data->name,
			"placeholder" => !$data->needsTag ? $data->placeholder : null,
			"maxlength" => $data->maxlength,
			"form" => $data->form !== false ? var_export($data->form, true) : null,
			"required" => $data->required,
			"readonly" => $data->readonly,
			"disabled" => $data->disabled,
			"checked" => $data->checked,
			"multiple" => $data->multiple,
			"tabindex" => $data->readonly || $data->disabled ? null : $data->tabindex,
			"autocomplete" => $data->autocomplete === false ? "false" : $data->autocomplete,
			"pattern" => $data->pattern
		]);

		// Check if it's a number input
		if ($data->type === "number") {
			// Check if field has a step attribute
			if ($data->step !== null) {
				$field->set_attribute("step", $data->step);
			}

			// Check if field has a min attribute
			if ($data->min !== null) {
				$field->set_attribute("min", $data->min);
			}

			// Check if field has a max attribute
			if ($data->max !== null) {
				$field->set_attribute("max", $data->max);
			}
		}

		// Check if it's a file picker
		if ($data->type === "file") {
			// Check if has an accept attribute
			if ($data->accept !== null) {
				$field->set_attribute("accept", $data->accept);
			}

			// Check if it's an image picker
			if ($data->picker_type === "image") {
				// Display the image placeholder
				echo "<img src=\"" . $data->picker_placeholder . "\" class=\"picker-preview\" />";
			}
		}

		// Iterate over all extra attributes
		foreach($data->attributes as $key => $value) {
			$field->set_attribute($key, $value);
		}

		// Check if field has a value
		if (!empty($data->value) || count($data->options) > 0) {
			// Applies the mapper function
			if ($data->mapToFront !== null) {
				$data->value = \call_user_func_array($data->mapToFront, [$data->value]);
			}

			// Check if field needs a tag
			if (!$data->needsTag) {
				// Render as an attribute
				$field->set_attribute("value", $data->value);
			} else {
				// Check if it's a select
				if ($data->type === "select") {
					// Check if select has a placeholder
					if ($data->placeholder !== null) {
						// Check if if an object
						if (is_array($data->placeholder)) {
							// Add placeholder to the array
							array_unshift($data->options, $data->placeholder);
						} else {
							// Append the placeholder option to it
							$field->append_child(new HtmlElement("option", [
								"disabled" => true,
								"selected" => empty($data->value),
								"inner_text" => $data->placeholder,
								// In case "required" is true, this value should be at least
								// filled with an empty string to make the client's browser validate
								"value" => ""
							]));
						}
					}

					$is_option_selected = false;

					$is_assoc = is_associative_array($data->options);

					// Iterate over all options
					foreach($data->options as $option_key => $option) {
						// Check if it's not and array and it's not an object
						if (!is_array($option) && !is_object($option)) {
							// Parse it as name and value
							$option = (object) [
								"name" => $option,
								"value" => $is_assoc ? $option_key : $option,
							];
						} else {
							// Check if it's not an associative array
							// Force it to be an object
							$option = (object) $option;
						}
						
						// Check if the current option has a "selected" param
						// and it is the selected
						if (isset($option->selected) && $option->selected === true) {
							$is_option_selected = true;
						} else
						// Check if the field is a multiple select
						if ($data->multiple === true && is_array($data->value)) {
							// It's checked if the current option value is inside the field value array
							$is_option_selected = in_array($option->value, $data->value);
						} else {
							$is_option_selected = $option->value == $data->value;
						}

						// Append the option to the input children
						$field->append_child(new HtmlElement("option", [
							"value" => $option->value,
							"disabled" => isset($option->disabled) ? @$option->disabled : false, 
							"selected" => $is_option_selected,
							"inner_text" => $option->name
						]));
					}
				} else
				// Check if it's a textarea
				if ($data->type === "textarea") {
					// Just place the value
					$field->set_inner_html(!empty($data->text) ? $data->text : $data->value);
				}
			}
		} else
		// If it's a button and has text or value
		if ($data->type === "button" && (!empty($data->value) || !empty($data->text))) {
			// Set the inner html
			$field->set_inner_html(!empty($data->text) ? $data->text : $data->value);
		}
		// Applies the default value if needed
		if ($data->value === null && $data->default !== null) {
			// Not for checkboxes (not sure why)
			if ($data->type !== "checkbox") {
				$field->set_attribute("value", $data->default);
			}
		}

		if ($data->return === true) {
			return $field->render(false);
		} else {
			$field->render();

			return $data;
		}
	}

    /**
     * Field types that doesn't need containers
     */
    const NO_CONTAINER_FIELD_TYPES = [
        "hidden"
    ];

    /**
     * Form ID
     *
     * @var string|int
     */
    public $id = null;

    /**
     * Fields handler
     *
     * @var array
     */
    private $fields = [];

    /**
     * The form title, if any
     *
     * @var string
     */
    private $title = null;

    /**
     * Form method, POST or GET
     *
     * @var string
     */
    private $method = "post";

    /**
     * The current form submitted data
     *
     * @var array
     */
    private $data = null;

    /**
     * The form action URL
     *
     * @var string
     */
    private $action = null;

    /**
     * Form save status
     *
     * @var boolean
     */
    public $was_saved = false;

    /**
     * Any alerts to be displayed
     *
     * @var array
     */
    private $alerts = [];

    /**
     * Form class string
     *
     * @var string
     */
    private $class = "";

    /**
     * Form input groups
     *
     * @var array
     */
    private $groups = [
        null => []
    ];

    public $submit_text = null;
    public $submit_class;

    /**
     * If the labels can be displayed
     *
     * @var boolean
     */
    private $labels = true;

    /**
     * If the form can be submitted
     *
     * @var boolean
     */
    public $can_submit = true;

    /**
     * If an render the optional indicators
     *
     * @var boolean
     */
    public $optional_indicators = true;

    /**
     * If the form ID can be included in the form action as a hash
     *
     * @var boolean
     */
    protected $action_include_hash = true;

    /**
     * If can allow empty field values
     *
     * @var boolean
     */
    public $allow_empty_field_values = false;

    /**
     * The amount of margin between fields (from 0 to 5)
     *
     * @var integer
     */
    public $field_margin_amount = 5;

    public function __construct($data = []) {
        $this->id = isset($data["id"]) ? $data["id"] : $this->id;
        $this->title = isset($data["title"]) ? esc_html($data["title"]) : $this->title;
        $this->method = isset($data["method"]) ? strtolower(esc_attr($data["method"])) : $this->method;
        $this->action = isset($data["action"]) ? esc_url($data["action"]) : $this->action;

        $this->submit_text = isset($data["submit_text"]) ? esc_html($data["submit_text"]) : __("Salvar alterações", "framering");
        $this->submit_class = isset($data["submit_class"]) ? esc_attr($data["submit_class"]) : $this->submit_class;
        $this->labels = $data["labels"] ?? true;

        $this->class = isset($data["class"]) ? esc_attr($data["class"]) : $this->class;

        $this->data = $this->method === "post" ? $_POST : $_GET;

        $this->action_include_hash = isset($data["action_include_hash"]) ? boolval($data["action_include_hash"]) : $this->action_include_hash;
        $this->optional_indicators = isset($data["optional_indicators"]) ? boolval($data["optional_indicators"]) : $this->optional_indicators;
        
        $this->allow_empty_field_values = isset($data["allow_empty_field_values"]) ? boolval($data["allow_empty_field_values"]) : $this->allow_empty_field_values;

        $this->field_margin_amount = isset($data["field_margin_amount"]) ? $data["field_margin_amount"] : $this->field_margin_amount;

        // Check if any field was given
        if (!empty($data["fields"])) {
            // Process them
            foreach($data["fields"] as $field) {
                $this->add_field($field);
            }
        }
    }

    /**
     * Checks if can include the hash in the form action
     *
     * @return boolean
     */
    public function can_include_hash(): bool {
        return $this->action_include_hash;
    }

    /**
     * Sets if can include the hash in the form action
     *
     * @param boolean $can_include
     * @return void
     */
    public function set_include_hash(bool $can_include) {
        $this->action_include_hash = $can_include;
    }

    /**
     * Clear the form data
     *
     * @return void
     */
    public function clear() {
        $this->data = null;
    }

    /**
     * Creates a new form group
     *
     * @param string $name The group name
     * @return string
     */
    public function create_group($name) {
        $this->groups[$name] = [];
        return $name;
    }

    /**
     * Add an alert to be rendered
     *
     * @param string $type The alert type
     * @param string $message The alert message
     * @param array $data The alert data, if any
     * @return void
     */
    public function add_alert(string $type, string $message, array $data = []) {
        $this->alerts[] = [
            "message" => $message,
            "type" => $type,
            "data" => $data
        ];
    }

    /**
     * Get the current form request data
     *
     * @return array
     */
    private function get_request_data() {
        return $this->id !== null && isset($this->data[$this->id]) ? $this->data[$this->id] : $this->data;
    }

    /**
     * Checks if it's submitting the form
     *
     * @return boolean
     */
    public function is_submit() {
        return ($this->method === "post" && $_SERVER["REQUEST_METHOD"] === "POST") || !empty($_REQUEST[$this->id]);
    }

    /**
     * Parse the form inputs with the given data
     *
     * @param array $data The data to parse the inputs
     * @return void
     */
    public function parse($data = []) {
        if ($data != $this->data) {
            // Iterate over all data
            foreach($data as $key => $value) {
                // Check if the field exists
                if (isset($this->fields[$key])) {
                    // Set this field new value
                    $this->fields[$key]["value"] = $value;
                }
            }
        }

        // Iterate over all request data
        // because the requested data overwrite the original data
        foreach($this->get_request_data() as $key => $value) {
            // Check if the field exists
            if (isset($this->fields[$key])) {
                // Set this field new value
                $this->fields[$key]["value"] = $value;
            }
        }
    }

    /**
     * Disables all form inputs
     *
     * @return void
     */
    public function disable() {
        foreach($this->fields as &$field) {
            $field["disabled"] = true;
        }
    }

    /**
     * Enables all form inputs
     *
     * @return void
     */
    public function enable() {
        foreach($this->fields as &$field) {
            unset($field["disabled"]);
        }
    }

    /**
     * Get an array value by a path
     *
     * @param array $arr The array
     * @param string $path The path separated by $sep
     * @param string $sep The path separator
     * @return mixed
     */
    private function get_by_path(array $arr, string $path, string $sep = ".") {
        // Extract all the keys by the separator
        $keys = explode($sep, $path);

        // Retrieve the first key
        $first = array_shift($keys);

        // Check if the first key is set inside the array
        if (isset($arr[$first])) {
            // Check if it's an array and has more keys
            if (is_array($arr[$first]) && count($keys) > 0) {
                // Get it by path again
                return $this->get_by_path($arr[$first], implode($sep, $keys), $sep);
            } else {
                // Return the first element
                return $arr[$first];
            }
        } else {
            return null;
        }
    }

    /**
     * Set an array value by a path
     *
     * @param array &$arr The array reference
     * @param mixed $value The value to be setted
     * @param string $path The path separated by $sep
     * @param string $sep The path separator
     * @return array
     */
    private function set_by_path(&$arr, $value, $path, $sep = ".") {
        $keys = explode($sep, $path);

        foreach($keys as $key) {
            $arr = &$arr[$key];
        }

        $arr = $value;

        return $arr;
    }

    /**
     * Parse a form into an array
     *
     * @param array $fields
     * @param array $data
     * @param array $options
     * @return array
     */
    private function parse_form($fields, $data, $options = []) {
        /**
         * Final data handler
         * 
         * @var array
         */
        $final_data = [];

        // Extract the options
        $disable_sql_escape = isset($options["disable-sql-escape"]) && $options["disable-sql-escape"] === true;

        // Iterate over all fields
        foreach($fields as $field) {
            // Check if the fields is readonly or disabled
            if ((isset($field["disabled"]) && $field["disabled"]) || (isset($field["readonly"]) && $field["readonly"])) {
                continue;
            }

            // Get the field name
            $name = isset($field["original_name"]) ? $field["original_name"] : $field["name"];

            // Get the parsed field name
            $parsed = $this->parse_field_name($name, true);
            $path = implode(".", $parsed);

            // Try to get the field value
            $value = $this->get_by_path($data, $path);

            // Switch the field type
            switch($field["type"]) {
                // If it's text or no type has been met
                case "text":
                default:
                    // Sanitize or unescape it
                    $value = $disable_sql_escape ? str_replace("\\'", "'", $value) : \sanitize_text_field($value);
                break;

                // If it's a textarea
                case "textarea":
                    // Sanitize or unescape it
                    $value = $disable_sql_escape ? str_replace("\\'", "'", $value) : \sanitize_textarea_field($value);
                break;

                // If it's a number
                case "number":
                    // Sanitize the number as a float
                    // because it can be an integer or a float
                    $value = floatval($value);
                break;

                // If it's a select
                case "select":
                    $is_valid_option = false;

                    // Retrieve all field options
                    $options = $field["options"];

                    // Retrieve all options
                    if (is_callable($options)) {
                        $options = call_user_func_array($options, [$field]);
                    }

                    $is_assoc = is_associative_array($options);

                    // Iterate over all select options
                    foreach($options as $option_key => $option) {
                        // Check if it's a multiple select
                        if (isset($field["multiple"]) && $field["multiple"] === true && is_array($value)) {
                            // Check if it's the selected option
                            if (in_array($option["value"], $value)) {
                                $is_valid_option = true;
                                break;
                            }
                        } else
                        // Check if it's the selected option
                        if (($is_assoc && $option_key == $value) || (!$is_assoc && $option["value"] == $value)) {
                            $is_valid_option = true;
                            break;
                        }
                    }

                    // Check if the selected value is a valid option
                    if (!$is_valid_option) {
                        return new \WP_Error("empty_field", sprintf(__("A opção selecionada no campo \"%s\" é inválida.", "framering"), $field["title"]));
                    }
                break;

                // If it's an editor
                case "editor":
                case "ckeditor":
                    // Leave it as is
                    $value = $value;
                break;

                // If it's a color
                case "color":
                    if (!is_color($value)) {
                        return new \WP_Error("invalid_field", sprintf(__("O código de cor informado é inválido.", "framering"), $field["title"]));
                    }

                    $value = \sanitize_hex_color($value);
                break;

                // If it's an e-mail
                case "email":
                    if (!\is_email($value)) {
                        return new \WP_Error("empty_field", sprintf(__("O endereço de e-mail informado é inválido.", "framering"), $field["title"]));
                    }

                    $value = \sanitize_email($value);
                break;

                // Check if it's a checkbox
                case "checkbox":
                    $value = isset($value);
                break;
            }

            // Check if the field data is not set or empty
            if (empty($value)) {
                // Check if the field is required
                if (isset($field["required"]) && $field["required"] === true) {
                    // Throw an error
                    return new \WP_Error("empty_field", sprintf(__("The field \"%s\" is required.", "framering"), $field["title"]));
                } else
                // Check if the field has a default value
                if (isset($field["default"])) {
                    // Set the value to the default value
                    $value = $field["default"];
                } else
                // If it's not a checkbox
                if ($field["type"] !== "checkbox") {
                    // Check if doesn't allow empty fields
                    if (!$this->allow_empty_field_values) {
                        // Just skip it, can be a custom or HTML field for example
                        continue;
                    }
                }
            }

            // Set the value by path
            $this->set_by_path($final_data, $value, $path);

            // Update the internal field value
            $this->fields[$name][$field["type"] === "checkbox" ? "checked" : "value"] = $value;
        }

        return $final_data;
    }

    /**
     * Process the form fields
     *
     * @param array $data The data to be processed
     * @param array $options Any options to be passed to the parser
     * @return array|WP_Error
     */
    public function process($data = null, $options = [
        "disable-sql-escape" => false
    ]) {
        // Check if no data has been given
        if ($data === null) {
            // Set it to the $_REQUEST var
            $data = $this->get_request_data();
        }

        // Parse the form
        $final_data = $this->parse_form($this->fields, $data, $options);

        $this->was_saved = true;

        return $final_data;
    }

    /**
     * Creates a new dummy form instance, processes and parses all fields on it
     *
     * @param string[] $fields The fields to be parsed
     * @param string[] $data The data to be matched against the fields
     * @return array|WP_Error
     */
    public static function process_fields($fields, $data = null, array $options = []) {
        return (
            new self(
                array_merge($options, [
                    "fields" => $fields
                ])
            )
        )->process($data);
    }

    /**
     * Sets the form title
     *
     * @param string $title
     * @return void
     */
    public function set_title($title) {
        $this->title = $title;
    }

    /**
     * Sets the form action
     *
     * @param string $action
     * @return void
     */
    public function set_action($action) {
        $this->action = $action;
    }

    /**
     * Sets the form method
     *
     * @param string $method Needs to be POST or GET. Will become lowercase.
     * @return void
     */
    public function set_method($method) {
        $this->method = strtolower($method);
    }

    /**
     * Parse a single field name
     *
     * @param string $name
     * @param boolean $as_array
     * @return string|array
     */
    private function parse_field_name($name, $as_array = false) {
        // Parse the string
        parse_str($name, $args);

        // Get the root name
        $final_name = !$as_array ? array_keys($args)[0] : [array_keys($args)[0]];

        $values = array_values($args);

        // Check if have children
        if (is_array($values[0]) && count($values[0]) > 0) {
            if ($as_array) {
                $final_name = array_merge($final_name, $this->parse_field_subnames($args, true));
            } else {
                $final_name .= $this->parse_field_subnames($args, true);
            }
        }

        return $final_name;
    }

    /**
     * Parse a single field subnames
     *
     * @param array $field
     * @param boolean $as_array
     * @return string|array
     */
    private function parse_field_subnames($field, $as_array = false) {
        $final_name = $as_array ? [] : "";

        foreach($field as $key => $name) {
            if (is_array($name)) {
                if ($as_array) {
                    $final_name += $this->parse_field_subnames($name, true);
                } else {
                    $final_name .= $this->parse_field_subnames($name);
                }
            } else
            if (strlen($name) === 0) {
                // Reached the end
                if ($as_array) {
                    $final_name[] = $key;
                } else {
                    $final_name .= "[" . $key . "]";
                }
                break;
            } else {
                if ($as_array) {
                    $final_name[] = $name;
                } else {
                    $final_name .= "[" . $name . "]";
                }
            }
        }

        return $final_name;
    }

    /**
     * Creates a field with the form data
     *
     * @param array $field Field data
     * @return array
     */
    public function create_field(array $field) {
        // Check if the field has a name
        if (isset($field["name"])) {
            // Parse the field name
            parse_str($field["name"], $args);

            // Check if still has arguments
            if (is_array(array_values($args)[0])) {
                // Update it
                $field["name"] = array_keys($args)[0];
                $field["name"] .= $this->parse_field_subnames($args);
            } else {
                // Set the base name
                $field["name"] = array_keys($args)[0];
            }
        }

        if (isset($field["name"])) {
            // Save the field original name
            $field["original_name"] = $field["name"];
        }

        // Check if the form has an ID and the field has a name
        if ($this->id !== null && isset($field["name"])) {
            // Add the form ID to the field
            $new_name = "framering[" . $field["name"];

            // Check if is opening any tag inside the name
            if (strpos($field["name"], "[") > -1) {
                // Replace it with a closing and opening one
                $new_name = substr_replace($new_name, "][", strrpos($new_name, "["), 1);
            }

            // Check if has no closing tag
            if ($new_name[strlen($new_name) - 1] !== "]") {
                // Add a closing tag
                $new_name .= "]";
            }

            $field["name"] = $new_name;
        }

        // Check if the field has no name
        if (!isset($field["name"])) {
            // Check if it has an internal name
            if (!isset($field["_name"])) {
                // Create an internal name for it
                $field["_name"] = uniqid("fr-field-");
            }

            // Get the field internal name
            $field["name"] = $field["_name"];

            // Update the field original name
            $field["original_name"] = $field["name"];
        }

        // Check if the field has a title
        if (isset($field["title"])) {
            // Escape it correctly
            $field["title"] = \esc_html($field["title"]);
        }

        return $field;
    }

    /**
     * Add a field to the form
     *
     * @param array $field Field data
     * @return void
     */
    public function add_field(array $field) {
        // Creates the field data
        $field = $this->create_field($field);

        // Save the field
        $this->fields[$field["original_name"]] = $field;

        return true;
    }

    /**
     * Removes a field from the form
     *
     * @param string $name The field name
     * @return void
     */
    public function remove_field(string $name) {
        unset($this->fields[$name]);
    }

    /**
     * Sets a single field value
     *
     * @param string $field The field value
     * @param mixed $value The new field value
     * @return void
     */
    public function set_field_value(string $field, $value) {
        if (!isset($this->fields[$field])) {
            return;
        }

        $this->fields[$field]["value"] = \maybe_unserialize($value);
    }

    /**
     * Add a nonce field to the form
     *
     * @param int|string $action The action name
     * @param string $name The nonce field name
     * @param boolean $referer Include the referer?
     * @return void
     */
    public function add_nonce_field($action, $name = "_wpnonce", $referer = true) {
        return $this->add_field([
            "_name" => $name,
            "type" => "nonce",
            "content" => \wp_nonce_field($action, $name, $referer, false)
        ]);
    }

    /**
     * Get a field by the field ID
     *
     * @param integer $field_id
     * @return array
     */
    public function get_field(int $field_id) {
        return $this->fields[$field_id];
    }

    /**
     * Retrieve all form fields
     *
     * @return array[array]
     */
    public function get_fields() {
        return $this->fields;
    }

    /**
     * Set all form fields
     *
     * @param array $fields
     * @return void
     */
    public function set_fields(array $fields) {
        $this->fields = $fields;
    }

    /**
     * Checks if can render the (optional) field indicator
     *
     * @return boolean
     */
    public function can_render_optional_indicators() {
        return $this->optional_indicators;
    }

    public function render() {
        return self::render_fields($this->fields, [
            "data" => $this->data
        ]);
    }

    /**
     * Render fields for a form
     *
     * @param array $fields An array of fields to be rendererd
     * @param string $layout The form layout (normal, bootstrap or table)
     * @return void
     */
    public static function render_fields($fields, $options = []) {
        $options = array_merge([
            "labels" => true,
            "return" => false,
            "optional_indicators" => true,
            "prefix" => "",
            "suffix" => ""
        ], $options);

        $result = "";

        // Iterate over all fields
        foreach($fields as $field) {
            if (!empty($options["prefix"])) {
                $field["name"] = $options["prefix"] . $field["name"];
            }

            if (!empty($options["suffix"])) {
                $field["name"] .= $options["suffix"];
            }

            // Set the field ID to the field name
            $field["id"] = $field["name"];
            $name = $field["name"];

            // Check if it's a nonce field
            if ($field["type"] === "nonce") {
                // Display it and continue
                $result .= $field["content"];
                continue;
            }

            // Set the field value if any
            if (isset($options["data"][$name])) {
                $field["value"] = $options["data"][$name];
            }
        
            $result .= "<div class=\"framering-form-group\">";
                if ($options["labels"] && isset($field["title"])) {
                    $result .= "<label for=\"" . $field["name"] . "\">" . esc_html($field["title"]) . "</label>";
                }

                $result .= "<div class=\"framering-input\">";
                    // Render it
                    $field["return"] = true;
                    $result .= self::render_field($field);

                    // Check if the field has a description
                    if (isset($field["description"])) {
                        // Display it
                        $result .= "<p class=\"description\">";
                            $result .= nl2br($field["description"]);
                        $result .= "</p>";
                    }
                $result .= "</div>";
            $result .= "</div>";
        }

        if ($options["return"]) {
            return $result;
        }

        echo $result;
    }
}