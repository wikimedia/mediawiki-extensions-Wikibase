<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemTermsCollisionDetectorTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$itemTermsCollisionDetector = $this->createMock( TermsCollisionDetector::class );
		$termsCollisionDetectorFactory = $this->createMock( TermsCollisionDetectorFactory::class );
		$termsCollisionDetectorFactory->expects( $this->once() )
			->method( 'getTermsCollisionDetector' )
			->with( Item::ENTITY_TYPE )
			->willReturn( $itemTermsCollisionDetector );
		$this->mockService( 'WikibaseRepo.TermsCollisionDetectorFactory',
			$termsCollisionDetectorFactory );

		$this->assertSame( $itemTermsCollisionDetector,
			$this->getService( 'WikibaseRepo.ItemTermsCollisionDetector' ) );
	}

}
