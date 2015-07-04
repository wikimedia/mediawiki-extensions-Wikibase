<?php

namespace Wikibase\Test\Api;

use Status;
use TestUser;
use Title;
use Wikibase\Api\EntitySaveHelper;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers Wikibase\Test\Api\EntitySaverHelper
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class EntitySaverHelperTest extends \MediaWikiTestCase {

	private function getMockApiBase() {
		return $this->getMockBuilder( 'ApiBase' )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getMockErrorReporter() {
		return $this->getMockBuilder( 'Wikibase\Api\ApiErrorReporter' )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getMockSummaryFormatter() {
		return $this->getMockBuilder( 'Wikibase\SummaryFormatter' )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getMockEntityTitleLookup() {
		return $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
	}

	private function getMockEntityRevisionLookup() {
		return $this->getMock( 'Wikibase\Lib\Store\EntityRevisionLookup' );
	}

	private function getMockEntityStore() {
		return $this->getMock( 'Wikibase\Lib\Store\EntityStore' );
	}

	private function getMockEntityPermissionChecker() {
		return $this->getMock( 'Wikibase\Repo\Store\EntityPermissionChecker' );
	}

	private function getMockEditFilterHookRunner() {
		return $this->getMockBuilder( 'Wikibase\Repo\Hooks\EditFilterHookRunner' )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getMockContext() {
		return $this->getMock( 'RequestContext' );
	}

	public function testAttemptSave() {
		$testUser = new TestUser( 'aUser' );

		$mockContext = $this->getMockContext();
		$mockContext->expects( $this->once() )
			->method( 'getUser' )
			->will( $this->returnValue( $testUser->getUser() ) );

		$mockApiBase = $this->getMockApiBase();
		$mockApiBase->expects( $this->once() )
			->method( 'isWriteMode' )
			->will( $this->returnValue( true ) );
		$mockApiBase->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->will( $this->returnValue( $mockContext ) );
		$mockApiBase->expects( $this->atLeastOnce() )
			->method( 'extractRequestParams' )
			->will( $this->returnValue( array() ) );

		$mockTitleLookup = $this->getMockEntityTitleLookup();
		$mockTitleLookup->expects( $this->atLeastOnce() )
			->method( 'getTitleForId' )
			->will( $this->returnValue( Title::newFromText( 'Title' ) ) );

		$mockEntityStore = $this->getMockEntityStore();
		$mockEntityStore->expects( $this->once() )
			->method( 'updateWatchlist' );

		$mockEntityPermissionChecker = $this->getMockEntityPermissionChecker();
		$mockEntityPermissionChecker->expects( $this->atLeastOnce() )
			->method( 'getPermissionStatusForEntity' )
			->will( $this->returnValue( Status::newGood() ) );

		$mockEditFilterHookRunner = $this->getMockEditFilterHookRunner();
		$mockEditFilterHookRunner->expects( $this->atLeastOnce() )
			->method( 'run' )
			->will( $this->returnValue( Status::newGood() ) );

		$helper = new EntitySaveHelper(
			$mockApiBase,
			$this->getMockErrorReporter(),
			$this->getMockSummaryFormatter(),
			$mockTitleLookup,
			$this->getMockEntityRevisionLookup(),
			$mockEntityStore,
			$mockEntityPermissionChecker,
			$mockEditFilterHookRunner
		);

		$entity = new Item();
		$entity->setId( new ItemId( 'Q444' ) );
		$entity->getFingerprint()->setLabel( 'en', 'Foo' );
		$entity->getSiteLinkList()->addSiteLink( new SiteLink( 'enwiki', 'APage' ) );
		$entity->getStatements()->addNewStatement( new PropertyNoValueSnak( new PropertyId( 'P8' ) ) );

		$summary = 'A String Summary';
		$flags = 0;

		$status = $helper->attemptSaveEntity( $entity, $summary, $flags );

		$this->assertTrue( $status->isGood() );
	}

	public function testSaveThrowsException_onNonWriteMode() {
		$mockApiBase = $this->getMockApiBase();
		$mockApiBase->expects( $this->once() )
			->method( 'isWriteMode' )
			->will( $this->returnValue( false ) );

		$helper = new EntitySaveHelper(
			$mockApiBase,
			$this->getMockErrorReporter(),
			$this->getMockSummaryFormatter(),
			$this->getMockEntityTitleLookup(),
			$this->getMockEntityRevisionLookup(),
			$this->getMockEntityStore(),
			$this->getMockEntityPermissionChecker(),
			$this->getMockEditFilterHookRunner()
		);

		$this->setExpectedException( 'LogicException' );
		$helper->attemptSaveEntity( new Item(), '' );
	}

}
