<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetPropertyStatements;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatements;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatementsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\PropertyStatementsRetriever;
use Wikibase\Repo\Tests\RestApi\Domain\ReadModel\NewStatementReadModel;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatements
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyStatementsTest extends TestCase {

	/**
	 * @var Stub|GetLatestPropertyRevisionMetadata
	 */
	private $getRevisionMetadata;

	/**
	 * @var Stub|PropertyStatementsRetriever
	 */
	private $statementsRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ 123, '20230111070707' ] );
		$this->statementsRetriever = $this->createStub( PropertyStatementsRetriever::class );
	}

	public function testGetPropertyStatements(): void {
		$propertyId = new NumericPropertyId( 'P42' );
		$revisionId = 987;
		$lastModified = '20201111070707';
		$statements = new StatementList(
			NewStatementReadModel::forProperty( 'P123' )
				->withValue( 'potato' )
				->withGuid( 'P42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
				->build(),
			NewStatementReadModel::someValueFor( 'P321' )
				->withGuid( 'P42$BBBBBBBB-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
				->build()
		);

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ $revisionId, $lastModified ] );

		$this->statementsRetriever = $this->createMock( PropertyStatementsRetriever::class );
		$this->statementsRetriever->expects( $this->once() )
			->method( 'getStatements' )
			->with( $propertyId )
			->willReturn( $statements );

		$response = $this->newUseCase()->execute(
			new GetPropertyStatementsRequest( $propertyId->getSerialization() )
		);

		$this->assertSame( $statements, $response->getStatements() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
		$this->assertSame( $lastModified, $response->getLastModified() );
	}

	public function testGivenFilterPropertyId_retrievesOnlyRequestedStatements(): void {
		$subjectPropertyId = new NumericPropertyId( 'P123' );
		$filterPropertyId = new NumericPropertyId( 'P111' );

		$expectedStatements = $this->createStub( StatementList::class );
		$this->statementsRetriever = $this->createMock( PropertyStatementsRetriever::class );
		$this->statementsRetriever->expects( $this->once() )
			->method( 'getStatements' )
			->with( $subjectPropertyId, $filterPropertyId )
			->willReturn( $expectedStatements );

		$response = $this->newUseCase()->execute(
			new GetPropertyStatementsRequest( $subjectPropertyId->getSerialization(), $filterPropertyId->getSerialization() )
		);

		$this->assertSame( $expectedStatements, $response->getStatements() );
	}

	public function testGivenPropertyNotFound_throws(): void {
		$expectedException = $this->createStub( UseCaseException::class );

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new GetPropertyStatementsRequest( 'P123' ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): GetPropertyStatements {
		return new GetPropertyStatements(
			$this->statementsRetriever,
			$this->getRevisionMetadata
		);
	}

}
