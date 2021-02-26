<?php

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementGuidParserTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService( 'WikibaseRepo.EntityIdParser',
			new ItemIdParser() );

		/** @var StatementGuidParser $statementGuidParser */
		$statementGuidParser = $this->getService( 'WikibaseRepo.StatementGuidParser' );
		$this->assertInstanceOf( StatementGuidParser::class, $statementGuidParser );

		$statementGuid = $statementGuidParser->parse( 'Q123$456' );
		$this->assertInstanceOf( StatementGuid::class, $statementGuid );
		$this->assertSame( 'Q123', $statementGuid->getEntityId()->getSerialization() );
		$this->assertSame( '456', $statementGuid->getGuidPart() );
	}

}
