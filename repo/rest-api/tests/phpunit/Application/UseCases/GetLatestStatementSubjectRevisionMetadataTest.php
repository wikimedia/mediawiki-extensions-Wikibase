<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestStatementSubjectRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\ReadModel\LatestStatementSubjectRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\StatementSubjectRevisionMetaDataRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetLatestStatementSubjectRevisionMetadata
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetLatestStatementSubjectRevisionMetadataTest extends TestCase {

	/**
	 * @dataProvider provideStatementSubjectId
	 */
	public function testExecute( EntityId $subjectId ): void {
		$expectedRevisionId = 123;
		$expectedLastModified = '20220101001122';

		$metadataRetriever = $this->createMock( StatementSubjectRevisionMetadataRetriever::class );
		$metadataRetriever->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $subjectId )
			->willReturn( LatestStatementSubjectRevisionMetadataResult::concreteRevision( $expectedRevisionId, $expectedLastModified ) );

		[ $revId, $lastModified ] = $this->newGetRevisionMetadata( $metadataRetriever )->execute( $subjectId );

		$this->assertSame( $expectedRevisionId, $revId );
		$this->assertSame( $expectedLastModified, $lastModified );
	}

	/**
	 * @dataProvider provideStatementSubjectId
	 */
	public function testGivenStatementSubjectDoesNotExist_throwsUseCaseError( EntityId $subjectId ): void {
		$metadataRetriever = $this->createStub( StatementSubjectRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestStatementSubjectRevisionMetadataResult::subjectNotFound() );

		try {
			$this->newGetRevisionMetadata( $metadataRetriever )->execute( $subjectId );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_SUBJECT_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( "Could not find an entity with the ID: {$subjectId}", $e->getErrorMessage() );
		}
	}

	public function testGivenItemRedirect_throwsItemRedirect(): void {
		$redirectSource = new ItemId( 'Q321' );

		$statementWithRedirectItem = new StatementGuid(
			$redirectSource,
			'FFFFFFFF-BBBB-CCCC-DDDD-EEEEEEEEEEEE'
		);

		$redirectTarget = 'Q123';

		$metadataRetriever = $this->createStub( StatementSubjectRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestStatementSubjectRevisionMetadataResult::redirect( new ItemId( $redirectTarget ) ) );

		try {
			$this->newGetRevisionMetadata( $metadataRetriever )->execute( $redirectSource );
			$this->fail( 'this should not be reached' );
		} catch ( ItemRedirect $e ) {
			$this->assertSame( $redirectTarget, $e->getRedirectTargetId() );
		}
	}

	private function newGetRevisionMetadata( StatementSubjectRevisionMetadataRetriever $metadataRetriever
	): GetLatestStatementSubjectRevisionMetadata {
		return new GetLatestStatementSubjectRevisionMetadata( $metadataRetriever );
	}

	public function provideStatementSubjectId(): Generator {
		yield 'item id' => [ new ItemId( 'Q123' ) ];
		yield 'property id' => [ new NumericPropertyId( 'P123' ) ];
	}

}
