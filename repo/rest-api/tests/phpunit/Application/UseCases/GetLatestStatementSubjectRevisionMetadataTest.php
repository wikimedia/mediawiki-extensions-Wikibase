<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases;

use Generator;
use PHPUnit\Framework\TestCase;
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
	 * @dataProvider provideStatementId
	 */
	public function testExecute( StatementGuid $statementId ): void {
		$expectedRevisionId = 123;
		$expectedLastModified = '20220101001122';

		$metadataRetriever = $this->createMock( StatementSubjectRevisionMetadataRetriever::class );
		$metadataRetriever->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $statementId )
			->willReturn( LatestStatementSubjectRevisionMetadataResult::concreteRevision( $expectedRevisionId, $expectedLastModified ) );

		[ $revId, $lastModified ] = $this->newGetRevisionMetadata( $metadataRetriever )->execute( $statementId );

		$this->assertSame( $expectedRevisionId, $revId );
		$this->assertSame( $expectedLastModified, $lastModified );
	}

	/**
	 * @dataProvider provideStatementId
	 */
	public function testGivenStatementSubjectDoesNotExist_throwsUseCaseError( StatementGuid $statementId ): void {
		$metadataRetriever = $this->createStub( StatementSubjectRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestStatementSubjectRevisionMetadataResult::subjectNotFound() );

		try {
			$this->newGetRevisionMetadata( $metadataRetriever )->execute( $statementId );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_SUBJECT_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( "Could not find an entity with the ID: {$statementId->getEntityId()}", $e->getErrorMessage() );
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
			$this->newGetRevisionMetadata( $metadataRetriever )->execute( $statementWithRedirectItem );
			$this->fail( 'this should not be reached' );
		} catch ( ItemRedirect $e ) {
			$this->assertSame( $redirectTarget, $e->getRedirectTargetId() );
		}
	}

	private function newGetRevisionMetadata( StatementSubjectRevisionMetadataRetriever $metadataRetriever
	): GetLatestStatementSubjectRevisionMetadata {
		return new GetLatestStatementSubjectRevisionMetadata( $metadataRetriever );
	}

	public function provideStatementId(): Generator {
		yield 'statement ID on an item' => [
			new StatementGuid(
				new ItemId( 'Q123' ),
				'D4FDE516-F20C-4154-ADCE-7C5B609DFDFF'
			),
		];
		yield 'statement ID on a property' => [
			new StatementGuid(
				new NumericPropertyId( 'P123' ),
				'D8404CDA-25E4-4334-AF13-A3290BCD9C0N'
			),
		];
	}

}
