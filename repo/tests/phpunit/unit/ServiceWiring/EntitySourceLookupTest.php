<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.EntitySourceDefinitions', $this->createStub( EntitySourceDefinitions::class ) );
		$this->mockService( 'WikibaseRepo.SubEntityTypesMapper', $this->createStub( SubEntityTypesMapper::class ) );

		$this->assertInstanceOf(
			EntitySourceLookup::class,
			$this->getService( 'WikibaseRepo.EntitySourceLookup' )
		);
	}

}
