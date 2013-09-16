<?php

namespace Wikibase\Test\Api;

use ApiMain;
use UsageException;
use Wikibase\Api\EntityHelper;
use Wikibase\ItemContent;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Api\EntityHelper
 *
 * @since 0.5
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseAPI
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Adam Shorland
 */
class EntityHelperTest extends \PHPUnit_Framework_TestCase {

	private function getNewInstance( $apiMain = null ) {
		if ( $apiMain === null ) {
			$apiMain = new ApiMain();
		}

		$modificationHelper = new EntityHelper(
			$apiMain,
			WikibaseRepo::getDefaultInstance()->getEntityIdParser()
		);

		return $modificationHelper;
	}

	public function testGetEntityTitleFromEntityId() {
		$item = ItemContent::newFromArray( array( 'entity' => 'q42' ) )->getEntity();
		$entityId = $item->getId();

		$claimModificationHelper = $this->getNewInstance();
		$this->assertInstanceOf( '\Title', $claimModificationHelper->getEntityTitleFromEntityId( $entityId ) );
	}

	public function testValidGetEntityIdFromString() {
		$validEntityIdString = 'q55';

		$claimModificationHelper = $this->getNewInstance();
		$this->assertInstanceOf(
			'\Wikibase\EntityId',
			$claimModificationHelper->getEntityIdFromString( $validEntityIdString )
		);
	}

	/**
	 * @expectedException UsageException
	 */
	public function testInvalidGetEntityIdFromString() {
		$invalidEntityIdString = 'no!';
		$claimModificationHelper = $this->getNewInstance();
		$claimModificationHelper->getEntityIdFromString( $invalidEntityIdString );
	}

}