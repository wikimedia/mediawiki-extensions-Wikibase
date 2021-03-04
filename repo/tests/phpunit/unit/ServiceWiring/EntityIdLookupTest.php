<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityIdLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.EntityContentFactory',
			$this->createMock( EntityContentFactory::class )
		);

		$this->assertInstanceOf(
			EntityIdLookup::class,
			$this->getService( 'WikibaseRepo.EntityIdLookup' )
		);
	}

}
