<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\MultipleEntitySourceServices;
use Wikibase\DataAccess\SingleEntitySourceServices;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevision;

/**
 * @covers \Wikibase\DataAccess\MultipleEntitySourceServices
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MultipleEntitySourceServicesTest extends \PHPUnit_Framework_TestCase {

	use \PHPUnit4And6Compat;

	public function testEntityFromKnownSourceUpdated_entityUpdatedPassedToRelevantServiceContainer() {
		$itemRevision = new EntityRevision( new Item( new ItemId( 'Q1' ) ) );

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->expects( $this->atLeastOnce() )
			->method( 'entityUpdated' )
			->with( $itemRevision );
		$propertyServices = $this->createMock( SingleEntitySourceServices::class );
		$propertyServices->expects( $this->never() )
			->method( 'entityUpdated' );

		$services = $this->newMultipleEntitySourceServices( [ 'items' => $itemServices, 'props' => $propertyServices ] );

		$services->entityUpdated( $itemRevision );
	}

	public function testEntityFromKnownSourceDeleted_entityDeletedPassedToRelevantServiceContainer() {
		$itemId = new ItemId( 'Q1' );

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->expects( $this->atLeastOnce() )
			->method( 'entityDeleted' )
			->with( $itemId );
		$propertyServices = $this->createMock( SingleEntitySourceServices::class );
		$propertyServices->expects( $this->never() )
			->method( 'entityDeleted' );

		$services = $this->newMultipleEntitySourceServices( [ 'items' => $itemServices, 'props' => $propertyServices ] );

		$services->entityDeleted( $itemId );
	}

	public function testRedirectOfEntityFromKnownSourceDeleted_redirectUpdatedPassedToRelevantServiceContainer() {
		$itemRedirect = new EntityRedirect( new ItemId( 'Q1' ), new ItemId( 'Q300' ) );
		$revisionId = 333;

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->expects( $this->atLeastOnce() )
			->method( 'redirectUpdated' )
			->with( $itemRedirect, $revisionId );
		$propertyServices = $this->createMock( SingleEntitySourceServices::class );
		$propertyServices->expects( $this->never() )
			->method( 'redirectUpdated' );

		$services = $this->newMultipleEntitySourceServices( [ 'items' => $itemServices, 'props' => $propertyServices ] );

		$services->redirectUpdated( $itemRedirect, $revisionId );
	}

	/**
	 * @param SingleEntitySourceServices[] $perSourceServices
	 * @return MultipleEntitySourceServices
	 */
	private function newMultipleEntitySourceServices( array $perSourceServices ) {
		return new MultipleEntitySourceServices(
			new EntitySourceDefinitions( [
				new EntitySource( 'items', 'itemdb', [ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ] ] ),
				new EntitySource( 'props', 'propb', [ 'property' => [ 'namespaceId' => 200, 'slot' => 'main' ] ] ),
			] ),
			$perSourceServices
		);
	}

}
