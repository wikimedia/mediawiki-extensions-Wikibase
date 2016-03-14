<?php

namespace Wikibase\Test\Repo\Api;

use Exception;
use UsageException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\BadRevisionException;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Repo\Api\EntityLoadingHelper;

/**
 * @covers Wikibase\Repo\Api\EntityLoadingHelper
 *
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class EntityLoadingHelperTest extends \MediaWikiTestCase {

	/**
	 * @param mixed $entityRevisionReturn if value is instance of Exception it will be thrown
	 *
	 * @return EntityRevisionLookup
	 */
	public function getMockEntityRevisionLookup( $entityRevisionReturn ) {
		$mock = $this->getMock( 'Wikibase\Lib\Store\EntityRevisionLookup' );
		if ( $entityRevisionReturn instanceof Exception ) {
			$mock->expects( $this->once() )
				->method( 'getEntityRevision' )
				->will( $this->throwException( $entityRevisionReturn ) );
		} else {
			$mock->expects( $this->once() )
				->method( 'getEntityRevision' )
				->will( $this->returnValue( $entityRevisionReturn ) );
		}
		return $mock;
	}

	private function getMockErrorReporter( $expectedExceptionCode = null, $expectedErrorCode = null ) {
		$mock = $this->getMockBuilder( 'Wikibase\Repo\Api\ApiErrorReporter' )
			->disableOriginalConstructor()
			->getMock();
		if ( $expectedExceptionCode ) {
			$mock->expects( $this->once() )
				->method( 'dieException' )
				->with( $this->isInstanceOf( 'Exception' ), $expectedExceptionCode )
				->will( $this->throwException( new UsageException( 'mockUsageException', 'mock' ) ) );
		}
		if ( $expectedErrorCode ) {
			$mock->expects( $this->once() )
				->method( 'dieError' )
				->with( $this->isType( 'string' ), $expectedErrorCode )
				->will( $this->throwException( new UsageException( 'mockUsageException', 'mock' ) ) );
		}
		return $mock;
	}

	public function getMockRevision() {
		return $this->getMockBuilder( 'Revision' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testRevision_returnsRevision() {
		$revision = $this->getMockRevision();
		$helper = new EntityLoadingHelper(
			$this->getMockEntityRevisionLookup( $revision ),
			$this->getMockErrorReporter()
		);

		$return = $helper->loadEntityRevision( new ItemId( 'Q1' ) );

		$this->assertSame( $revision, $return );
	}

	public function testNullRevision_callsErrorReporter() {
		$helper = new EntityLoadingHelper(
			$this->getMockEntityRevisionLookup( null ),
			$this->getMockErrorReporter( null, 'cant-load-entity-content' )
		);

		$this->setExpectedException( UsageException::class );
		$helper->loadEntityRevision( new ItemId( 'Q1' ) );
	}

	public function testUnresolvedRedirectException_callsErrorReporter() {
		$helper = new EntityLoadingHelper(
			$this->getMockEntityRevisionLookup( new RevisionedUnresolvedRedirectException(
				new ItemId( 'Q1' ),
				new ItemId( 'Q1' )
			) ),
			$this->getMockErrorReporter( 'unresolved-redirect' )
		);

		$this->setExpectedException( UsageException::class );
		$helper->loadEntityRevision( new ItemId( 'Q1' ) );
	}

	public function testBadRevisionException_callsErrorReporter() {
		$helper = new EntityLoadingHelper(
			$this->getMockEntityRevisionLookup( new BadRevisionException() ),
			$this->getMockErrorReporter( 'nosuchrevid' )
		);

		$this->setExpectedException( UsageException::class );
		$helper->loadEntityRevision( new ItemId( 'Q1' ) );
	}

	public function testStorageException_callsErrorReporter() {
		$helper = new EntityLoadingHelper(
			$this->getMockEntityRevisionLookup( new StorageException() ),
			$this->getMockErrorReporter( 'cant-load-entity-content' )
		);

		$this->setExpectedException( UsageException::class );
		$helper->loadEntityRevision( new ItemId( 'Q1' ) );
	}

}
