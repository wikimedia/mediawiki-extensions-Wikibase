<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Entity\Property;
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
class PropertyTermsCollisionDetectorTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$propertyTermsCollisionDetector = $this->createMock( TermsCollisionDetector::class );
		$termsCollisionDetectorFactory = $this->createMock( TermsCollisionDetectorFactory::class );
		$termsCollisionDetectorFactory->expects( $this->once() )
			->method( 'getTermsCollisionDetector' )
			->with( Property::ENTITY_TYPE )
			->willReturn( $propertyTermsCollisionDetector );
		$this->mockService( 'WikibaseRepo.TermsCollisionDetectorFactory',
			$termsCollisionDetectorFactory );

		$this->assertSame( $propertyTermsCollisionDetector,
			$this->getService( 'WikibaseRepo.PropertyTermsCollisionDetector' ) );
	}

}
