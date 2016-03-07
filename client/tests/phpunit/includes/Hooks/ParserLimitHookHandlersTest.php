<?php

namespace Wikibase\Client\Tests\Hooks;

use Language;
use Parser;
use ParserOutput;
use PHPUnit_Framework_TestCase;
use Wikibase\Client\Hooks\ParserLimitHookHandlers;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;

/**
 * @covers Wikibase\Client\Hooks\ParserLimitHookHandlers
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group WikibaseHooks
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class ParserLimitHookHandlersTest extends PHPUnit_Framework_TestCase {

	public function testDoParserLimitReportPrepare() {
		$restrictedEntityLookup = $this->getMockBuilder( RestrictedEntityLookup::class )
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

		$handler->doParserLimitReportPrepare( $this->getMock( Parser::class ), $parserOutput );

		$limitReportData = $parserOutput->getLimitReportData();

		$this->assertSame( 42, $limitReportData['EntityAccessCount'] );
	}

	/**
	 * @dataProvider doParserLimitReportFormatProvider
	 */
	public function testDoParserLimitReportFormat( $expected, $isHTML, $localize ) {
		$restrictedEntityLookup = $this->getMockBuilder( RestrictedEntityLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$handler = new ParserLimitHookHandlers(
			$restrictedEntityLookup,
			Language::factory( 'qqx' )
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
		$labelEn = wfMessage( 'wikibase-limitreport-entities-accessed' )->inLanguage( 'en' )->text();
		$colonSeparatorEn = wfMessage( 'colon-separator' )->inLanguage( 'en' )->text();

		return array(
			'Localized (qqx), HTML' => array(
				'<tr><th>(wikibase-limitreport-entities-accessed)</th><td>123</td></tr>',
				true,
				true
			),
			'Non-localized (English), HTML' => array(
				'<tr><th>' . $labelEn . '</th><td>123</td></tr>',
				true,
				false
			),
			'Localized (qqx), non-HTML' => array(
				'(wikibase-limitreport-entities-accessed)(colon-separator)123',
				false,
				true
			),
			'Non-localized (English), non-HTML' => array(
				$labelEn . $colonSeparatorEn . 123,
				false,
				false
			)
		);
	}

}
