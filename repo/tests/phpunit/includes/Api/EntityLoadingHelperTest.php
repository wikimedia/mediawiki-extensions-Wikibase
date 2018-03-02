<?php

namespace Wikibase\Repo\Tests\Api;

use ApiBase;
use Exception;
use PHPUnit_Framework_MockObject_MockObject;
use ApiUsageException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lib\Store\EntityByLinkedTitleLookup;
use Wikibase\Lib\Store\EntityRevision;
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
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Daniel Kinzler
 */
class EntityLoadingHelperTest extends \MediaWikiTestCase {

	/**
	 * @param array $params
	 *
	 * @return ApiBase|PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getMockApiBase( array $params ) {
		$apiBase = $this->getMockBuilder( ApiBase::class )
			->disableOriginalConstructor()
			->getMock();

		$apiBase->expects( $this->any() )
			->method( 'extractRequestParams' )
			->will( $this->returnValue( $params ) );

		return $apiBase;
	}

	/**
	 * @param EntityId|null $entityId Entity ID getEntityRevision() should expect.
	 * @param EntityRevision|null $entityRevision The EntityRevision getEntityRevision() should return.
	 * @param Exception|null $exception The Exception getEntityRevision() should throw.
	 *
	 * @return EntityRevisionLookup|PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getMockEntityRevisionLookup(
		EntityId $entityId = null,
		EntityRevision $entityRevision = null,
		Exception $exception = null
	) {
		$mock = $this->getMock( EntityRevisionLookup::class );

		if ( !$entityId ) {
			$mock->expects( $this->never() )
				->method( 'getEntityRevision' );
		} else {
			$invocation = $mock->expects( $this->once() )
				->method( 'getEntityRevision' )
				->with( $entityId );

			if ( $exception ) {
				$invocation->will( $this->throwException( $exception ) );
			} else {
				$invocation->will( $this->returnValue( $entityRevision ) );
			}
		}

		return $mock;
	}

	/**
	 * @param string|null $expectedExceptionCode
	 * @param string|null $expectedErrorCode
	 *
	 * @return ApiErrorReporter
	 */
	protected function getMockErrorReporter( $expectedExceptionCode, $expectedErrorCode ) {
		$mock = $this->getMockBuilder( ApiErrorReporter::class )
			->disableOriginalConstructor()
			->getMock();
		$apiUsageException = ApiUsageException::newWithMessage( null, 'mockApiUsageException' );

		if ( $expectedExceptionCode ) {
			$mock->expects( $this->once() )
				->method( 'dieException' )
				->with( $this->isInstanceOf( Exception::class ), $expectedExceptionCode )
				->will( $this->throwException( $apiUsageException ) );
		} else {
			$mock->expects( $this->never() )
				->method( 'dieException' );
		}

		// TODO: Remove the deprecated dieError when it is not used any more.
		$dieWithErrorCodeMethods = $this->logicalOr( 'dieWithError', 'dieError' );

		if ( $expectedErrorCode ) {
			$mock->expects( $this->once() )
				->method( $dieWithErrorCodeMethods )
				->with( $this->anything(), $expectedErrorCode )
				->will( $this->throwException( $apiUsageException ) );
		} else {
			$mock->expects( $this->never() )
				->method( $dieWithErrorCodeMethods );
		}

		return $mock;
	}

	/**
	 * @return EntityRevision|PHPUnit_Framework_MockObject_MockObject
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
	 * @param array $config Associative configuration array. Known keys:
	 *   - params: request parameters, as an associative array
	 *   - entityId: The ID expected by getEntityRevisions
	 *   - revision: EntityRevision to return from getEntityRevisions
	 *   - exception: Exception to throw from getEntityRevisions
	 *   - dieErrorCode: The error code expected by dieError
	 *   - dieExceptionCode: The error code expected by dieException
	 *
	 * @return EntityLoadingHelper
	 */
	protected function newEntityLoadingHelper( array $config ) {
		return new EntityLoadingHelper(
			$this->getMockApiBase( isset( $config['params'] ) ? $config['params'] : [] ),
			new ItemIdParser(),
			$this->getMockEntityRevisionLookup(
				isset( $config['entityId'] ) ? $config['entityId'] : null,
				isset( $config['revision'] ) ? $config['revision'] : null,
				isset( $config['exception'] ) ? $config['exception'] : null
			),
			$this->getMockErrorReporter(
				isset( $config['dieExceptionCode'] ) ? $config['dieExceptionCode'] : null,
				isset( $config['dieErrorCode'] ) ? $config['dieErrorCode'] : null
			)
		);
	}

	public function testLoadEntity() {
		$revision = $this->getMockRevision();
		$entity = $revision->getEntity();
		$id = new ItemId( 'Q1' );

		$helper = $this->newEntityLoadingHelper( [
			'entityId' => $id,
			'revision' => $revision,
		] );
		$return = $helper->loadEntity( $id );

		$this->assertSame( $entity, $return );
	}

	public function testLoadEntity_idFromRequestParams() {
		$revision = $this->getMockRevision();
		$entity = $revision->getEntity();
		$id = new ItemId( 'Q1' );

		$params = [ 'entity' => 'Q1' ];
		$helper = $this->newEntityLoadingHelper( [
			'params' => $params,
			'entityId' => $id,
			'revision' => $revision,
		] );
		$return = $helper->loadEntity();

		$this->assertSame( $entity, $return );
	}

	public function testLoadEntity_titleFromRequestParams() {
		$revision = $this->getMockRevision();
		$entity = $revision->getEntity();
		$id = new ItemId( 'Q1' );

		$params = [ 'site' => 'foowiki', 'title' => 'FooBar' ];
		$helper = $this->newEntityLoadingHelper( [
			'params' => $params,
			'entityId' => $id,
			'revision' => $revision,
		] );

		$entityByLinkedTitleLookup = $this->getMock( EntityByLinkedTitleLookup::class );
		$entityByLinkedTitleLookup->expects( $this->once() )
			->method( 'getEntityIdForLinkedTitle' )
			->with( 'foowiki', 'FooBar' )
			->will( $this->returnValue( $id ) );

		$helper->setEntityByLinkedTitleLookup( $entityByLinkedTitleLookup );

		$return = $helper->loadEntity();
		$this->assertSame( $entity, $return );
	}

	public function testLoadEntity_badId() {
		$params = [ 'entity' => 'xyz' ];
		$helper = $this->newEntityLoadingHelper( [
			'params' => $params,
			'dieExceptionCode' => 'invalid-entity-id'
		] );

		$this->setExpectedException( ApiUsageException::class );
		$helper->loadEntity();
	}

	public function testLoadEntity_noId() {
		$helper = $this->newEntityLoadingHelper( [
			'params' => [],
			'dieErrorCode' => 'no-entity-id'
		] );

		$this->setExpectedException( ApiUsageException::class );
		$helper->loadEntity();
	}

	public function testLoadEntity_NotFound() {
		$id = new ItemId( 'Q1' );

		$helper = $this->newEntityLoadingHelper( [
			'entityId' => $id,
			'dieErrorCode' => 'no-such-entity'
		] );

		$this->setExpectedException( ApiUsageException::class );
		$helper->loadEntity( $id );
	}

	public function testLoadEntity_UnresolvedRedirectException() {
		$id = new ItemId( 'Q1' );

		$helper = $this->newEntityLoadingHelper( [
			'entityId' => $id,
			'exception' => new RevisionedUnresolvedRedirectException(
				$id,
				new ItemId( 'Q11' )
			),
			'dieExceptionCode' => 'unresolved-redirect'
		] );

		$this->setExpectedException( ApiUsageException::class );
		$helper->loadEntity( $id );
	}

	public function testLoadEntity_BadRevisionException() {
		$id = new ItemId( 'Q1' );

		$helper = $this->newEntityLoadingHelper( [
			'entityId' => $id,
			'exception' => new BadRevisionException(),
			'dieExceptionCode' => 'nosuchrevid'
		] );

		$this->setExpectedException( ApiUsageException::class );
		$helper->loadEntity( $id );
	}

	public function testLoadEntity_StorageException() {
		$id = new ItemId( 'Q1' );

		$helper = $this->newEntityLoadingHelper( [
			'entityId' => $id,
			'exception' => new StorageException(),
			'dieExceptionCode' => 'cant-load-entity-content'
		] );

		$this->setExpectedException( ApiUsageException::class );
		$helper->loadEntity( $id );
	}

}
