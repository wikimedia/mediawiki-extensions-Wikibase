<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\Hooks;

use Parser;
use ParserOutput;
use Wikibase\Client\Hooks\ParserHookHandler;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;

/**
 * @covers \Wikibase\Client\Hooks\ParserHookHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group WikibaseHooks
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class ParserHookHandlerTest extends \PHPUnit\Framework\TestCase {

	public function testOnParserLimitReportPrepare() {
		$restrictedEntityLookup = $this->createMock( RestrictedEntityLookup::class );

		$restrictedEntityLookup->expects( $this->once() )
			->method( 'getEntityAccessCount' )
			->willReturn( 42 );

		$handler = new ParserHookHandler(
			$restrictedEntityLookup,
			1234
		);

		$parserOutput = new ParserOutput();

		$handler->onParserLimitReportPrepare( $this->createMock( Parser::class ), $parserOutput );

		$limitReportData = $parserOutput->getLimitReportData();

		$this->assertSame(
			[ 42, 1234 ],
			$limitReportData['limitreport-entityaccesscount']
		);
	}

}
