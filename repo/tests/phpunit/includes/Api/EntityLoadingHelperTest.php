<?php

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use Exception;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWikiIntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lib\Store\BadRevisionException;
use Wikibase\Lib\Store\EntityByLinkedTitleLookup;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\EntityLoadingHelper;
use Wikibase\Repo\Store\EntityTitleStoreLookup;

/**
 * @covers \Wikibase\Repo\Api\EntityLoadingHelper
 *
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Daniel Kinzler
 */
class EntityLoadingHelperTest extends MediaWikiIntegrationTestCase {

	protected function getMockRevisionLookup( ?int $revisionId, ?RevisionRecord $revision ): RevisionLookup {
		$revisionLookup = $this->createMock( RevisionLookup::class );
		if ( $revisionId !== null ) {
			$revisionLookup->method( 'getRevisionById' )
				->with( $revisionId )
				->willReturn( $revision );
		} else {
			$this->assertNull( $revision );
			$revisionLookup->expects( $this->never() )
				->method( 'getRevisionById' );
		}
		return $revisionLookup;
	}

	/**
	 * @param EntityId|null $entityId Entity ID getEntityRevision() should expect.
	 * @param EntityRevision|null $entityRevision The EntityRevision getEntityRevision() should return.
	 * @param Exception|null $exception The Exception getEntityRevision() should throw.
	 *
	 * @return EntityRevisionLookup|MockObject
	 */
	protected function getMockEntityRevisionLookup(
		EntityId $entityId = null,
		EntityRevision $entityRevision = null,
		Exception $exception = null
	) {
		$mock = $this->createMock( EntityRevisionLookup::class );

		if ( !$entityId ) {
			$mock->expects( $this->never() )
				->method( 'getEntityRevision' );
		} else {
			$invocation = $mock->expects( $this->once() )
				->method( 'getEntityRevision' )
				->with( $entityId );

			if ( $exception ) {
				$invocation->willThrowException( $exception );
			} else {
				$invocation->willReturn( $entityRevision );
			}
		}

		return $mock;
	}

	protected function getMockEntityTitleStoreLookup(
		?EntityId $entityId,
		?Title $title
	): EntityTitleStoreLookup {
		$entityTitleStoreLookup = $this->createMock( EntityTitleStoreLookup::class );
		if ( $entityId !== null ) {
			$entityTitleStoreLookup->method( 'getTitleForId' )
				->with( $entityId )
				->willReturn( $title );
		} else {
			$this->assertNull( $title );
			$entityTitleStoreLookup->expects( $this->never() )
				->method( 'getTitleForId' );
		}
		return $entityTitleStoreLookup;
	}

	/**
	 * @param string|null $expectedExceptionCode
	 * @param string|null $expectedErrorCode
	 *
	 * @return ApiErrorReporter
	 */
	protected function getMockErrorReporter( $expectedExceptionCode, $expectedErrorCode ) {
		$mock = $this->createMock( ApiErrorReporter::class );
		$apiUsageException = ApiUsageException::newWithMessage( null, 'mockApiUsageException' );

		if ( $expectedExceptionCode ) {
			$mock->expects( $this->once() )
				->method( 'dieException' )
				->with( $this->isInstanceOf( Exception::class ), $expectedExceptionCode )
				->willThrowException( $apiUsageException );
		} else {
			$mock->expects( $this->never() )
				->method( 'dieException' );
		}

		// TODO: Remove the deprecated dieError when it is not used any more.
		$dieWithErrorCodeMethods = $this->logicalOr( 'dieWithError', 'dieError', 'dieStatus' );

		if ( $expectedErrorCode ) {
			$mock->expects( $this->once() )
				->method( $dieWithErrorCodeMethods )
				->with( $this->anything(), $expectedErrorCode )
				->willThrowException( $apiUsageException );
		} else {
			$mock->expects( $this->never() )
				->method( $dieWithErrorCodeMethods );
		}

		return $mock;
	}

	/**
	 * @return EntityRevision|MockObject
	 */
	protected function getMockRevision() {
		$entity = $this->createMock( EntityDocument::class );

		$revision = $this->createMock( EntityRevision::class );

		$revision->method( 'getEntity' )
			->willReturn( $entity );

		return $revision;
	}

	/**
	 * @param array $config Associative configuration array. Known keys:
	 *   - params: request parameters, as an associative array
	 *   - entityId: The ID expected by getEntityRevisions
	 *   - entityRevision: EntityRevision to return from getEntityRevisions
	 *   - exception: Exception to throw from getEntityRevisions
	 *   - revisionId: The ID expected by RevisionLookup
	 *   - revision: RevisionRecord to return from RevisionLookup
	 *   - entityTitle: Title to return from EntityTitleStoreLookup
	 *   - dieErrorCode: The error code expected by dieError
	 *   - dieExceptionCode: The error code expected by dieException
	 *
	 * @return EntityLoadingHelper
	 */
	protected function newEntityLoadingHelper( array $config ) {
		$services = MediaWikiServices::getInstance();

		return new EntityLoadingHelper(
			$this->getMockRevisionLookup(
				$config['revisionId'] ?? null,
				$config['revision'] ?? null
			),
			$services->getTitleFactory(),
			new ItemIdParser(),
			$this->getMockEntityRevisionLookup(
				$config['entityId'] ?? null,
				$config['entityRevision'] ?? null,
				$config['exception'] ?? null
			),
			$this->getMockEntityTitleStoreLookup(
				$config['entityId'] ?? null,
				$config['entityTitle'] ?? null
			),
			$this->getMockErrorReporter(
				$config['dieExceptionCode'] ?? null,
				$config['dieErrorCode'] ?? null
			)
		);
	}

	public function testLoadEntity() {
		$revision = $this->getMockRevision();
		$entity = $revision->getEntity();
		$id = new ItemId( 'Q1' );

		$helper = $this->newEntityLoadingHelper( [
			'entityId' => $id,
			'entityRevision' => $revision,
		] );
		$return = $helper->loadEntity( [], $id );

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
			'entityRevision' => $revision,
		] );
		$return = $helper->loadEntity( $params );

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
			'entityRevision' => $revision,
		] );

		$entityByLinkedTitleLookup = $this->createMock( EntityByLinkedTitleLookup::class );
		$entityByLinkedTitleLookup->expects( $this->once() )
			->method( 'getEntityIdForLinkedTitle' )
			->with( 'foowiki', 'FooBar' )
			->willReturn( $id );

		$helper->setEntityByLinkedTitleLookup( $entityByLinkedTitleLookup );

		$return = $helper->loadEntity( $params );
		$this->assertSame( $entity, $return );
	}

	public function testLoadEntity_badId() {
		$params = [ 'entity' => 'xyz' ];
		$helper = $this->newEntityLoadingHelper( [
			'params' => $params,
			'dieExceptionCode' => 'invalid-entity-id',
		] );

		$this->expectException( ApiUsageException::class );
		$helper->loadEntity( $params );
	}

	public function testLoadEntity_noId() {
		$helper = $this->newEntityLoadingHelper( [
			'params' => [],
			'dieErrorCode' => 'no-entity-id',
		] );

		$this->expectException( ApiUsageException::class );
		$helper->loadEntity( [] );
	}

	public function testLoadEntity_NotFound() {
		$id = new ItemId( 'Q1' );

		$helper = $this->newEntityLoadingHelper( [
			'entityId' => $id,
			'dieErrorCode' => 'no-such-entity',
		] );

		$this->expectException( ApiUsageException::class );
		$helper->loadEntity( [], $id );
	}

	public function testLoadEntity_UnresolvedRedirectException() {
		$id = new ItemId( 'Q1' );

		$helper = $this->newEntityLoadingHelper( [
			'entityId' => $id,
			'exception' => new RevisionedUnresolvedRedirectException(
				$id,
				new ItemId( 'Q11' )
			),
			'dieExceptionCode' => 'unresolved-redirect',
		] );

		$this->expectException( ApiUsageException::class );
		$helper->loadEntity( [], $id );
	}

	public function testLoadEntity_BadRevisionException_missingRevision() {
		$id = new ItemId( 'Q1' );

		$helper = $this->newEntityLoadingHelper( [
			'entityId' => $id,
			'revisionId' => 0,
			'revision' => null,
			'exception' => new BadRevisionException(),
			'dieExceptionCode' => 'nosuchrevid',
		] );

		$this->expectException( ApiUsageException::class );
		$helper->loadEntity( [], $id );
	}

	public function testLoadEntity_StorageException() {
		$id = new ItemId( 'Q1' );

		$helper = $this->newEntityLoadingHelper( [
			'entityId' => $id,
			'exception' => new StorageException(),
			'dieExceptionCode' => 'cant-load-entity-content',
		] );

		$this->expectException( ApiUsageException::class );
		$helper->loadEntity( [], $id );
	}

}
