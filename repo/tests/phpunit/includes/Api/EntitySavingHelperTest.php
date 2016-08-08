<?php

namespace Wikibase\Test\Repo\Api;

use ApiBase;
use Exception;
use LogicException;
use PHPUnit_Framework_MockObject_MockObject;
use RequestContext;
use Status;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\EditEntity;
use Wikibase\EditEntityFactory;
use Wikibase\EntityRevision;
use Wikibase\Repo\Api\EntityLoadingHelper;
use Wikibase\Repo\Api\EntitySavingHelper;
use Wikibase\SummaryFormatter;

/**
 * @covers Wikibase\Repo\Api\EntitySavingHelper
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class EntitySavingHelperTest extends EntityLoadingHelperTest {

	/**
	 * @return ApiBase|PHPUnit_Framework_MockObject_MockObject
	 */
	private function getMockApiBase() {
		return $this->getMockBuilder( ApiBase::class )
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @return SummaryFormatter
	 */
	private function getMockSummaryFormatter() {
		return $this->getMockBuilder( SummaryFormatter::class )
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @param int|null $calls
	 *
	 * @return EditEntity
	 */
	private function getMockEditEntity( $calls = null ) {
		$mock = $this->getMockBuilder( EditEntity::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $calls === null ? $this->any() : $this->exactly( $calls ) )
			->method( 'attemptSave' )
			->will( $this->returnValue( Status::newGood() ) );
		return $mock;
	}

	/**
	 * @param int|null $calls
	 *
	 * @return EditEntityFactory
	 */
	private function getMockEditEntityFactory( $calls = null ) {
		$mock = $this->getMockBuilder( EditEntityFactory::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $calls === null ? $this->any() : $this->exactly( $calls ) )
			->method( 'newEditEntity' )
			->will( $this->returnValue( $this->getMockEditEntity( $calls ) ) );
		return $mock;
	}

	private function newContext() {
		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$context = new RequestContext();
		$context->setUser( $user );
		return $context;
	}

	public function testLoadEntity_baserevid() {
		$itemId = new ItemId( 'Q1' );

		$revision = $this->getMockRevision();
		$entity = $revision->getEntity();

		$mockApiBase = $this->getMockApiBase();
		$mockApiBase->expects( $this->any() )
			->method( 'isWriteMode' )
			->will( $this->returnValue( true ) );
		$mockApiBase->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $this->newContext() ) );
		$mockApiBase->expects( $this->any() )
			->method( 'extractRequestParams' )
			->will( $this->returnValue( array( 'baserevid' => 17 ) ) );

		$revisionLookup = $this->getMockEntityRevisionLookup( true );
		$revisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $itemId, 17 )
			->will( $this->returnValue( $revision ) );

		$helper = new EntitySavingHelper(
			$mockApiBase,
			$revisionLookup,
			$this->getMockErrorReporter(),
			$this->getMockSummaryFormatter(),
			$this->getMockEditEntityFactory()
		);

		$return = $helper->loadEntity( $itemId );

		$this->assertSame( $entity, $return );
	}

	public function testAttemptSave() {
		$mockApiBase = $this->getMockApiBase();
		$mockApiBase->expects( $this->once() )
			->method( 'isWriteMode' )
			->will( $this->returnValue( true ) );
		$mockApiBase->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->will( $this->returnValue( $this->newContext() ) );
		$mockApiBase->expects( $this->atLeastOnce() )
			->method( 'extractRequestParams' )
			->will( $this->returnValue( array() ) );

		$helper = new EntitySavingHelper(
			$mockApiBase,
			$this->getMockEntityRevisionLookup( false ),
			$this->getMockErrorReporter(),
			$this->getMockSummaryFormatter(),
			$this->getMockEditEntityFactory( 1 )
		);

		$entity = new Item();
		$entity->setId( new ItemId( 'Q444' ) );
		$entity->getFingerprint()->setLabel( 'en', 'Foo' );
		$entity->getSiteLinkList()->addNewSiteLink( 'enwiki', 'APage' );
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

		$helper = new EntitySavingHelper(
			$mockApiBase,
			$this->getMockEntityRevisionLookup( false ),
			$this->getMockErrorReporter(),
			$this->getMockSummaryFormatter(),
			$this->getMockEditEntityFactory( 0 )
		);

		$this->setExpectedException( LogicException::class );
		$helper->attemptSaveEntity( new Item(), '' );
	}

	/**
	 * @param EntityRevision|Exception|null $lookupResult
	 * @param string|null $expectedError
	 * @return EntityLoadingHelper
	 */
	protected function newEntityLoadingHelper(
		$lookupResult = null,
		$expectedExceptionCode = null,
		$expectedErrorCode = null
	) {
		$mockApiBase = $this->getMockApiBase();
		$mockApiBase->expects( $this->any() )
			->method( 'isWriteMode' )
			->will( $this->returnValue( true ) );
		$mockApiBase->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $this->newContext() ) );
		$mockApiBase->expects( $this->any() )
			->method( 'extractRequestParams' )
			->will( $this->returnValue( array() ) );

		return new EntitySavingHelper(
			$mockApiBase,
			$this->getMockEntityRevisionLookup( $lookupResult ),
			$this->getMockErrorReporter( $expectedExceptionCode, $expectedErrorCode ),
			$this->getMockSummaryFormatter(),
			$this->getMockEditEntityFactory()
		);
	}

}
