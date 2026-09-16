<?php
/**
 * Tests for the placeholder repair applied to this plugin's translations.
 *
 * @package Complianz_Terms_Conditions
 */

/**
 * Covers cmplz_tc_repair_placeholders() and the gettext filters that apply it.
 *
 * The malformed strings below are the real cs_CZ translations that crashed the
 * plugin on PHP 8 (Asana 1214573807894849), where `%N$s` lost its `s`.
 */
class FormatGuardTest extends WP_UnitTestCase {

	/**
	 * Injects a replacement translation for one original string.
	 *
	 * Runs at priority 5 so the plugin's repair filter, registered at 10, sees it.
	 *
	 * @param  string $needle      Fragment identifying the original string.
	 * @param  string $translation Translation to serve instead.
	 * @param  string $filter      One of the four gettext filters the plugin hooks.
	 * @return callable            The registered callback, for remove_filter().
	 */
	private function fake_translation( $needle, $translation, $filter = 'gettext' ) {
		$args     = array(
			'gettext'               => 3,
			'gettext_with_context'  => 4,
			'ngettext'              => 5,
			'ngettext_with_context' => 6,
		)[ $filter ];
		$callback = static function ( $current, $text ) use ( $needle, $translation ) {
			return false !== strpos( $text, $needle ) ? $translation : $current;
		};

		add_filter( $filter, $callback, 5, $args );

		return $callback;
	}

	/**
	 * Every damaged cs_CZ placeholder is made valid again.
	 *
	 * @return void
	 */
	public function test_reported_czech_strings_are_repaired() {
		$cases = array(
			// config/steps.php:72 — the crash in the report.
			' Upozorňujeme, v naší %1$sdocumentaci%2$s. Zašlete nám prosím %3$sžádost o podporu%4$.'
				=> ' Upozorňujeme, v naší %1$sdocumentaci%2$s. Zašlete nám prosím %3$sžádost o podporu%4$s.',
			// config/documents/terms-conditions.php:612.
			'stránky %1$scontact%2$.'              => 'stránky %1$scontact%2$s.',
			// config/documents/terms-conditions.php:561 — every placeholder damaged.
			'podmínky %1$prohlášením%2$ a %3$zásadami%4$ mezi vámi a %5$ ohledně.'
				=> 'podmínky %1$sprohlášením%2$s a %3$szásadami%4$s mezi vámi a %5$s ohledně.',
			// config/documents/terms-conditions.php:437.
			'v našich %3$sZásadách%4$.'            => 'v našich %3$sZásadách%4$s.',
			// class-document.php:1861.
			'nebo nejprve %1$vytvořte nabídku%2$.' => 'nebo nejprve %1$svytvořte nabídku%2$s.',
		);

		foreach ( $cases as $damaged => $expected ) {
			$this->assertSame( $expected, cmplz_tc_repair_placeholders( $damaged ) );
		}
	}

	/**
	 * Valid patterns and plain text are returned untouched.
	 *
	 * @return void
	 */
	public function test_healthy_strings_are_untouched() {
		$healthy = array(
			'use our %1$sdocs%2$s or log a %3$sticket%4$s.',
			'at least %s years of age.',
			'paragraph %d of %d',
			'padded %1$04d and %2$-10s',
			'100%% guaranteed',
			'a sentence with no placeholders at all',
			'a price of $10 and 50% off',
		);

		foreach ( $healthy as $string ) {
			$this->assertSame( $string, cmplz_tc_repair_placeholders( $string ) );
		}
	}

	/**
	 * Repaired placeholders make the damaged translation formattable again.
	 *
	 * @return void
	 */
	public function test_repaired_string_formats_without_error() {
		$damaged  = 'Zašlete nám prosím %1$sžádost%2$.';
		$repaired = cmplz_tc_repair_placeholders( $damaged );

		$this->assertSame( 'Zašlete nám prosím <a>žádost</a>.', sprintf( $repaired, '<a>', '</a>' ) );
	}

	/**
	 * End-to-end through __(): the reported fatal no longer happens.
	 *
	 * Without the filter this call throws ValueError on PHP 8, which is what took
	 * Czech sites down on every request.
	 *
	 * @return void
	 */
	public function test_damaged_translation_does_not_fatal_via_gettext() {
		$callback = $this->fake_translation(
			'please read this',
			'Další informace najdete v tomto %1$článku%2$.'
		);

		$result = cmplz_tc_read_more( 'https://complianz.io/docs' );
		remove_filter( 'gettext', $callback, 5 );

		$this->assertStringContainsString( 'href="https://complianz.io/docs"', $result );
		$this->assertStringContainsString( '>článku</a>', $result );
		$this->assertStringNotContainsString( '$', $result );
	}

	/**
	 * End-to-end through _x(): the document strings are repaired too.
	 *
	 * @return void
	 */
	public function test_damaged_translation_does_not_fatal_via_gettext_with_context() {
		$callback = $this->fake_translation(
			'constitute the entire agreement',
			'Tyto podmínky spolu s %1$prohlášením%2$ a %3$zásadami%4$ mezi vámi a %5$ ohledně.',
			'gettext_with_context'
		);

		$result = sprintf(
			// translators: 1-4 are anchor tags, 5 is the organisation name.
			_x( 'These Terms and Conditions, together with our %1$sprivacy statement%2$s and %3$scookie policy%4$s, constitute the entire agreement between you and %5$s in relation to your use of this website.', 'Legal document', 'complianz-terms-conditions' ),
			'<a>',
			'</a>',
			'<a>',
			'</a>',
			'Acme BV'
		);
		remove_filter( 'gettext_with_context', $callback, 5 );

		$this->assertStringContainsString( '<a>prohlášením</a>', $result );
		$this->assertStringContainsString( '<a>zásadami</a>', $result );
		$this->assertStringContainsString( 'a Acme BV ohledně.', $result );
	}

	/**
	 * End-to-end through _n(): plural forms are repaired too.
	 *
	 * @return void
	 */
	public function test_damaged_plural_translation_does_not_fatal() {
		$callback = $this->fake_translation( 'day of', '%1$ dnů z %2$.', 'ngettext' );

		$result = sprintf(
			// translators: 1 is the elapsed number of days, 2 is the total.
			_n( '%1$s day of %2$s', '%1$s days of %2$s', 3, 'complianz-terms-conditions' ),
			3,
			14
		);
		remove_filter( 'ngettext', $callback, 5 );

		$this->assertSame( '3 dnů z 14.', $result );
	}

	/**
	 * Translations belonging to other plugins are left alone.
	 *
	 * The domain is the last argument of all four filters, so the callback reads it
	 * from the end regardless of how many arguments the hook passes.
	 *
	 * @return void
	 */
	public function test_other_text_domains_are_not_touched() {
		$damaged  = 'someone else %1$poškozeno%2$.';
		$repaired = 'someone else %1$spoškozeno%2$s.';

		$this->assertSame( $damaged, cmplz_tc_repair_translation( $damaged, 'original', 'other-plugin' ) );
		$this->assertSame( $damaged, cmplz_tc_repair_translation( $damaged, 'one', 'many', 2, 'Context', 'other-plugin' ) );
		$this->assertSame( $repaired, cmplz_tc_repair_translation( $damaged, 'original', 'complianz-terms-conditions' ) );
		$this->assertSame( $repaired, cmplz_tc_repair_translation( $damaged, 'one', 'many', 2, 'complianz-terms-conditions' ) );
	}

	/**
	 * The filters are registered, so every translated string passes through them.
	 *
	 * @return void
	 */
	public function test_filters_are_registered() {
		foreach ( array( 'gettext', 'gettext_with_context', 'ngettext', 'ngettext_with_context' ) as $filter ) {
			$this->assertSame( 10, has_filter( $filter, 'cmplz_tc_repair_translation' ), $filter );
		}
	}
}
