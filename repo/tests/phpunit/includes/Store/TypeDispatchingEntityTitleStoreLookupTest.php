<?php

namespace Wikibase\Repo\Tests\Store;

use MediaWikiTestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\TypeDispatchingEntityTitleStoreLookup;

/**
 * @covers \Wikibase\Repo\Store\TypeDispatchingEntityTitleStoreLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class TypeDispatchingEntityTitleStoreLookupTest extends MediaWikiTestCase {

	public function testGivenUnknownEntityType_getTitleForIdForwardsToDefaultService() {
		$id = new PropertyId( 'P1' );
		$lookup = $this->newInstance( [], $id );

		$result = $lookup->getTitleForId( $id );
		$this->assertSame( 'fromParentService', $result );
	}

	public function testGivenCustomEntityType_getTitleForIdInstantiatesCustomService() {
		$id = new PropertyId( 'P1' );
		$lookup = $this->newInstance( [
			'property' => function ( EntityTitleStoreLookup $defaultService ) use ( $id ) {
				$customService = $this->getMock( EntityTitleStoreLookup::class );
				$customService->expects( $this->once() )
					->method( 'getTitleForId' )
					->with( $id )
					->willReturn( 'fromCustomService' );
				return $customService;
			},
		] );

		$result = $lookup->getTitleForId( $id );
		$this->assertSame( 'fromCustomService', $result );
	}

	/**
	 * @param callable[] $callbacks
	 * @param EntityId|null $expectedId
	 *
	 * @return TypeDispatchingEntityTitleStoreLookup
	 */
	public function newInstance( array $callbacks, EntityId $expectedId = null ) {
		$defaultService = $this->getMock( EntityTitleStoreLookup::class );
		$defaultService->expects( $expectedId ? $this->once() : $this->never() )
			->method( 'getTitleForId' )
			->with( $expectedId )
			->willReturn( 'fromParentService' );

		return new TypeDispatchingEntityTitleStoreLookup( $callbacks, $defaultService );
	}

}
