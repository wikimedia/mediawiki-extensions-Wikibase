<?php

namespace Wikibase\Repo\Tests\Store;

use MediaWikiCoversValidator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\TypeDispatchingEntityTitleStoreLookup;
use Wikimedia\Assert\PostconditionException;

/**
 * @covers \Wikibase\Repo\Store\TypeDispatchingEntityTitleStoreLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class TypeDispatchingEntityTitleStoreLookupTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiCoversValidator;

	public function testGivenInvalidCallback_getTitleForIdFails() {
		$lookup = new TypeDispatchingEntityTitleStoreLookup(
			[
				'property' => function ( EntityTitleStoreLookup $defaultService ) {
					return (object)[];
				},
			],
			$this->newDefaultService()
		);

		$this->expectException( PostconditionException::class );
		$lookup->getTitleForId( new NumericPropertyId( 'P1' ) );
	}

	public function testGivenUnknownEntityType_getTitleForIdForwardsToDefaultService() {
		$id = new NumericPropertyId( 'P1' );
		$lookup = new TypeDispatchingEntityTitleStoreLookup(
			[],
			$this->newDefaultService( $id )
		);

		$result = $lookup->getTitleForId( $id );
		$this->assertSame( 'fromDefaultService', $result );
	}

	public function testGivenCustomEntityType_getTitleForIdInstantiatesCustomService() {
		$id = new NumericPropertyId( 'P1' );
		$lookup = new TypeDispatchingEntityTitleStoreLookup(
			[
				'property' => function ( EntityTitleStoreLookup $defaultService ) use ( $id ) {
					$customService = $this->createMock( EntityTitleStoreLookup::class );
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
		$defaultService = $this->createMock( EntityTitleStoreLookup::class );

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

	public function testDispatchingLookupBatchCallContinuesToBatch() {
		$defaultService = $this->createMock( EntityTitleStoreLookup::class );
		$defaultService->expects( $this->once() )
			->method( 'getTitlesForIds' )->willReturn( [] );
		$lookup = new TypeDispatchingEntityTitleStoreLookup(
			[],
			$defaultService
		);
		$lookup->getTitlesForIds( [ new ItemId( 'Q3214' ) ] );
	}

}
