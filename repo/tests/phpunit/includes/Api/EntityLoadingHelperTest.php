<?php

namespace Wikibase\Test\Repo\Api;

use Exception;
use UsageException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\BadRevisionException;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Repo\Api\ApiErrorReporter;
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
	 * @param mixed $entityRevisionReturn if value is instance of Exception it will be thrown;
	 * If it is false, 0 calls will be expected. Instances of EntityRevision (and null) will be
	 * returned as is.
	 *
	 * @return EntityRevisionLookup
	 */
	protected function getMockEntityRevisionLookup( $entityRevisionReturn ) {
		$mock = $this->getMock( EntityRevisionLookup::class );

		if ( $entityRevisionReturn === false ) {
			$mock->expects( $this->never() )
				->method( 'getEntityRevision' );
		} elseif ( $entityRevisionReturn instanceof Exception ) {
			$mock->expects( $this->once() )
				->method( 'getEntityRevision' )
				->will( $this->throwException( $entityRevisionReturn ) );
		} elseif ( $entityRevisionReturn instanceof EntityRevision ) {
			$mock->expects( $this->once() )
				->method( 'getEntityRevision' )
				->will( $this->returnValue( $entityRevisionReturn ) );
		}
		return $mock;
	}

	/**
	 * @param string|null $expectedExceptionCode
	 * @param string|null $expectedErrorCode
	 * @return ApiErrorReporter
	 */
	protected function getMockErrorReporter( $expectedExceptionCode = null, $expectedErrorCode = null ) {
		$mock = $this->getMockBuilder( ApiErrorReporter::class )
			->disableOriginalConstructor()
			->getMock();
		if ( $expectedExceptionCode ) {
			$mock->expects( $this->once() )
				->method( 'dieException' )
				->with( $this->isInstanceOf( Exception::class ), $expectedExceptionCode )
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

	/**
	 * @return EntityRevision
	 */
	protected function getMockRevision() {
		$entity = $this->getMock( EntityDocument::class );

		$revision = $this->getMockBuilder( EntityRevision::class )
			->disableOriginalConstructor()
			->getMock();

		$revision->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnValue( $entity ) );

		return $revision;
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
		return new EntityLoadingHelper(
			$this->getMockEntityRevisionLookup( $lookupResult ),
			$this->getMockErrorReporter( $expectedExceptionCode, $expectedErrorCode )
		);
	}

	public function testLoadEntity() {
		$revision = $this->getMockRevision();
		$entity = $revision->getEntity();

		$helper = $this->newEntityLoadingHelper( $revision );

		$return = $helper->loadEntity( new ItemId( 'Q1' ) );

		$this->assertSame( $entity, $return );
	}

	public function testLoadEntity_NullRevision() {
		$helper = $this->newEntityLoadingHelper( null, null, 'cant-load-entity-content' );

		$this->setExpectedException( UsageException::class );
		$helper->loadEntity( new ItemId( 'Q1' ) );
	}

	public function testLoadEntity_UnresolvedRedirectException() {
		$helper = $this->newEntityLoadingHelper(
			new RevisionedUnresolvedRedirectException(
				new ItemId( 'Q1' ),
				new ItemId( 'Q1' )
			),
			'unresolved-redirect'
		);

		$this->setExpectedException( UsageException::class );
		$helper->loadEntity( new ItemId( 'Q1' ) );
	}

	public function testLoadEntity_BadRevisionException() {
		$helper = $this->newEntityLoadingHelper( new BadRevisionException(), 'nosuchrevid' );

		$this->setExpectedException( UsageException::class );
		$helper->loadEntity( new ItemId( 'Q1' ) );
	}

	public function testLoadEntity_StorageException() {
		$helper = $this->newEntityLoadingHelper( new StorageException(), 'cant-load-entity-content' );

		$this->setExpectedException( UsageException::class );
		$helper->loadEntity( new ItemId( 'Q1' ) );
	}

}
