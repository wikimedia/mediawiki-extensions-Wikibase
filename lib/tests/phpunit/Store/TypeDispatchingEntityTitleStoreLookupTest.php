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
		$lookup = $this->newInstance( [], 'getEntityRevision', $id );

		$result = $lookup->getEntityRevision( $id );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenCustomEntityType_getEntityRevisionInstantiatesCustomService() {
		$id = new PropertyId( 'P1' );
		$lookup = $this->newInstance(
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
			'getEntityRevision'
		);

		$result = $lookup->getEntityRevision( $id );
		$this->assertSame( 'fromCustomService', $result );
	}

	public function testGivenUnknownEntityType_getLatestRevisionIdForwardsToDefaultService() {
		$id = new PropertyId( 'P1' );
		$lookup = $this->newInstance( [], 'getLatestRevisionId', $id );

		$result = $lookup->getLatestRevisionId( $id );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenCustomEntityType_getLatestRevisionIdInstantiatesCustomService() {
		$id = new PropertyId( 'P1' );
		$lookup = $this->newInstance(
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
			'getLatestRevisionId'
		);

		$result = $lookup->getLatestRevisionId( $id );
		$this->assertSame( 'fromCustomService', $result );
	}

	/**
	 * @param callable[] $callbacks
	 * @param string $expectedMethod
	 * @param EntityId|null $expectedId
	 *
	 * @return TypeDispatchingEntityRevisionLookup
	 */
	public function newInstance( array $callbacks, $expectedMethod, EntityId $expectedId = null ) {
		$defaultService = $this->getMock( EntityRevisionLookup::class );
		$defaultService->expects( $expectedId ? $this->once() : $this->never() )
			->method( $expectedMethod )
			->with( $expectedId )
			->willReturn( 'fromParentService' );

		return new TypeDispatchingEntityRevisionLookup( $callbacks, $defaultService );
	}

}
