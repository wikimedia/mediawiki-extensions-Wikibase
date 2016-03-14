<?php

namespace Wikibase\Test\Repo\Api;

use ApiBase;
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
use Wikibase\Repo\Api\ApiErrorReporter;
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
class EntitySavingHelperTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return ApiBase|PHPUnit_Framework_MockObject_MockObject
	 */
	private function getMockApiBase() {
		return $this->getMockBuilder( ApiBase::class )
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @return ApiErrorReporter
	 */
	private function getMockErrorReporter() {
		return $this->getMockBuilder( ApiErrorReporter::class )
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
	 * @param int $calls
	 *
	 * @return EditEntity
	 */
	private function getMockEditEntity( $calls ) {
		$mock = $this->getMockBuilder( EditEntity::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->exactly( $calls ) )
			->method( 'attemptSave' )
			->will( $this->returnValue( Status::newGood() ) );
		return $mock;
	}

	/**
	 * @param int $calls
	 *
	 * @return EditEntityFactory
	 */
	private function getMockEditEntityFactory( $calls ) {
		$mock = $this->getMockBuilder( EditEntityFactory::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->exactly( $calls ) )
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
			$this->getMockErrorReporter(),
			$this->getMockSummaryFormatter(),
			$this->getMockEditEntityFactory( 0 )
		);

		$this->setExpectedException( LogicException::class );
		$helper->attemptSaveEntity( new Item(), '' );
	}

}
