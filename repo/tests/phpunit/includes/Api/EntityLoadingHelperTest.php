<?php

namespace Wikibase\Test\Repo\Api;

use ApiBase;
use Exception;
use PHPUnit_Framework_MockObject_MockObject;
use UsageException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\LegacyIdInterpreter;
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
	 * @return ApiBase|PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getMockApiBase() {
		return $this->getMockBuilder( ApiBase::class )
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @param mixed $entityRevisionReturn if value is instance of Exception it will be thrown;
	 * If it is false, 0 calls will be expected. Instances of EntityRevision (and null) will be
	 * returned as is.
	 *
	 * @return EntityRevisionLookup|PHPUnit_Framework_MockObject_MockObject
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
		} elseif( $entityRevisionReturn instanceof EntityRevision ) {
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
			$this->getMockApiBase(),
			new BasicEntityIdParser(),
			$this->getMockEntityRevisionLookup( $lookupResult ),
			$this->getMockErrorReporter( $expectedExceptionCode, $expectedErrorCode )
		);
	}

	public function testLoadEntityRevision() {
		$revision = $this->getMockRevision();

		$helper = $this->newEntityLoadingHelper( $revision );

		$return = $helper->loadEntityRevision( new ItemId( 'Q1' ) );

		$this->assertSame( $revision, $return );

		$this->markTestIncomplete( 'No tests for failure cases, since this method is not intended to stay public.' );
	}

	public function testGetEntityIdFromParams() {
		$helper = $this->newEntityLoadingHelper();

		$result = $helper->getEntityIdFromParams( [ 'entity' => 'Q12' ] );
		$this->assertEquals( new ItemId( 'Q12' ), $result );

		$helper->setEntityIdParam( 'foo' );
		$result = $helper->getEntityIdFromParams( [ 'foo' => 'Q21' ] );
		$this->assertEquals( new ItemId( 'Q21' ), $result );

		$this->markTestIncomplete( 'Only basic test, since this method is not intended to stay public.' );
	}

	public function testLoadEntity() {
		$revision = $this->getMockRevision();
		$entity = $revision->getEntity();

		$helper = $this->newEntityLoadingHelper( $revision );
		$return = $helper->loadEntity( new ItemId( 'Q1' ) );

		$this->assertSame( $entity, $return );

		$this->markTestIncomplete( 'FIXME: needs test for ID supplied via request params, directly and as site:title.' );
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
