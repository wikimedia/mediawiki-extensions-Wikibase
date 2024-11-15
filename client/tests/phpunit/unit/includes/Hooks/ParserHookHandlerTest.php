<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\Hooks;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use Wikibase\Client\Hooks\ParserHookHandler;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookupFactory;

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

		$restrictedEntityLookupFactory = $this->createMock( RestrictedEntityLookupFactory::class );
		$parser = $this->createMock( Parser::class );

		$restrictedEntityLookupFactory->expects( $this->once() )
			->method( 'getRestrictedEntityLookup' )
			->with( $parser )
			->willReturn( $restrictedEntityLookup );

		$handler = new ParserHookHandler(
			$restrictedEntityLookupFactory,
			1234
		);

		$parserOutput = new ParserOutput();

		$handler->onParserLimitReportPrepare( $parser, $parserOutput );

		$limitReportData = $parserOutput->getLimitReportData();

		$this->assertSame(
			[ 42, 1234 ],
			$limitReportData['limitreport-entityaccesscount']
		);
	}

}
