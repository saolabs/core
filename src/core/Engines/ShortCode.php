<?php

namespace Saola\Core\Engines;

class ShortCode
{
    public $tags = [];

    /**
     * Adds a new shortcode.
     *
     * @param string   $tag      Shortcode tag to be searched in post content.
     * @param callable $callback The callback function to run when the shortcode is found.
     *                           Every shortcode callback is passed three parameters by default,
     *                           including an array of attributes (`$atts`), the shortcode content
     *                           or null if not set (`$content`), and finally the shortcode tag
     *                           itself (`$shortcode_tag`), in that order.
     */
    public function add($tag, $callback)
    {

        if ('' == trim($tag)) {
            return;
        }

        if (0 !== preg_match('@[<>&/\[\]\x00-\x20=]@', $tag)) {
            return;
        }

        $this->tags[$tag] = $callback;
    }


    /**
     * Removes hook for shortcode.
     *
     * @param string $tag Shortcode tag to remove hook for.
     */
    public function remove($tag = null)
    {
        if (!$tag) {
            $this->tags = [];
            return true;
        }
        unset($this->tags[$tag]);
    }


    /**
     * Whether a registered shortcode exists named $tag
     *
     * @since 3.6.0
     *
     * @global array $shortcode_tags List of shortcode tags and their callback hooks.
     *
     * @param string $tag Shortcode tag to check.
     * @return bool Whether the given shortcode exists.
     */
    public function exists($tag)
    {
        return array_key_exists($tag, $this->tags);
    }


    /**
     * Whether the passed content contains the specified shortcode
     *
     * @since 3.6.0
     *
     * @global array $shortcode_tags
     *
     * @param string $content Content to search for shortcodes.
     * @param string $tag     Shortcode tag to check.
     * @return bool Whether the passed content contains the given shortcode.
     */
    public function has($content, $tag)
    {
        if (false === strpos($content, '[')) {
            return false;
        }

        if ($this->exists($tag)) {
            preg_match_all('/' . $this->getShortcodeRegex() . '/', $content, $matches, PREG_SET_ORDER);
            if (empty($matches)) {
                return false;
            }

            foreach ($matches as $shortcode) {
                if ($tag === $shortcode[2]) {
                    return true;
                } elseif (!empty($shortcode[5]) && $this->has($shortcode[5], $tag)) {
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * Retrieve the shortcode regular expression for searching.
     *
     * The regular expression combines the shortcode tags in the regular expression
     * in a regex class.
     *
     * The regular expression contains 6 different sub matches to help with parsing.
     *
     * 1 - An extra [ to allow for escaping shortcodes with double [[]]
     * 2 - The shortcode name
     * 3 - The shortcode argument list
     * 4 - The self closing /
     * 5 - The content of a shortcode when it wraps some content.
     * 6 - An extra ] to allow for escaping shortcodes with double [[]]
     *
     * @since 2.5.0
     * @since 4.4.0 Added the `$tagnames` parameter.
     *
     * @global array $shortcode_tags
     *
     * @param array $tagnames Optional. List of shortcodes to find. Defaults to all registered shortcodes.
     * @return string The shortcode search regular expression
     */
    public function getShortcodeRegex($tagnames = null)
    {

        if (empty($tagnames)) {
            $tagnames = array_keys($this->tags);
        }
        $tagregexp = join('|', array_map('preg_quote', $tagnames));

        // WARNING! Do not change this regex without changing do_shortcode_tag() and strip_shortcode_tag().
        // Also, see shortcode_unautop() and shortcode.js.

        // phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound -- don't remove regex indentation
        return '\\['                             // Opening bracket.
            . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]].
            . "($tagregexp)"                     // 2: Shortcode name.
            . '(?![\\w-])'                       // Not followed by word character or hyphen.
            . '('                                // 3: Unroll the loop: Inside the opening shortcode tag.
            .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash.
            .     '(?:'
            .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket.
            .         '[^\\]\\/]*'               // Not a closing bracket or forward slash.
            .     ')*?'
            . ')'
            . '(?:'
            .     '(\\/)'                        // 4: Self closing tag...
            .     '\\]'                          // ...and closing bracket.
            . '|'
            .     '\\]'                          // Closing bracket.
            .     '(?:'
            .         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags.
            .             '[^\\[]*+'             // Not an opening bracket.
            .             '(?:'
            .                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag.
            .                 '[^\\[]*+'         // Not an opening bracket.
            .             ')*+'
            .         ')'
            .         '\\[\\/\\2\\]'             // Closing shortcode tag.
            .     ')?'
            . ')'
            . '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]].
        // phpcs:enable
    }

    /**
     * Search content for shortcodes and filter shortcodes through their hooks.
     *
     * This function is an alias for do_shortcode().
     *
     * @since 5.4.0
     *
     * @see do_shortcode()
     *
     * @param string $content     Content to search for shortcodes.
     * @param bool   $ignore_html When true, shortcodes inside HTML elements will be skipped.
     *                            Default false.
     * @return string Content with shortcodes filtered out.
     */
    public function apply($content, $ignore_html = false)
    {
        return $this->doShortCode($content, $ignore_html);
    }


    /**
     * Search content for shortcodes and filter shortcodes through their hooks.
     *
     * If there are no shortcode tags defined, then the content will be returned
     * without any filtering. This might cause issues when plugins are disabled but
     * the shortcode will still show up in the post or content.
     *
     * @since 2.5.0
     *
     * @global array $shortcode_tags List of shortcode tags and their callback hooks.
     *
     * @param string $content     Content to search for shortcodes.
     * @param bool   $ignore_html When true, shortcodes inside HTML elements will be skipped.
     *                            Default false.
     * @return string Content with shortcodes filtered out.
     */
    function doShortCode($content, $ignore_html = false)
    {

        if (false === strpos($content, '[')) {
            return $content;
        }

        if (empty($this->tags) || !is_array($this->tags)) {
            return $content;
        }

        // Find all registered tag names in $content.
        preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
        $tagnames = array_intersect(array_keys($this->tags), $matches[1]);

        if (empty($tagnames)) {
            return $content;
        }

        $content = $this->doShortcodesInHtmlTags($content, $ignore_html, $tagnames);
        

        $pattern = $this->getShortcodeRegex($tagnames);
        
        $content = preg_replace_callback("/$pattern/", 'Saola\Core\Engines\do_shortcode_tag', $content);

        // Always restore square braces so we don't break things like <!--[if IE ]>.
        $content = $this->unescapeInvalidShortcodes($content);

        return $content;
    }


    /**
     * Search only inside HTML elements for shortcodes and process them.
     *
     * Any [ or ] characters remaining inside elements will be HTML encoded
     * to prevent interference with shortcodes that are outside the elements.
     * Assumes $content processed by KSES already.  Users with unfiltered_html
     * capability may get unexpected output if angle braces are nested in tags.
     *
     * @since 4.2.3
     *
     * @param string $content Content to search for shortcodes
     * @param bool $ignore_html When true, all square braces inside elements will be encoded.
     * @param array $tagnames List of shortcodes to find.
     * @return string Content with shortcodes filtered out.
     */
    public function doShortcodesInHtmlTags($content, $ignore_html, $tagnames)
    {
        // Normalize entities in unfiltered HTML before adding placeholders.
        $trans   = array(
            '&#91;' => '&#091;',
            '&#93;' => '&#093;',
        );
        $content = strtr($content, $trans);
        $trans   = array(
            '[' => '&#91;',
            ']' => '&#93;',
        );

        $pattern = $this->getShortcodeRegex($tagnames);
        $textarr = $this->htmlSplit($content);

        foreach ($textarr as &$element) {
            if ('' == $element || '<' !== $element[0]) {
                continue;
            }

            $noopen  = false === strpos($element, '[');
            $noclose = false === strpos($element, ']');
            if ($noopen || $noclose) {
                // This element does not contain shortcodes.
                if ($noopen xor $noclose) {
                    // Need to encode stray '[' or ']' chars.
                    $element = strtr($element, $trans);
                }
                continue;
            }

            if ($ignore_html || '<!--' === substr($element, 0, 4) || '<![CDATA[' === substr($element, 0, 9)) {
                // Encode all '[' and ']' chars.
                $element = strtr($element, $trans);
                continue;
            }

            $attributes = wp_kses_attr_parse($element);
            if (false === $attributes) {
                // Some plugins are doing things like [name] <[email]>.
                if (1 === preg_match('%^<\s*\[\[?[^\[\]]+\]%', $element)) {
                    $element = preg_replace_callback("/$pattern/", 'Saola\Core\Engines\do_shortcode_tag', $element);
                }

                // Looks like we found some crazy unfiltered HTML. Skipping it for sanity.
                $element = strtr($element, $trans);
                continue;
            }

            // Get element name.
            $front   = array_shift($attributes);
            $back    = array_pop($attributes);
            $matches = array();
            preg_match('%[a-zA-Z0-9]+%', $front, $matches);
            $elname = $matches[0];

            // Look for shortcodes in each attribute separately.
            foreach ($attributes as &$attr) {
                $open  = strpos($attr, '[');
                $close = strpos($attr, ']');
                if (false === $open || false === $close) {
                    continue; // Go to next attribute. Square braces will be escaped at end of loop.
                }
                $double = strpos($attr, '"');
                $single = strpos($attr, "'");
                if ((false === $single || $open < $single) && (false === $double || $open < $double)) {
                    /*
				 * $attr like '[shortcode]' or 'name = [shortcode]' implies unfiltered_html.
				 * In this specific situation we assume KSES did not run because the input
				 * was written by an administrator, so we should avoid changing the output
				 * and we do not need to run KSES here.
				 */
                    $attr = preg_replace_callback("/$pattern/", 'Saola\Core\Engines\do_shortcode_tag', $attr);
                } else {
                    // $attr like 'name = "[shortcode]"' or "name = '[shortcode]'".
                    // We do not know if $content was unfiltered. Assume KSES ran before shortcodes.
                    $count    = 0;
                    // $new_attr = preg_replace_callback("/$pattern/", 'do_shortcode_tag', $attr, -1, $count);
                    // if ($count > 0) {
                    //     // Sanitize the shortcode output using KSES.
                    //     $new_attr = wp_kses_one_attr($new_attr, $elname);
                    //     if ('' !== trim($new_attr)) {
                    //         // The shortcode is safe to use now.
                    //         $attr = $new_attr;
                    //     }
                    // }
                }
            }
            $element = $front . implode('', $attributes) . $back;

            // Now encode any remaining '[' or ']' chars.
            $element = strtr($element, $trans);
        }

        $content = implode('', $textarr);

        return $content;
    }


    /**
     * Remove placeholders added by do_shortcodes_in_html_tags().
     *
     * @since 4.2.3
     *
     * @param string $content Content to search for placeholders.
     * @return string Content with placeholders removed.
     */
    public function unescapeInvalidShortcodes($content)
    {
        // Clean up entire string, avoids re-parsing HTML.
        $trans = array(
            '&#91;' => '[',
            '&#93;' => ']',
        );

        $content = strtr($content, $trans);

        return $content;
    }


    /**
     * Separate HTML elements and comments from the text.
     *
     * @since 4.2.4
     *
     * @param string $input The text which has to be formatted.
     * @return string[] Array of the formatted text.
     */
    public function htmlSplit($input)
    {
        return preg_split($this->getHtmlSplitRegex(), $input, -1, PREG_SPLIT_DELIM_CAPTURE);
    }

        
    /**
     * Retrieve the regular expression for an HTML element.
     *
     * @since 4.4.0
     *
     * @staticvar string $regex
     *
     * @return string The regular expression
     */
    public function getHtmlSplitRegex() {
        static $regex;

        if ( ! isset( $regex ) ) {
            // phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound -- don't remove regex indentation
            $comments =
                '!'             // Start of comment, after the <.
                . '(?:'         // Unroll the loop: Consume everything until --> is found.
                .     '-(?!->)' // Dash not followed by end of comment.
                .     '[^\-]*+' // Consume non-dashes.
                . ')*+'         // Loop possessively.
                . '(?:-->)?';   // End of comment. If not found, match all input.

            $cdata =
                '!\[CDATA\['    // Start of comment, after the <.
                . '[^\]]*+'     // Consume non-].
                . '(?:'         // Unroll the loop: Consume everything until ]]> is found.
                .     '](?!]>)' // One ] not followed by end of comment.
                .     '[^\]]*+' // Consume non-].
                . ')*+'         // Loop possessively.
                . '(?:]]>)?';   // End of comment. If not found, match all input.

            $escaped =
                '(?='             // Is the element escaped?
                .    '!--'
                . '|'
                .    '!\[CDATA\['
                . ')'
                . '(?(?=!-)'      // If yes, which type?
                .     $comments
                . '|'
                .     $cdata
                . ')';

            $regex =
                '/('                // Capture the entire match.
                .     '<'           // Find start of element.
                .     '(?'          // Conditional expression follows.
                .         $escaped  // Find end of escaped element.
                .     '|'           // ...else...
                .         '[^>]*>?' // Find end of normal element.
                .     ')'
                . ')/';
            // phpcs:enable
        }

        return $regex;
    }

    /**
     * @var ShortCode
     */
    public static $intance = null;
    public static function checkInstance(){
        if(!static::$intance) static::$intance = new static();
    }
    public static function addShortcode($tag, $callback){
        static::checkInstance();
        return static::$intance->add($tag, $callback);
    }
    public static function hasShortcode($content, $tag){
        static::checkInstance();
        return static::$intance->has($content, $tag);
    }
    public static function do($content, $ignore_html){
        static::checkInstance();
        return static::$intance->doShortCode($content, $ignore_html);
    }
    public static function removeShortcode($tag = null){
        static::checkInstance();
        return static::$intance->remove($tag);
    }
    public static function getInstance(){
        static::checkInstance();
        return static::$intance;
    }
    
}


/**
 * Returns an array of HTML attribute names whose value contains a URL.
 *
 * This function returns a list of all HTML attributes that must contain
 * a URL according to the HTML specification.
 *
 * This list includes URI attributes both allowed and disallowed by KSES.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes
 *
 * @since 5.0.1
 *
 * @return string[] HTML attribute names whose value contains a URL.
 */
function wp_kses_uri_attributes() {
	$uri_attributes = array(
		'action',
		'archive',
		'background',
		'cite',
		'classid',
		'codebase',
		'data',
		'formaction',
		'href',
		'icon',
		'longdesc',
		'manifest',
		'poster',
		'profile',
		'src',
		'usemap',
		'xmlns',
	);

	/**
	 * Filters the list of attributes that are required to contain a URL.
	 *
	 * Use this filter to add any `data-` attributes that are required to be
	 * validated as a URL.
	 *
	 * @since 5.0.1
	 *
	 * @param string[] $uri_attributes HTML attribute names whose value contains a URL.
	 */
	// $uri_attributes = apply_filters( 'wp_kses_uri_attributes', $uri_attributes );

	return $uri_attributes;
}




/**
 * Finds all attributes of an HTML element.
 *
 * Does not modify input.  May return "evil" output.
 *
 * Based on `wp_kses_split2()` and `wp_kses_attr()`.
 *
 * @since 4.2.3
 *
 * @param string $element HTML element.
 * @return array|bool List of attributes found in the element. Returns false on failure.
 */
function wp_kses_attr_parse( $element ) {
	$valid = preg_match( '%^(<\s*)(/\s*)?([a-zA-Z0-9]+\s*)([^>]*)(>?)$%', $element, $matches );
	if ( 1 !== $valid ) {
		return false;
	}

	$begin  = $matches[1];
	$slash  = $matches[2];
	$elname = $matches[3];
	$attr   = $matches[4];
	$end    = $matches[5];

	if ( '' !== $slash ) {
		// Closing elements do not get parsed.
		return false;
	}

	// Is there a closing XHTML slash at the end of the attributes?
	if ( 1 === preg_match( '%\s*/\s*$%', $attr, $matches ) ) {
		$xhtml_slash = $matches[0];
		$attr        = substr( $attr, 0, -strlen( $xhtml_slash ) );
	} else {
		$xhtml_slash = '';
	}

	// Split it.
	$attrarr = wp_kses_hair_parse( $attr );
	if ( false === $attrarr ) {
		return false;
	}

	// Make sure all input is returned by adding front and back matter.
	array_unshift( $attrarr, $begin . $slash . $elname );
	array_push( $attrarr, $xhtml_slash . $end );

	return $attrarr;
}

/**
 * Builds an attribute list from string containing attributes.
 *
 * Does not modify input.  May return "evil" output.
 * In case of unexpected input, returns false instead of stripping things.
 *
 * Based on `wp_kses_hair()` but does not return a multi-dimensional array.
 *
 * @since 4.2.3
 *
 * @param string $attr Attribute list from HTML element to closing HTML element tag.
 * @return array|bool List of attributes found in $attr. Returns false on failure.
 */
function wp_kses_hair_parse( $attr ) {
	if ( '' === $attr ) {
		return array();
	}

	// phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound -- don't remove regex indentation
	$regex =
	'(?:'
	.     '[-a-zA-Z:]+'   // Attribute name.
	. '|'
	.     '\[\[?[^\[\]]+\]\]?' // Shortcode in the name position implies unfiltered_html.
	. ')'
	. '(?:'               // Attribute value.
	.     '\s*=\s*'       // All values begin with '='.
	.     '(?:'
	.         '"[^"]*"'   // Double-quoted.
	.     '|'
	.         "'[^']*'"   // Single-quoted.
	.     '|'
	.         '[^\s"\']+' // Non-quoted.
	.         '(?:\s|$)'  // Must have a space.
	.     ')'
	. '|'
	.     '(?:\s|$)'      // If attribute has no value, space is required.
	. ')'
	. '\s*';              // Trailing space is optional except as mentioned above.
	// phpcs:enable

	// Although it is possible to reduce this procedure to a single regexp,
	// we must run that regexp twice to get exactly the expected result.

	$validation = "%^($regex)+$%";
	$extraction = "%$regex%";

	if ( 1 === preg_match( $validation, $attr ) ) {
		preg_match_all( $extraction, $attr, $attrarr );
		return $attrarr[0];
	} else {
		return false;
	}
}


/**
 * Retrieve the shortcode attributes regex.
 *
 * @since 4.4.0
 *
 * @return string The shortcode attribute regular expression
 */
function get_shortcode_atts_regex() {
	return '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|\'([^\']*)\'(?:\s|$)|(\S+)(?:\s|$)/';
}

/**
 * Retrieve all attributes from the shortcodes tag.
 *
 * The attributes list has the attribute name as the key and the value of the
 * attribute as the value in the key/value pair. This allows for easier
 * retrieval of the attributes, since all attributes have to be known.
 *
 * @since 2.5.0
 *
 * @param string $text
 * @return array|string List of attribute values.
 *                      Returns empty array if trim( $text ) == '""'.
 *                      Returns empty string if trim( $text ) == ''.
 *                      All other matches are checked for not empty().
 */
function shortcode_parse_atts( $text ) {
	$atts    = array();
	$pattern = get_shortcode_atts_regex();
	$text    = preg_replace( "/[\x{00a0}\x{200b}]+/u", ' ', $text );
	if ( preg_match_all( $pattern, $text, $match, PREG_SET_ORDER ) ) {
		foreach ( $match as $m ) {
			if ( ! empty( $m[1] ) ) {
				$atts[ strtolower( $m[1] ) ] = stripcslashes( $m[2] );
			} elseif ( ! empty( $m[3] ) ) {
				$atts[ strtolower( $m[3] ) ] = stripcslashes( $m[4] );
			} elseif ( ! empty( $m[5] ) ) {
				$atts[ strtolower( $m[5] ) ] = stripcslashes( $m[6] );
			} elseif ( isset( $m[7] ) && strlen( $m[7] ) ) {
				$atts[] = stripcslashes( $m[7] );
			} elseif ( isset( $m[8] ) && strlen( $m[8] ) ) {
				$atts[] = stripcslashes( $m[8] );
			} elseif ( isset( $m[9] ) ) {
				$atts[] = stripcslashes( $m[9] );
			}
		}

		// Reject any unclosed HTML elements.
		foreach ( $atts as &$value ) {
			if ( false !== strpos( $value, '<' ) ) {
				if ( 1 !== preg_match( '/^[^<]*+(?:<[^>]*+>[^<]*+)*+$/', $value ) ) {
					$value = '';
				}
			}
		}
	} else {
		$atts = ltrim( $text );
	}

	return $atts;
}


/**
 * Regular Expression callable for do_shortcode() for calling shortcode hook.
 *
 * @see get_shortcode_regex for details of the match array contents.
 *
 * @since 2.5.0
 * @access private
 *
 * @global array $shortcode_tags
 *
 * @param array $m Regular expression match array
 * @return string|false False on failure.
 */
function do_shortcode_tag( $m ) {
    $shortcode = ShortCode::getInstance();

	// Allow [[foo]] syntax for escaping a tag.
	if ( '[' === $m[1] && ']' === $m[6] ) {
		return substr( $m[0], 1, -1 );
	}

	$tag  = $m[2];
	$attr = shortcode_parse_atts( $m[3] );

	if ( ! is_callable( $shortcode->tags[ $tag ] ) ) {
		/* translators: %s: Shortcode tag. */
		// $message = sprintf( __( 'Attempting to parse a shortcode without a valid callback: %s' ), $tag );
		return $m[0];
	}

	/**
	 * Filters whether to call a shortcode callback.
	 *
	 * Returning a non-false value from filter will short-circuit the
	 * shortcode generation process, returning that value instead.
	 *
	 * @since 4.7.0
	 *
	 * @param false|string $return      Short-circuit return value. Either false or the value to replace the shortcode with.
	 * @param string       $tag         Shortcode name.
	 * @param array|string $attr        Shortcode attributes array or empty string.
	 * @param array        $m           Regular expression match array.
	 */
	// $return = apply_filters( 'pre_do_shortcode_tag', false, $tag, $attr, $m );
	// if ( false !== $return ) {
	// 	return $return;
	// }

	$content = isset( $m[5] ) ? $m[5] : null;

	$output = $m[1] . call_user_func( $shortcode->tags[ $tag ], $attr, $content, $tag ) . $m[6];

    return $output;
	/**
	 * Filters the output created by a shortcode callback.
	 *
	 * @since 4.7.0
	 *
	 * @param string       $output Shortcode output.
	 * @param string       $tag    Shortcode name.
	 * @param array|string $attr   Shortcode attributes array or empty string.
	 * @param array        $m      Regular expression match array.
	 */
	// return apply_filters( 'do_shortcode_tag', $output, $tag, $attr, $m );
}

