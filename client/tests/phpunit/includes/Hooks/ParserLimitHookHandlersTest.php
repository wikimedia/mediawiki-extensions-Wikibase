<?php

namespace Wikibase\Client\Tests\Hooks;

use Language;
use ParserOutput;
use Wikibase\Client\Hooks\ParserLimitHookHandlers;

/**
 * @covers Wikibase\Client\Hooks\ParserLimitHookHandlers
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group WikibaseHooks
 *
 * @license GNU GPL v2+
 * @author Marius Hoch
 */
class ParserLimitHookHandlersTest extends \PHPUnit_Framework_TestCase {

	public function testDoParserLimitReportPrepare() {
		$restrictedEntityLookup = $this->getMockBuilder( 'Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup' )
			->disableOriginalConstructor()
			->getMock();

		$restrictedEntityLookup->expects( $this->once() )
			->method( 'getEntityAccessCount' )
			->will( $this->returnValue( 42 ) );

		$handler = new ParserLimitHookHandlers(
			$restrictedEntityLookup,
			Language::factory( 'en' )
		);

		$parserOutput = new ParserOutput();

		$handler->doParserLimitReportPrepare(
			$this->getMock( 'Parser' ),
			$parserOutput
		);

		$limitReportData = $parserOutput->getLimitReportData();

		$this->assertSame( 42, $limitReportData['EntityAccessCount'] );
	}

	/**
	 * @dataProvider doParserLimitReportFormatProvider
	 */
	public function testDoParserLimitReportFormat( $expected, Language $language, $isHTML, $localize ) {
		$restrictedEntityLookup = $this->getMockBuilder( 'Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup' )
			->disableOriginalConstructor()
			->getMock();

		$handler = new ParserLimitHookHandlers(
			$restrictedEntityLookup,
			$language
		);

		$value = 123;
		$result = '';

		$handler->doParserLimitReportFormat(
			'EntityAccessCount',
			$value,
			$result,
			$isHTML,
			$localize
		);

		$this->assertSame( $expected, $result );
	}

	public function doParserLimitReportFormatProvider() {
		$languageRu = Language::factory( 'ru' );
		$languageEn = Language::factory( 'en' );
		$labelRu = wfMessage( 'wikibase-limitreport-entities-accessed' )->inLanguage( $languageRu )->text();
		$labelEn = wfMessage( 'wikibase-limitreport-entities-accessed' )->inLanguage( $languageEn )->text();
		$colonSeparatorRu = wfMessage( 'colon-separator' )->inLanguage( $languageRu )->text();
		$colonSeparatorEn = wfMessage( 'colon-separator' )->inLanguage( $languageEn )->text();

		return array(
			'Russian, html' => array(
				'<tr><th>' . $labelRu . '</th><td>123</td></tr>',
				$languageRu,
				true,
				true
			),
			'Non-localized (English), html' => array(
				'<tr><th>' . $labelEn . '</th><td>123</td></tr>',
				$languageRu,
				true,
				false
			),
			'Russian, non-html' => array(
				$labelRu . $colonSeparatorRu . 123,
				$languageRu,
				false,
				true
			),
			'Non-localized (English), non-html' => array(
				$labelRu . $colonSeparatorEn . 123,
				$languageRu,
				false,
				false
			)
		);
	}

}
