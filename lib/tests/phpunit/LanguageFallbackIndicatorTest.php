<?php

namespace Wikibase\Lib\Tests;

use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\LanguageNameLookup;

/**
 * @covers \Wikibase\Lib\LanguageFallbackIndicator
 *
 * @group ValueFormatters
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class LanguageFallbackIndicatorTest extends \PHPUnit\Framework\TestCase {

	private function getIndicator() {
		$languageNameLookup = $this->createMock( LanguageNameLookup::class );
		$languageNameLookup->method( 'getName' )
			->willReturnCallback( function( $languageCode ) {
				$names = [
						'de' => 'Deutsch',
						'de-at' => 'Österreichisches Deutsch',
						'de-ch' => 'Schweizer Hochdeutsch',
						'en' => 'english in german',
						'en-ca' => 'Canadian English',
						'mul' => 'multilingual',
				];
				return $names[ $languageCode ];
			} );

		$languageFallbackIndicator = new LanguageFallbackIndicator(
			$languageNameLookup
		);

		return $languageFallbackIndicator;
	}

	public function formatProvider_fallback() {
		$deTermFallback = new TermFallback( 'de', 'Kätzchen', 'de', 'de' );
		$deAtTerm = new TermFallback( 'de-at', 'Kätzchen', 'de', 'de' );
		$atDeTerm = new TermFallback( 'de', 'Kätzchen', 'de-at', 'de-at' );
		$deChTerm = new TermFallback( 'de-ch', 'Frass', 'de-ch', 'de' );
		$enGbEnCaTerm = new TermFallback( 'en-gb', 'Kitten', 'en', 'en-ca' );
		$deEnTerm = new TermFallback( 'de', 'Kitten', 'en', 'en' );
		$deMulTerm = new TermFallback( 'de', 'Felis catus', 'mul', 'mul' );

		$translitDeCh = wfMessage( 'wikibase-language-fallback-transliteration-hint', 'Deutsch', 'Schweizer Hochdeutsch' )->text();
		$translitEnCa = wfMessage( 'wikibase-language-fallback-transliteration-hint', 'Canadian English', 'English' )->text();

		return [
			'plain fallabck term' => [
				'expected' => '',
				'term' => $deTermFallback,
			],
			'fallback to base' => [
				'expected' => '<sup class="wb-language-fallback-indicator'
					. ' wb-language-fallback-variant">' . "\u{00A0}Deutsch</sup>",
				'term' => $deAtTerm,
			],
			'fallback to variant' => [
				'expected' => '<sup class="wb-language-fallback-indicator'
					. ' wb-language-fallback-variant">' . "\u{00A0}Österreichisches Deutsch</sup>",
				'term' => $atDeTerm,
			],
			'transliteration to requested language' => [
				'expected' => '<sup class="wb-language-fallback-indicator'
					. ' wb-language-fallback-transliteration">'
					. "\u{00A0}" . $translitDeCh
					. '</sup>',
				'term' => $deChTerm,
			],
			'transliteration to other variant' => [
				'expected' => '<sup class="wb-language-fallback-indicator'
					. ' wb-language-fallback-transliteration wb-language-fallback-variant">'
					. "\u{00A0}" . $translitEnCa
					. '</sup>',
				'term' => $enGbEnCaTerm,
			],
			'fallback to alternative language' => [
				'expected' => '<sup class="wb-language-fallback-indicator'
					. '">' . "\u{00A0}english in german</sup>",
				'term' => $deEnTerm,
			],
			'fallback to multilingual' => [
				'expected' => '<sup class="wb-language-fallback-indicator'
					. ' wb-language-fallback-mul">' . "\u{00A0}multilingual</sup>",
				'term' => $deMulTerm,
			],
		];
	}

	/**
	 * @dataProvider formatProvider_fallback
	 */
	public function testFormat_fallback( $expected, TermFallback $term ) {
		$languageFallbackIndicator = $this->getIndicator();

		$result = $languageFallbackIndicator->getHtml( $term );

		$this->assertSame( $expected, $result );
	}

}
