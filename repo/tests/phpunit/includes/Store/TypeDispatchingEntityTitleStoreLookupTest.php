<?php

namespace Wikibase\Repo\Tests\Store;

use InvalidArgumentException;
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

	/**
	 * @covers \Wikibase\Repo\Store\TypeDispatchingEntityTitleStoreLookup::getLookup
	 */
	public function testGivenInvalidCallback_getTitleForIdFails() {
		$lookup = new TypeDispatchingEntityTitleStoreLookup(
			[
				'property' => function ( EntityTitleStoreLookup $defaultService ) {
					return new \stdClass();
				},
			],
			$this->newDefaultService()
		);

		$this->setExpectedException( InvalidArgumentException::class );
		$lookup->getTitleForId( new PropertyId( 'P1' ) );
	}

	public function testGivenUnknownEntityType_getTitleForIdForwardsToDefaultService() {
		$id = new PropertyId( 'P1' );
		$lookup = new TypeDispatchingEntityTitleStoreLookup(
			[],
			$this->newDefaultService( $id )
		);

		$result = $lookup->getTitleForId( $id );
		$this->assertSame( 'fromDefaultService', $result );
	}

	public function testGivenCustomEntityType_getTitleForIdInstantiatesCustomService() {
		$id = new PropertyId( 'P1' );
		$lookup = new TypeDispatchingEntityTitleStoreLookup(
			[
				'property' => function ( EntityTitleStoreLookup $defaultService ) use ( $id ) {
					$customService = $this->getMock( EntityTitleStoreLookup::class );
					$customService->expects( $this->once() )
						->method( 'getTitleForId' )
						->with( $id )
						->willReturn( 'fromCustomService' );
					return $customService;
				},
			],
			$this->newDefaultService()
		);

		$result = $lookup->getTitleForId( $id );
		$this->assertSame( 'fromCustomService', $result );
	}

	/**
	 * @param EntityId|null $expectedId
	 *
	 * @return EntityTitleStoreLookup
	 */
	public function newDefaultService( EntityId $expectedId = null ) {
		$defaultService = $this->getMock( EntityTitleStoreLookup::class );

		if ( $expectedId ) {
			$defaultService->expects( $this->once() )
				->method( 'getTitleForId' )
				->with( $expectedId )
				->willReturn( 'fromDefaultService' );
		} else {
			$defaultService->expects( $this->never() )
				->method( 'getTitleForId' );
		}

		return $defaultService;
	}

}
