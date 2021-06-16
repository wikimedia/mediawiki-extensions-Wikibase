<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsLookup;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermsCollisionDetectorFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$dbFactory = $this->createMock( RepoDomainDbFactory::class );
		$dbFactory->expects( $this->once() )
			->method( 'newRepoDb' );
		$this->mockService( 'WikibaseRepo.RepoDomainDbFactory',
			$dbFactory );
		$this->mockService( 'WikibaseRepo.TypeIdsLookup',
			$this->createMock( TypeIdsLookup::class ) );

		$termsCollisionDetectorFactory = $this->getService( 'WikibaseRepo.TermsCollisionDetectorFactory' );

		$this->assertInstanceOf( TermsCollisionDetectorFactory::class, $termsCollisionDetectorFactory );
	}

}
