<?php

namespace Wikibase\Client\Tests\Hooks;

use Parser;
use ParserOutput;
use PHPUnit_Framework_TestCase;
use Wikibase\Client\Hooks\ParserLimitReportPrepareHookHandler;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;

/**
 * @covers Wikibase\Client\Hooks\ParserLimitReportPrepareHookHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group WikibaseHooks
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class ParserLimitReportPrepareHookHandlerTest extends PHPUnit_Framework_TestCase {

	public function testNewFromGlobalState() {
		$instance = ParserLimitReportPrepareHookHandler::newFromGlobalState();

		$this->assertInstanceOf( ParserLimitReportPrepareHookHandler::class, $instance );
	}

	public function testDoParserLimitReportPrepare() {
		$restrictedEntityLookup = $this->getMockBuilder( RestrictedEntityLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$restrictedEntityLookup->expects( $this->once() )
			->method( 'getEntityAccessCount' )
			->will( $this->returnValue( 42 ) );

		$handler = new ParserLimitReportPrepareHookHandler(
			$restrictedEntityLookup,
			1234
		);

		$parserOutput = new ParserOutput();

		$handler->doParserLimitReportPrepare( $this->getMock( Parser::class ), $parserOutput );

		$limitReportData = $parserOutput->getLimitReportData();

		$this->assertSame(
			[ 42, 1234 ],
			$limitReportData['limitreport-entityaccesscount']
		);
	}

}
