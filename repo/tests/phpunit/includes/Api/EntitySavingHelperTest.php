<?php

namespace Wikibase\Test\Repo\Api;

use LogicException;
use RequestContext;
use Status;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Repo\Api\EntitySavingHelper;

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

	private function getMockApiBase() {
		return $this->getMockBuilder( 'ApiBase' )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getMockErrorReporter() {
		return $this->getMockBuilder( 'Wikibase\Repo\Api\ApiErrorReporter' )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getMockSummaryFormatter() {
		return $this->getMockBuilder( 'Wikibase\SummaryFormatter' )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getMockEditEntity( $calls ) {
		$mock = $this->getMockBuilder( 'Wikibase\EditEntity' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->exactly( $calls ) )
			->method( 'attemptSave' )
			->will( $this->returnValue( Status::newGood() ) );
		return $mock;
	}

	private function getMockEditEntityFactory( $calls ) {
		$mock = $this->getMockBuilder( 'Wikibase\EditEntityFactory' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->exactly( $calls ) )
			->method( 'newEditEntity' )
			->will( $this->returnValue( $this->getMockEditEntity( $calls ) ) );
		return $mock;
	}

	private function newContext() {
		$user = $this->getMockBuilder( 'User' )
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
