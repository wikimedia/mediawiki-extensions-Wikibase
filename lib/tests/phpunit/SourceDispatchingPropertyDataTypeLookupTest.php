<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataAccess\Tests\NewDatabaseEntitySource;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\ServiceBySourceAndTypeDispatcher;
use Wikibase\Lib\SourceDispatchingPropertyDataTypeLookup;

/**
 * @covers \Wikibase\Lib\SourceDispatchingPropertyDataTypeLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SourceDispatchingPropertyDataTypeLookupTest extends TestCase {

/**
 * @var MockObject|EntitySourceLookup
 */
	private $entitySourceLookup;

	/**
	 * @var MockObject|ServiceBySourceAndTypeDispatcher
	 */
	private $lookupCallbacks;

	protected function setUp(): void {
		parent::setUp();

		$this->entitySourceLookup = $this->createStub( EntitySourceLookup::class );
		$this->lookupCallbacks = [];
	}

	public function testGivenEntityDataTypeLookupDefinedForEntitySource_usesRespectiveEntityDataTypeLookup(): void {
		$propertyId = new NumericPropertyId( 'P321' );
		$dataTypeId = 'wikibase-schmentity';
		$propertySourceName = 'schmentitySource';

		$this->entitySourceLookup = $this->createMock( EntitySourceLookup::class );

		$propertyDataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$propertyDataTypeLookup->expects( $this->once() )
			->method( 'getDataTypeIdForProperty' )
			->with( $propertyId )
			->willReturn( $dataTypeId );

		$this->lookupCallbacks = [
			$propertySourceName => function () use ( $propertyDataTypeLookup ) {
				return $propertyDataTypeLookup;
			},
		];

		$this->entitySourceLookup->expects( $this->atLeastOnce() )
			->method( 'getEntitySourceById' )
			->with( $propertyId )
			->willReturn( NewDatabaseEntitySource::havingName( $propertySourceName )->build() );

		$this->assertSame( $dataTypeId, $this->newDispatchingPropertyDataTypeLookup()->getDataTypeIdForProperty( $propertyId ) );
	}

	private function newDispatchingPropertyDataTypeLookup(): SourceDispatchingPropertyDataTypeLookup {
		return new SourceDispatchingPropertyDataTypeLookup(
			$this->entitySourceLookup,
			$this->lookupCallbacks
		);
	}

}
