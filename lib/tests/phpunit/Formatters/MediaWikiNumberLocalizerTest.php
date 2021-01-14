<?php

namespace Wikibase\Lib\Tests\Formatters;

use AssertionError;
use Language;
use MediaWiki\MediaWikiServices;
use MediaWikiCoversValidator;
use PHPUnit\Framework\TestCase;
use Wikibase\Lib\Formatters\MediaWikiNumberLocalizer;

/**
 * @covers \Wikibase\Lib\Formatters\MediaWikiNumberLocalizer
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MediaWikiNumberLocalizerTest extends TestCase {
	use MediaWikiCoversValidator;

	/**
	 * @dataProvider provideLocalizeNumberCases
	 * @param Language $language
	 * @param string|int|float $number
	 * @param string|string[] $expecteds
	 */
	public function testLocalizeNumber( Language $language, $number, $expecteds ) {
		$localizer = new MediaWikiNumberLocalizer( $language );
		$expecteds = (array)$expecteds;

		$actual = $localizer->localizeNumber( $number );

		$this->addToAssertionCount( 1 );
		foreach ( $expecteds as $expected ) {
			if ( $expected === $actual ) {
				return;
			}
		}
		$expected = "[ '" . implode( "', '", $expecteds ) . "' ]";
		throw new AssertionError(
			"Failed asserting that $expected contains '$actual'"
		);
	}

	public function provideLocalizeNumberCases() {
		$languageFactory = MediaWikiServices::getInstance()->getLanguageFactory();
		$languageEn = $languageFactory->getLanguage( 'en' );
		$languageAr = $languageFactory->getLanguage( 'ar' );

		yield [ $languageEn, '0', '0' ];
		yield [ $languageEn, '123456', '123,456' ];
		yield [ $languageEn, '123456.789', '123,456.789' ];
		yield [ $languageEn, '-123', '−123' ];
		yield [ $languageEn, 123, '123' ];
		yield [ $languageEn, 123.456, '123.456' ];

		yield [ $languageAr, '0', '٠' ];
		yield [ $languageAr, '123456', '١٢٣٬٤٥٦' ];
		yield [ $languageAr, '123456.789', '١٢٣٬٤٥٦٫٧٨٩' ];
		yield [ $languageAr, '-123', '−١٢٣' ];
		yield [ $languageAr, 123, '١٢٣' ];
		yield [ $languageAr, 123.456, '١٢٣٫٤٥٦' ];

		$nines = '9999999999999999999';
		$ninesEn = '9,999,999,999,999,999,999';
		$ninesAr = '٩٬٩٩٩٬٩٩٩٬٩٩٩٬٩٩٩٬٩٩٩٬٩٩٩';
		yield [ $languageEn, $nines, [ $nines, $ninesEn ] ];
		yield [ $languageAr, $nines, [ $nines, $ninesAr ] ];

		$pi = '3.1415926535897932';
		$piEn = $pi;
		$piAr = '٣٫١٤١٥٩٢٦٥٣٥٨٩٧٩٣٢';
		yield [ $languageEn, $pi, [ $pi, $piEn ] ];
		yield [ $languageAr, $pi, [ $pi, $piAr ] ];
	}

}
