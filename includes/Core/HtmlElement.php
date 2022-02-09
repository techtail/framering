<?php
/**
 * Framering - HTML Element class
 * 
 * @author Matheus Giovani <matheus@ad3com.com.br>
 * @since 11/03/2021
 */

namespace Framering\Core;

class HtmlElement {
    /**
     * All tags that can be automatically closed
     * 
     * @var string[]
     */
    const AUTO_CLOSEABLE_TABS = ["input", "img"];

    /**
     * Tag attributes that doesn't need value
     * 
     * @var string[]
     */
    const TAG_PROPERTIES = ["async", "autofocus", "autoplay", "checked", "contenteditable", "default", "defer", "disabled", "draggable", "hidden",
                            "ismap", "loop", "multiple", "muted", "novalidate", "preload", "readonly", "required", "reversed", "selected", "spellcheck", "translate"];

    private $tag = "div";
    private $attributes = [];
    private $properties = [];

    private $inner_html = null;
    private $inner_text = null;
    private $children = [];

    private $dirty = true;
    private $result = null;

    public function __construct($tag = "div", array $options = []) {
        $this->tag = $tag;

        // Sets attributes if any was given
        $this->attributes = $options ?? [];

        // Check if any inner text was given
        if (isset($options["inner_text"])) {
            $this->set_inner_text($options["inner_text"]);
            unset($this->attributes["inner_text"]);
        }

        // Check if any inner html was given
        if (isset($options["inner_html"])) {
            $this->set_inner_html($options["inner_html"]);
            unset($this->attributes["inner_html"]);
        }

        // Check if any children elements was given
        if (isset($options["children"])) {
            // Iterate over all children and add them
            foreach($options["children"] as $child) {
                $this->append_child($child);
            }

            unset($this->attributes["children"]);
        }
    }

    /**
     * Returns the element inner content (HTML, text or children elements)
     *
     * @return string
     */
    public function get_inner_content() {
        $content = "";

        // Check if has inner HTML
        if (!empty($this->inner_html)) {
            $content = $this->inner_html;
        } else
        // Check if inner text
        if (!empty($this->inner_text)) {
            $content = htmlentities($this->inner_text);
        } else
        // Check if has children
        if (!empty($this->children)) {
            $content = implode("\n", array_map(function($item) {
                return $item->render(false);
            }, $this->children));
        }

        return $content;
    }

    /**
     * Sets the element inner HTML
     *
     * @param string $html The new inner HTML
     * @return void
     */
    public function set_inner_html(string $html) {
        $this->inner_html = $html;
        $this->inner_text = null;
        $this->children = [];

        $this->dirty = true;
    }

    /**
     * Sets the element inner text
     *
     * @param string $text The new inner text
     * @return void
     */
    public function set_inner_text(string $text) {
        $this->inner_text = $text;
        $this->inner_html = null;
        $this->children = [];

        $this->dirty = true;
    }

    /**
     * Appends a children element to it
     *
     * @param HTMLElement $el
     * @return void
     */
    public function append_child(HTMLElement $el) {
        $this->children[] = $el;

        $this->dirty = true;
    }

    /**
     * Checks if has any inner content
     *
     * @return boolean
     */
    public function has_inner_content() {
        return !empty($this->inner_html) || !empty($this->inner_text) || !empty($this->children);
    }

    /**
     * Sets an attribute of the element
     *
     * @param string $key The attribute name
     * @param mixed $value The attribute value
     * @return void
     */
    public function set_attribute(string $key, $value) {
        $this->attributes[$key] = $value;
        $this->dirty = true;
    }

    public function set_property(string $key, $value = true) {
        $this->attributes[$key] = $value;
        $this->dirty = true;
    }

    /**
     * Renders the element
     *
     * @param boolean $echo If needs to be echoed
     * @return string
     */
    public function render($echo = true) {
        // Check if it's dirty
        if ($this->dirty) {
            $el = ["<{$this->tag}"];

            // Iterate over all attributes
            foreach($this->attributes as $key => $value) {
                // Check if has no value.
                // Zeroes and empty strings should be allowed due
                // to some elements actually require those values.
                // For example, the default <option> element may
                // require an empty string as value if the parent <select> is marked as required.
                if (empty($value) && $value !== 0 && $value !== "") {
                    continue;
                }

                // Check if it's a property
                if (in_array($key, self::TAG_PROPERTIES)) {
                    $el[] = $key;
                } else {
                    $el[] = "{$key}=\"" . \esc_attr($value) . "\"";
                }
            }

            // Check if has no inner content
            if (!$this->has_inner_content()) {
                // Check if it's an auto closeable tag
                if (in_array($this->tag, self::AUTO_CLOSEABLE_TABS)) {
                    // Auto-close it
                    $el[] = "/>";
                } else {
                    // Append a closing tag to the element
                    $el[count($el) - 1] .= ">";

                    // Append the closing tag to it
                    $el[count($el) - 1] .= "</{$this->tag}>";
                }
            } else {
                // Append a closing tag to the element
                $el[count($el) - 1] .= ">";

                // Set the inner contents
                $el[count($el) - 1] .= $this->get_inner_content();

                // Append the closing tag to it
                $el[count($el) - 1] .= "</{$this->tag}>";
            }

            // Allow hooking
            $el = \apply_filters("ofertorio/html_element_render", $el, $this);

            // Implode all parts
            $this->result = implode(" ", $el);

            // Allow hooking
            $this->result = \apply_filters("ofertorio/html_element_rendered", $this->result, $this);

            if ($echo) {
                echo $this->result;
            }
        }

        return $this->result;
    }
}