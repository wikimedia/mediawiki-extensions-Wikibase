<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TypeIdsAcquirerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$databaseTypeIdsStore = $this->createMock( DatabaseTypeIdsStore::class );
		$this->mockService( 'WikibaseRepo.DatabaseTypeIdsStore',
			$databaseTypeIdsStore );

		$typeIdsAcquirer = $this->getService( 'WikibaseRepo.TypeIdsAcquirer' );

		$this->assertSame( $databaseTypeIdsStore, $typeIdsAcquirer );
	}

}
