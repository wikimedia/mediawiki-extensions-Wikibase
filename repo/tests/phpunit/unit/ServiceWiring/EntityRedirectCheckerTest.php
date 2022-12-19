<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\Lib\EntitySourceAndTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityRedirectChecker;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityRedirectCheckerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$sourceAndTypeDefinitions = $this->createMock( EntitySourceAndTypeDefinitions::class );
		$sourceAndTypeDefinitions->expects( $this->once() )
			->method( 'getServiceBySourceAndType' )
			->with( EntityTypeDefinitions::REDIRECT_CHECKER_CALLBACK )
			->willReturn( [
				'some-source' => [ 'some-entity-type' => function () {
					return $this->createStub( EntityRedirectChecker::class );
				} ],
			] );
		$this->mockService( 'WikibaseRepo.EntitySourceAndTypeDefinitions', $sourceAndTypeDefinitions );

		$this->mockService(
			'WikibaseRepo.EntitySourceLookup',
			$this->createMock( EntitySourceLookup::class )
		);

		$this->assertInstanceOf(
			EntityRedirectChecker::class,
			$this->getService( 'WikibaseRepo.EntityRedirectChecker' )
		);
	}

}
