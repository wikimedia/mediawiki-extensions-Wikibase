<?php

namespace Wikibase\Lib\Tests\Store;

use MediaWikiTestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\TypeDispatchingEntityRevisionLookup;

/**
 * @covers \Wikibase\Lib\Store\TypeDispatchingEntityRevisionLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class TypeDispatchingEntityRevisionLookupTest extends MediaWikiTestCase {

	public function testGivenUnknownEntityType_getEntityRevisionForwardsToDefaultService() {
		$id = new PropertyId( 'P1' );
		$lookup = new TypeDispatchingEntityRevisionLookup(
			[],
			$this->newDefaultService( 'getEntityRevision', $id )
		);

		$result = $lookup->getEntityRevision( $id );
		$this->assertSame( 'fromDefaultService', $result );
	}

	public function testGivenCustomEntityType_getEntityRevisionInstantiatesCustomService() {
		$id = new PropertyId( 'P1' );
		$lookup = new TypeDispatchingEntityRevisionLookup(
			[
				'property' => function ( EntityRevisionLookup $defaultService ) use ( $id ) {
					$customService = $this->getMock( EntityRevisionLookup::class );
					$customService->expects( $this->once() )
						->method( 'getEntityRevision' )
						->with( $id )
						->willReturn( 'fromCustomService' );
					return $customService;
				},
			],
			$this->newDefaultService( 'getEntityRevision' )
		);

		$result = $lookup->getEntityRevision( $id );
		$this->assertSame( 'fromCustomService', $result );
	}

	public function testGivenUnknownEntityType_getLatestRevisionIdForwardsToDefaultService() {
		$id = new PropertyId( 'P1' );
		$lookup = new TypeDispatchingEntityRevisionLookup(
			[],
			$this->newDefaultService( 'getLatestRevisionId', $id )
		);

		$result = $lookup->getLatestRevisionId( $id );
		$this->assertSame( 'fromDefaultService', $result );
	}

	public function testGivenCustomEntityType_getLatestRevisionIdInstantiatesCustomService() {
		$id = new PropertyId( 'P1' );
		$lookup = new TypeDispatchingEntityRevisionLookup(
			[
				'property' => function ( EntityRevisionLookup $defaultService ) use ( $id ) {
					$customService = $this->getMock( EntityRevisionLookup::class );
					$customService->expects( $this->once() )
						->method( 'getLatestRevisionId' )
						->with( $id )
						->willReturn( 'fromCustomService' );
					return $customService;
				},
			],
			$this->newDefaultService( 'getLatestRevisionId' )
		);

		$result = $lookup->getLatestRevisionId( $id );
		$this->assertSame( 'fromCustomService', $result );
	}

	/**
	 * @param string $expectedMethod
	 * @param EntityId|null $expectedId
	 *
	 * @return EntityRevisionLookup
	 */
	public function newDefaultService( $expectedMethod, EntityId $expectedId = null ) {
		$defaultService = $this->getMock( EntityRevisionLookup::class );

		if ( $expectedId ) {
			$defaultService->expects( $this->once() )
				->method( $expectedMethod )
				->with( $expectedId )
				->willReturn( 'fromDefaultService' );
		} else {
			$defaultService->expects( $this->never() )
				->method( $expectedMethod );
		}

		return $defaultService;
	}

}
