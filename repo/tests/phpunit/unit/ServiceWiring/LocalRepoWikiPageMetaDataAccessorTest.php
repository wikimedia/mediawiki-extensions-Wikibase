<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Psr\Log\NullLogger;
use Wikibase\DataAccess\EntitySource;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LocalRepoWikiPageMetaDataAccessorTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.EntityNamespaceLookup',
			new EntityNamespaceLookup( [] ) );
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [] ) );
		$this->mockService( 'WikibaseRepo.LocalEntitySource',
			$this->createMock( EntitySource::class ) );
		$this->mockService( 'WikibaseRepo.Logger',
			new NullLogger() );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSlotRoleStore' );

		$this->assertInstanceOf(
			WikiPageEntityMetaDataAccessor::class,
			$this->getService( 'WikibaseRepo.LocalRepoWikiPageMetaDataAccessor' )
		);
	}

}
