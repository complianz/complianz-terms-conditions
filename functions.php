<?php
/**
 * Global helper functions for the Complianz Terms & Conditions plugin.
 *
 * Provides utility functions used across the plugin for template rendering,
 * option retrieval, UI notifications, HTML sanitisation, language handling,
 * document condition callbacks, and capability checks. All functions are
 * wrapped in function_exists() guards so themes or other plugins can override
 * them when needed.
 *
 * @package    Complianz_Terms_Conditions
 * @author     Complianz
 * @copyright  2023 Complianz.io
 * @license    GPL-2.0-or-later
 * @link       https://complianz.io
 *
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || die( 'you do not have acces to this page!' );

if ( ! function_exists( 'cmplz_tc_repair_placeholders' ) ) {
	/**
	 * Restores printf placeholders that a translation dropped the conversion character from.
	 *
	 * Community translations can damage positional placeholders (ex.: the cs_CZ
	 * strings turned `%4$s` into `%4$` and `%1$sprivacy` into `%1$privacy`). To avoid
	 * a fatal PHP 8 ValueError from taking down every request on affected sites, rewriting
	 * `%N$` to `%N$s` keeps the sentence and its links intact.
	 *
	 * @since  1.4.1
	 *
	 * @param  string $translation Translated string, possibly with damaged placeholders.
	 * @return string              The string with every positional placeholder valid again.
	 */
	function cmplz_tc_repair_placeholders( $translation ) {
		// Every damaged placeholder contains `$`, so strings without one need no work.
		if ( false === strpos( $translation, '$' ) ) {
			return $translation;
		}

		// Matches `%N$` only when what follows cannot complete a conversion specification.
		// A translated word starting with a conversion character (`%1$binary`) still reads
		// as valid and is left alone: it prints oddly, but sprintf() no longer fatals.
		return preg_replace( "/%(\d+)\\\$(?![-+0']*(?:'.)?-?\d*(?:\.\d+)?[bcdeEfFgGosuxX])/", '%${1}$s', $translation );
	}
}

if ( ! function_exists( 'cmplz_tc_repair_translation' ) ) {
	/**
	 * Repairs placeholders in this plugin's translations.
	 *
	 * Filters `gettext`, `gettext_with_context`, `ngettext` and `ngettext_with_context`,
	 * which all pass the text domain as their last argument.
	 *
	 * @since  1.4.1
	 *
	 * @param  string $translation Translated text.
	 * @param  mixed  ...$args     Remaining hook arguments; the last one is the text domain.
	 * @return string              Translated text with valid placeholders.
	 */
	function cmplz_tc_repair_translation( $translation, ...$args ) {
		return 'complianz-terms-conditions' === end( $args ) ? cmplz_tc_repair_placeholders( $translation ) : $translation;
	}
}

if ( ! function_exists( 'cmplz_tc_get_template' ) ) {
	/**
	 * Loads and returns a plugin template file, with optional theme override support.
	 *
	 * Resolves the template path via the `cmplz_tc_template_file` filter, then checks
	 * whether the active theme provides an override in its own directory under the
	 * plugin's slug folder. PHP templates are executed with output buffering so any
	 * PHP logic inside them is evaluated; non-PHP files (e.g. HTML) are read directly.
	 * Named placeholders in the form `{key}` are replaced with values from `$args`.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $filename Template filename relative to the plugin's `templates/`
	 *                          directory (e.g. 'wizard/menu.php').
	 * @param  array  $args     Associative array of placeholder replacements. Each key
	 *                          maps to a `{key}` token in the template.
	 *                          Example: array( 'title' => 'My Title', 'content' => '<p>...</p>' ).
	 * @return string|false     Rendered template string, or false if the plugin-side
	 *                          template file does not exist.
	 */
	function cmplz_tc_get_template( $filename, $args = array() ) {

		/**
		 * Filters the resolved path to a plugin template file.
		 *
		 * @since 1.0.0
		 *
		 * @param string $file     Absolute path to the plugin template file.
		 * @param string $filename Template filename relative to the templates/ directory.
		 */
		$file = apply_filters( 'cmplz_tc_template_file', trailingslashit( cmplz_tc_path ) . 'templates/' . $filename, $filename );

		// Build the path where the active theme could provide a template override.
		$theme_file = trailingslashit( get_stylesheet_directory() )
						. trailingslashit( basename( cmplz_tc_path ) )
						. 'templates/' . $filename;

		// Return false early when the plugin-bundled template is missing entirely.
		if ( ! file_exists( $file ) ) {
			return false;
		}

		// Use the theme override when it exists.
		if ( file_exists( $theme_file ) ) {
			$file = $theme_file;
		}

		if ( strpos( $file, '.php' ) !== false ) {
			// Execute PHP templates with output buffering to capture dynamic output.
			ob_start();
			require $file;
			$contents = ob_get_clean();
		} else {
			// Read static (non-PHP) template files directly.
			$contents = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a local file path, not a remote URL.
		}

		// Replace {key} placeholder tokens with the provided argument values.
		if ( ! empty( $args ) ) {
			foreach ( $args as $fieldname => $value ) {
				// Only scalars are token-substitutable; skip arrays (e.g. field errors/values).
				if ( is_scalar( $value ) ) {
					$contents = str_replace( '{' . $fieldname . '}', (string) $value, $contents );
				}
			}
		}

		return $contents;
	}
}

if ( ! function_exists( 'cmplz_tc_settings_page' ) ) {
	/**
	 * Returns the URL of the plugin's admin settings page.
	 *
	 * When the full Complianz suite is active (detected via the `cmplz_version`
	 * constant), the page is hosted under `admin.php`; on standalone installs it
	 * falls back to `tools.php`.
	 *
	 * @since  1.0.0
	 *
	 * @return string Absolute URL to the Terms & Conditions settings page.
	 */
	function cmplz_tc_settings_page() {
		if ( ! defined( 'cmplz_version' ) ) {
			// Standalone install: page lives under Tools.
			return add_query_arg( array( 'page' => 'terms-conditions' ), admin_url( 'tools.php' ) );
		} else {
			// Full Complianz suite: page is registered under the main admin menu.
			return add_query_arg( array( 'page' => 'terms-conditions' ), admin_url( 'admin.php' ) );
		}
	}
}

if ( ! function_exists( 'cmplz_tc_get_value' ) ) {

	/**
	 * Retrieves a stored plugin option value for a given field name.
	 *
	 * Looks up the field definition in the config to determine which option group
	 * (source) the value belongs to, then reads it from the database. Falls back
	 * to the field's configured default when the stored value is absent and
	 * `$use_default` is true. When the field is marked as translatable, the value
	 * is passed through Polylang, WPML, or any registered `wpml_translate_single_string`
	 * filter before being returned.
	 *
	 * For very early usage (before the plugin classes are available), supply `$page`
	 * directly to bypass the config class lookup.
	 *
	 * @since  1.0.0
	 *
	 * @param  string      $fieldname     The option field name (e.g. 'company_name').
	 * @param  bool|string $page          The option group/source identifier (e.g. 'settings'),
	 *                                    or false to resolve it automatically from the field config.
	 * @param  bool        $use_default   Whether to fall back to the field's configured default
	 *                                    value when no stored value exists. Default true.
	 * @param  bool        $use_translate Whether to pass the value through multilingual
	 *                                    translation plugins (Polylang, WPML). Default true.
	 * @return array|bool|mixed|string    The stored (and optionally translated) value, the field
	 *                                    default, an empty string, or false when the field is
	 *                                    unknown and no page is supplied.
	 */
	function cmplz_tc_get_value( $fieldname, $page = false, $use_default = true, $use_translate = true ) {

		// Return early when no page is given and the field is not registered in config.
		if ( ! $page && ! isset( COMPLIANZ_TC::$config->fields[ $fieldname ] ) ) {
			return false;
		}

		// Resolve the option source/group from the field definition when not supplied.
		if ( ! $page ) {
			$page = COMPLIANZ_TC::$config->fields[ $fieldname ]['source'];
		}

		$fields  = get_option( 'complianz_tc_options_' . $page );
		$default = ( $use_default && $page && isset( COMPLIANZ_TC::$config->fields[ $fieldname ]['default'] ) ) ? COMPLIANZ_TC::$config->fields[ $fieldname ]['default'] : '';
		$value   = isset( $fields[ $fieldname ] ) ? $fields[ $fieldname ] : $default;

		// Pass translatable values through active multilingual plugins.
		if ( $use_translate ) {
			if ( isset( COMPLIANZ_TC::$config->fields[ $fieldname ]['translatable'] )
				&& COMPLIANZ_TC::$config->fields[ $fieldname ]['translatable']
			) {
				if ( function_exists( 'pll__' ) ) {
					$value = pll__( $value );
				}
				if ( function_exists( 'icl_translate' ) ) {
					$value = icl_translate( 'complianz', $fieldname, $value );
				}
				/**
				 * Filters a translatable option value via WPML string translation.
				 *
				 * @since 1.0.0
				 *
				 * @param mixed  $value     The current field value.
				 * @param string $context   The WPML context ('complianz').
				 * @param string $fieldname The field name being translated.
				 */
				$value = apply_filters( 'wpml_translate_single_string', $value, 'complianz', $fieldname );
			}
		}

		// Per-field value filter (e.g. never-empty resolution for withdrawal_notification_email).
		return apply_filters( "cmplz_tc_fieldvalue_{$fieldname}", $value, $fieldname );
	}
}

if ( ! function_exists( 'cmplz_tc_default_withdrawal_notification_email' ) ) {
	/**
	 * Resolves the recipient for Complianz withdrawal-form requests, never empty.
	 *
	 * Returns the given value when it is a non-empty string; otherwise prefers the
	 * general contact email captured in the wizard (email_company) and falls back
	 * to the site administrator address. Doubles as the config default pre-fill
	 * (called with no argument) and the read-time value filter callback for
	 * withdrawal_notification_email, so an empty saved value still resolves.
	 *
	 * @since 1.4.0
	 *
	 * @param  string $value Current value to keep when non-empty. Default ''.
	 * @return string A recipient email address.
	 */
	function cmplz_tc_default_withdrawal_notification_email( $value = '' ) {
		if ( '' !== (string) $value ) {
			return $value;
		}
		$company = (string) cmplz_tc_get_value( 'email_company', 'terms-conditions' );
		return '' !== $company ? $company : (string) get_option( 'admin_email' );
	}
}

if ( ! function_exists( 'cmplz_tc_intro' ) ) {

	/**
	 * Renders an introductory message panel in the plugin admin UI.
	 *
	 * Outputs a styled `<div>` panel containing the provided message. Used to
	 * display contextual introductions at the top of wizard steps or settings
	 * sections. Silently returns when `$msg` is empty.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $msg The message to display. HTML is allowed. Returns without
	 *                     output when this is an empty string.
	 * @return void
	 */
	function cmplz_tc_intro( $msg ) {
		if ( '' === $msg ) {
			return;
		}
		// Output the intro panel directly; $msg is expected to be pre-sanitised by the caller.
		$html = "<div class='cmplz-panel cmplz-notification cmplz-intro'>{$msg}</div>";

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is pre-sanitised upstream.
	}
}

if ( ! function_exists( 'cmplz_tc_notice' ) ) {
	/**
	 * Renders or returns a notification panel for the plugin admin UI.
	 *
	 * Generates a styled notification `<div>` that can be echoed immediately or
	 * returned as a string for inclusion in a larger template. Supports conditional
	 * display: when `$condition` is provided, the panel is shown or hidden based on
	 * whether the specified wizard field currently matches the expected answer.
	 * The `$remove_after_change` flag adds a CSS class that hides the notice as soon
	 * as any wizard field value changes (handled by frontend JS).
	 *
	 * @since  1.0.0
	 *
	 * @param  string     $msg                 The notification message. HTML is allowed.
	 *                                          Returns without output when empty.
	 * @param  string     $type                Visual style of the notice. One of:
	 *                                          'notice' (default), 'warning', 'success'.
	 * @param  bool       $remove_after_change  When true, adds the 'cmplz-remove-after-change'
	 *                                          CSS class so JS hides the notice on field change.
	 * @param  bool       $do_echo              When true (default) the HTML is echoed; when false
	 *                                          it is returned as a string.
	 * @param  array|bool $condition            Optional condition array with keys:
	 *                                          - 'question' (string) Field name to check.
	 *                                          - 'answer'   (string) Expected field value.
	 *                                          The notice is hidden via CSS when the condition
	 *                                          does not currently apply. Default false.
	 * @return string|void                      The HTML string when $do_echo is false, otherwise void.
	 */
	function cmplz_tc_notice( $msg, $type = 'notice', $remove_after_change = false, $do_echo = true, $condition = false ) {
		if ( '' === $msg ) {
			return;
		}

		// Build conditional visibility attributes when a condition is supplied.
		$condition_check    = '';
		$condition_question = '';
		$condition_answer   = '';
		$cmplz_hidden       = '';
		if ( $condition ) {
			$condition_check    = 'condition-check';
			$condition_question = "data-condition-question='{$condition['question']}'";
			$condition_answer   = "data-condition-answer='{$condition['answer']}'";
			$args['condition']  = array( $condition['question'] => $condition['answer'] );
			// Check whether the condition currently applies to determine initial visibility.
			$cmplz_hidden = cmplz_field::this()->condition_applies( $args ) ? '' : 'cmplz-hidden';

		}

		// Add the removal class when the notice should disappear on any field change.
		$remove_after_change_class = $remove_after_change ? 'cmplz-remove-after-change' : '';

		$html = "<div class='cmplz-panel-wrap'><div class='cmplz-panel cmplz-notification cmplz-{$type} {$remove_after_change_class} {$cmplz_hidden} {$condition_check}' {$condition_question} {$condition_answer}><div>{$msg}</div></div></div>";

		if ( $do_echo ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is pre-sanitised upstream.
		} else {
			return $html;
		}
	}
}

if ( ! function_exists( 'cmplz_tc_sidebar_notice' ) ) {
	/**
	 * Renders or returns a sidebar-style notification for the plugin admin UI.
	 *
	 * Similar to cmplz_tc_notice() but uses a different wrapper structure and CSS
	 * class intended for placement in the wizard or settings sidebar (help modals,
	 * contextual tips). Supports the same condition and remove-after-change behaviour.
	 *
	 * @since  1.0.0
	 *
	 * @see    cmplz_tc_notice() For the main-content equivalent.
	 *
	 * @param  string     $msg                 The notification message. HTML is allowed.
	 *                                          Returns without output when empty.
	 * @param  string     $type                Visual style. One of: 'notice' (default),
	 *                                          'warning', 'success'.
	 * @param  bool       $remove_after_change  When true, adds 'cmplz-remove-after-change'
	 *                                          so the notice is hidden by JS on field change.
	 * @param  bool       $do_echo              When true (default) the HTML is echoed; when false
	 *                                          it is returned as a string.
	 * @param  bool|array $condition            Optional condition array with 'question' and
	 *                                          'answer' keys (see cmplz_tc_notice()). Default false.
	 * @return string|void                      The HTML string when $do_echo is false, otherwise void.
	 */
	function cmplz_tc_sidebar_notice( $msg, $type = 'notice', $remove_after_change = false, $do_echo = true, $condition = false ) {
		if ( '' === $msg ) {
			return;
		}

		// Build conditional visibility attributes when a condition is supplied.
		$condition_check    = '';
		$condition_question = '';
		$condition_answer   = '';
		$cmplz_hidden       = '';
		if ( $condition ) {
			$condition_check    = 'condition-check';
			$condition_question = "data-condition-question='{$condition['question']}'";
			$condition_answer   = "data-condition-answer='{$condition['answer']}'";
			$args['condition']  = array( $condition['question'] => $condition['answer'] );
			// Check whether the condition currently applies to determine initial visibility.
			$cmplz_hidden = cmplz_field::this()->condition_applies( $args ) ? '' : 'cmplz-hidden';

		}

		// Add the removal class when the notice should disappear on any field change.
		$remove_after_change_class = $remove_after_change ? 'cmplz-remove-after-change' : '';

		$html = "<div class='cmplz-help-modal cmplz-notice cmplz-{$type} {$remove_after_change_class} {$cmplz_hidden} {$condition_check}' {$condition_question} {$condition_answer}>{$msg}</div>";

		if ( $do_echo ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is pre-sanitised upstream.
		} else {
			return $html;
		}
	}
}

if ( ! function_exists( 'cmplz_tc_localize_date' ) ) {

	/**
	 * Translates the month name and weekday name within a date string to the current locale.
	 *
	 * Extracts the English month name (e.g. "June") and weekday name (e.g. "Wednesday")
	 * from the supplied date string using PHP's date() function, then substitutes each
	 * with its localised equivalent via WordPress's __() translation function.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $date A date string parseable by strtotime() (e.g. '2024-06-12').
	 * @return string The date string with the month and weekday names translated to the
	 *                current WordPress locale.
	 */
	function cmplz_tc_localize_date( $date ) {
		// Extract the English month name and replace it with the localised equivalent.
		$month           = gmdate( 'F', strtotime( $date ) ); // e.g. "June".
		$month_localized = __( $month ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Variable contains an English month name produced by gmdate(); runtime translation is intentional.
		$date            = str_replace( $month, $month_localized, $date );

		// Extract the English weekday name and replace it with the localised equivalent.
		$weekday           = gmdate( 'l', strtotime( $date ) ); // e.g. "Wednesday".
		$weekday_localized = __( $weekday ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Variable contains an English weekday name produced by gmdate(); runtime translation is intentional.
		$date              = str_replace( $weekday, $weekday_localized, $date );

		return $date;
	}
}

if ( ! function_exists( 'cmplz_tc_read_more' ) ) {
	/**
	 * Builds a localised "Read more" sentence with a hyperlink to an external article.
	 *
	 * Returns a formatted string like " For more information on this subject, please
	 * read this [article]." with `$url` as the link target. Optionally prepends a
	 * non-breaking space to visually separate it from preceding text.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $url       The URL of the article or documentation page to link to.
	 * @param  bool   $add_space Whether to prepend a non-breaking space (`&nbsp;`) before
	 *                           the sentence. Default true.
	 * @return string            The complete "read more" sentence HTML.
	 */
	function cmplz_tc_read_more( $url, $add_space = true ) {
		$html
			= sprintf(
				// translators: %1$s is the opening anchor tag, %2$s is the closing anchor tag.
				__(
					'For more information on this subject, please read this %1$sarticle%2$s.',
					'complianz-terms-conditions'
				),
				'<a target="_blank" href="' . $url . '">',
				'</a>'
			);
		if ( $add_space ) {
			$html = '&nbsp;' . $html;
		}

		return $html;
	}
}


if ( ! function_exists( 'cmplz_tc_get_regions' ) ) {
	/**
	 * Returns the list of geographical regions supported by the plugin.
	 *
	 * Currently the plugin generates a single Terms & Conditions document covering
	 * all regions. This function provides the region list used by the document and
	 * wizard configuration systems; future versions may expand it with region-specific
	 * entries.
	 *
	 * @since  1.0.0
	 *
	 * @return array Associative array of region slugs to display labels.
	 *               Example: array( 'all' => 'All regions' )
	 */
	function cmplz_tc_get_regions() {
		$output['all'] = __( 'All regions', 'complianz-terms-conditions' );

		return $output;
	}
}

// Register the activation hook before defining the callback so WordPress can bind it.
register_activation_hook( __FILE__, 'cmplz_tc_set_activation_time_stamp' );

if ( ! function_exists( 'cmplz_tc_set_activation_time_stamp' ) ) {
	/**
	 * Stores the plugin activation timestamp as a WordPress option.
	 *
	 * Fired on plugin activation via register_activation_hook(). The timestamp is
	 * used by other parts of the plugin to determine how long the plugin has been
	 * installed (e.g. for delaying review prompts or deferred notices).
	 *
	 * @since  1.0.0
	 *
	 * @param  bool $networkwide True when activated network-wide on a multisite install,
	 *                           false for a single-site activation.
	 * @return void
	 */
	function cmplz_tc_set_activation_time_stamp( $networkwide ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by register_activation_hook() signature; multisite support may use it in future.
		update_option( 'cmplz_tc_activation_time', time() );
	}
}

if ( ! function_exists( 'cmplz_tc_allowed_html' ) ) {
	/**
	 * Returns the list of HTML tags and attributes permitted for wp_kses() sanitisation.
	 *
	 * Defines an extended allowed-tag map used when sanitising plugin-generated HTML
	 * that may contain rich markup (e.g. wizard notices, document content). The list
	 * can be extended by third parties via the `cmplz_tc_allowed_html` filter.
	 *
	 * @since  1.0.0
	 *
	 * @return array Associative array of allowed HTML tags mapped to their permitted
	 *               attributes. Format matches the $allowed_html parameter of wp_kses().
	 */
	function cmplz_tc_allowed_html() {

		// Define the full set of tags and attributes the plugin may output.
		$allowed_tags = array(
			'a'          => array(
				'class'  => array(),
				'href'   => array(),
				'rel'    => array(),
				'title'  => array(),
				'target' => array(),
				'id'     => array(),
			),
			'button'     => array(
				'id'     => array(),
				'class'  => array(),
				'href'   => array(),
				'rel'    => array(),
				'title'  => array(),
				'target' => array(),
			),
			'b'          => array(),
			'br'         => array(),
			'blockquote' => array(
				'cite' => array(),
			),
			'div'        => array(
				'class' => array(),
				'id'    => array(),
			),
			'h1'         => array(),
			'h2'         => array(),
			'h3'         => array(),
			'h4'         => array(),
			'h5'         => array(),
			'h6'         => array(),
			'i'          => array(),
			'input'      => array(
				'type'          => array(),
				'class'         => array(),
				'id'            => array(),
				'required'      => array(),
				'value'         => array(),
				'placeholder'   => array(),
				'data-category' => array(),
				'style'         => array(
					'color' => array(),
				),
			),
			'img'        => array(
				'alt'    => array(),
				'class'  => array(),
				'height' => array(),
				'src'    => array(),
				'width'  => array(),
			),
			'label'      => array(
				'for'   => array(),
				'class' => array(),
				'style' => array(
					'visibility' => array(),
				),
			),
			'li'         => array(
				'class' => array(),
				'id'    => array(),
			),
			'ol'         => array(
				'class' => array(),
				'id'    => array(),
			),
			'p'          => array(
				'class' => array(),
				'id'    => array(),
			),
			'span'       => array(
				'class' => array(),
				'title' => array(),
				'style' => array(
					'color'   => array(),
					'display' => array(),
				),
				'id'    => array(),
			),
			'strong'     => array(),
			'table'      => array(
				'class' => array(),
				'id'    => array(),
			),
			'tr'         => array(),
			// SVG elements are included to support inline icon output.
			'svg'        => array(
				'width'   => array(),
				'height'  => array(),
				'viewBox' => array(),
			),
			'polyline'   => array(
				'points' => array(),

			),
			'path'       => array(
				'd' => array(),

			),
			'style'      => array(),
			'td'         => array(
				'colspan' => array(),
				'scope'   => array(),
			),
			'th'         => array( 'scope' => array() ),
			'ul'         => array(
				'class' => array(),
				'id'    => array(),
			),
		);

		/**
		 * Filters the allowed HTML tags and attributes for plugin output sanitisation.
		 *
		 * @since 1.0.0
		 *
		 * @param array $allowed_tags Associative array of tags to permitted attributes.
		 */
		return apply_filters( 'cmplz_tc_allowed_html', $allowed_tags );
	}
}

if ( ! function_exists( 'cmplz_tc_translate' ) ) {
	/**
	 * Translates a field value through active multilingual plugins.
	 *
	 * Passes the supplied value through Polylang (pll__()), WPML (icl_translate()),
	 * and the `wpml_translate_single_string` filter in sequence, returning the
	 * translated result. Only active translation plugins affect the value; inactive
	 * ones are silently skipped.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $value     The original field value to translate.
	 * @param  string $fieldname The field name used as the WPML string context identifier.
	 * @return string            The translated value, or the original when no translation is found.
	 */
	function cmplz_tc_translate( $value, $fieldname ) {
		if ( function_exists( 'pll__' ) ) {
			$value = pll__( $value );
		}

		if ( function_exists( 'icl_translate' ) ) {
			$value = icl_translate( 'complianz', $fieldname, $value );
		}

		/**
		 * Filters the translated value via WPML string translation.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed  $value     The current field value.
		 * @param string $context   The WPML context ('complianz').
		 * @param string $fieldname The field name being translated.
		 */
		$value = apply_filters( 'wpml_translate_single_string', $value, 'complianz', $fieldname );

		return $value;
	}
}

if ( ! function_exists( 'cmplz_tc_sanitize_language' ) ) {

	/**
	 * Sanitises a language string to a two-letter ISO 639-1 code.
	 *
	 * Truncates the input to its first two characters, validates that they form a
	 * string of exactly two ASCII letters, and returns the code in lowercase. This
	 * prevents invalid or overly long locale strings (e.g. 'en_US') from being
	 * stored where a simple language code is expected.
	 *
	 * @since  1.0.0
	 *
	 * @param  mixed $language The raw language value to sanitise (e.g. 'en', 'EN', 'en_US').
	 * @return bool|string      Lowercase two-letter language code on success (e.g. 'en'),
	 *                          or false when the input is not a valid string or does not
	 *                          contain two ASCII letters.
	 */
	function cmplz_tc_sanitize_language( $language ) {
		// Pattern matches exactly two ASCII letters (a-z or A-Z).
		$pattern = '/^[a-zA-Z]{2}$/';
		if ( ! is_string( $language ) ) {
			return false;
		}
		// Truncate to two characters to handle locale strings like 'en_US'.
		$language = substr( $language, 0, 2 );

		if ( (bool) preg_match( $pattern, $language ) ) {
			$language = strtolower( $language );

			return $language;
		}

		return false;
	}
}

if ( ! function_exists( 'cmplz_tcf_creative_commons' ) ) {

	/**
	 * Callback: checks whether the Creative Commons document section should be shown.
	 *
	 * Used as a `callback_condition` in the document element configuration to
	 * conditionally include the Creative Commons licence paragraph. Returns false
	 * (hide the section) when the copyright setting is 'allrights' or 'norights',
	 * because those values indicate a standard copyright notice rather than a CC licence.
	 *
	 * @since  1.0.0
	 *
	 * @return bool True when a Creative Commons licence type is selected, false otherwise.
	 */
	function cmplz_tcf_creative_commons() {
		$type = cmplz_tc_get_value( 'about_copyright' );
		if ( 'allrights' === $type || 'norights' === $type ) {
			return false;
		} else {
			return true;
		}
	}
}

if ( ! function_exists( 'cmplz_tcf_nuts' ) ) {

	/**
	 * Callback: checks whether the NUTS (Network Utility Transaction Services) section applies.
	 *
	 * Used as a `callback_condition` for document sections that are specific to
	 * utility or service subscriptions sold via NUTS. Returns true when the return
	 * policy covers NUTS services or utilities, so that the relevant withdrawal
	 * provisions are included in the generated document.
	 *
	 * @since  1.0.0
	 *
	 * @return bool True when the return type is 'nuts_services' or 'nuts_utilities',
	 *              false otherwise.
	 */
	function cmplz_tcf_nuts() {
		$services  = cmplz_tc_get_value( 'about_returns' ) === 'nuts_services';
		$utilities = cmplz_tc_get_value( 'about_returns' ) === 'nuts_utilities';
		if ( $services || $utilities ) {
			return true;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'cmplz_tc_uses_gutenberg' ) ) {
	/**
	 * Checks whether the current WordPress install is using the Gutenberg block editor.
	 *
	 * Returns true when the has_block() function exists (WordPress 5.0+) and the
	 * Classic Editor plugin is not active. This is used to decide whether to register
	 * a Gutenberg block or fall back to a classic shortcode widget.
	 *
	 * @since  1.0.0
	 *
	 * @return bool True when Gutenberg is available and Classic Editor is not active,
	 *              false otherwise.
	 */
	function cmplz_tc_uses_gutenberg() {

		if ( function_exists( 'has_block' )
			&& ! class_exists( 'Classic_Editor' )
		) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'cmplz_tc_user_can_manage' ) ) {
	/**
	 * Checks whether the current user has permission to manage plugin settings.
	 *
	 * Used as a capability gate throughout the plugin's admin UI. Requires both an
	 * active login session and the `manage_options` capability (typically limited to
	 * administrators). Returns false immediately when either condition is not met.
	 *
	 * @since  1.0.0
	 *
	 * @return bool True if the current user is logged in and has `manage_options`,
	 *              false otherwise.
	 */
	function cmplz_tc_user_can_manage() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		return true;
	}
}


if ( ! function_exists( 'cmplz_tc_array_filter_multidimensional' ) ) {
	/**
	 * Filters a multidimensional array, keeping only elements where a specific key matches a value.
	 *
	 * Iterates over a flat array of associative sub-arrays and returns only those whose
	 * `$filter_key` element equals `$filter_value`. Elements that do not have the
	 * specified key are excluded. This is used by the wizard to extract fields that
	 * match a particular attribute, such as all required fields (`'required' => true`).
	 *
	 * @since  1.0.0
	 *
	 * @param  array  $items        The input array of associative sub-arrays to filter.
	 * @param  string $filter_key   The key to check within each sub-array.
	 * @param  mixed  $filter_value The value that $filter_key must equal for an element
	 *                              to be included in the result.
	 * @return array                Filtered array preserving original keys, containing only
	 *                              elements where $items[n][$filter_key] === $filter_value.
	 */
	function cmplz_tc_array_filter_multidimensional(
		$items,
		$filter_key,
		$filter_value
	) {
		$new = array_filter(
			$items,
			function ( $element ) use ( $filter_value, $filter_key ) {
				return isset( $element[ $filter_key ] ) ? ( $element[ $filter_key ] === $filter_value ) : false;
			}
		);

		return $new;
	}
}
