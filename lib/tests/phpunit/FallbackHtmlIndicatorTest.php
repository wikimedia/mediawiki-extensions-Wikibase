<?php

namespace Wikibase\Lib\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\FallbackHtmlIndicator;
use Wikibase\Lib\LanguageNameLookup;

/**
 * @covers Wikibase\Lib\FallbackHtmlIndicator
 *
 * @group ValueFormatters
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class FallbackHtmlIndicatorTest extends PHPUnit_Framework_TestCase {

	private function getIndicator() {
		$languageNameLookup = $this->getMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->any() )
			->method( 'getName' )
			->will( $this->returnCallback( function( $languageCode ) {
				$names = array(
						'de' => 'Deutsch',
						'de-at' => 'Österreichisches Deutsch',
						'de-ch' => 'Schweizer Hochdeutsch',
						'en' => 'english in german',
						'en-ca' => 'Canadian English'
				);
				return $names[ $languageCode ];
			} ) );

		$fallbackHtmlIndicator = new FallbackHtmlIndicator(
			$languageNameLookup
		);

		return $fallbackHtmlIndicator;
	}

	public function formatProvider_fallback() {
		$deTermFallback = new TermFallback( 'de', 'Kätzchen', 'de', 'de' );
		$deAtTerm = new TermFallback( 'de-at', 'Kätzchen', 'de', 'de' );
		$atDeTerm = new TermFallback( 'de', 'Kätzchen', 'de-at', 'de-at' );
		$deChTerm = new TermFallback( 'de-ch', 'Frass', 'de-ch', 'de' );
		$enGbEnCaTerm = new TermFallback( 'en-gb', 'Kitten', 'en', 'en-ca' );
		$deEnTerm = new TermFallback( 'de', 'Kitten', 'en', 'en' );

		$translitDeCh = wfMessage( 'wikibase-language-fallback-transliteration-hint', 'Deutsch', 'Schweizer Hochdeutsch' )->text();
		$translitEnCa = wfMessage( 'wikibase-language-fallback-transliteration-hint', 'Canadian English', 'English' )->text();

		return array(
			'plain fallabck term' => array(
				'expected' => '',
				'term' => $deTermFallback,
			),
			'fallback to base' => array(
				'expected' => '<sup class="wb-language-fallback-'
					. 'indicator wb-language-fallback-variant">Deutsch</sup>',
				'term' => $deAtTerm,
			),
			'fallback to variant' => array(
				'expected' => '<sup class="wb-language-fallback-'
					. 'indicator wb-language-fallback-variant">Österreichisches Deutsch</sup>',
				'term' => $atDeTerm,
			),
			'transliteration to requested language' => array(
				'expected' => '<sup class="wb-language-fallback-'
					. 'indicator wb-language-fallback-transliteration">'
					. $translitDeCh
					. '</sup>',
				'term' => $deChTerm,
			),
			'transliteration to other variant' => array(
				'expected' => '<sup class="wb-language-fallback-'
					. 'indicator wb-language-fallback-transliteration wb-language-fallback-'
					. 'variant">'
					. $translitEnCa
					. '</sup>',
				'term' => $enGbEnCaTerm,
			),
			'fallback to alternative language' => array(
				'expected' => '<sup class="wb-language-fallback-'
					. 'indicator">english in german</sup>',
				'term' => $deEnTerm,
			),
		);
	}

	/**
	 * @dataProvider formatProvider_fallback
	 */
	public function testFormat_fallback( $expected, TermFallback $term ) {
		$fallbackHtmlIndicator = $this->getIndicator();

		$result = $fallbackHtmlIndicator->getHtml( $term );

		$this->assertSame( $expected, $result );
	}

}
