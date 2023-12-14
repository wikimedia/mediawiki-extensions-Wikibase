<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\AddPropertyStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property as DataModelProperty;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Domain\ReadModel\NewStatementReadModel;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryPropertyRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AddPropertyStatementTest extends TestCase {

	private AssertPropertyExists $assertPropertyExists;
	private AddPropertyStatementValidator $validator;
	private PropertyRetriever $propertyRetriever;
	private GuidGenerator $guidGenerator;
	private PropertyUpdater $propertyUpdater;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	protected function setUp(): void {
		parent::setUp();

		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->validator = new TestValidatingRequestDeserializer();
		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->guidGenerator = new GuidGenerator();
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
	}

	public function testAddStatement(): void {
		$id = new NumericPropertyId( 'P321' );
		$property = new DataModelProperty( $id, null, 'string' );
		$newGuid = new StatementGuid( $id, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'edit comment';
		[ $statementReadModel, $statementWriteModel ] = NewStatementReadModel::noValueFor( 'P123' )
			->withGuid( $newGuid )
			->buildReadAndWriteModel();

		$this->guidGenerator = $this->createStub( GuidGenerator::class );
		$this->guidGenerator->method( 'newStatementId' )->willReturn( $newGuid );

		$propertyRepo = new InMemoryPropertyRepository();
		$propertyRepo->addProperty( $property );
		$this->propertyRetriever = $propertyRepo;
		$this->propertyUpdater = $propertyRepo;

		$response = $this->newUseCase()->execute(
			new AddPropertyStatementRequest(
				"$id",
				$this->getValidNoValueStatementSerialization(),
				$editTags,
				$isBot,
				$comment,
				null
			)
		);

		$this->assertEquals( $statementReadModel, $response->getStatement() );
		$this->assertSame( $propertyRepo->getLatestRevisionTimestamp( $id ), $response->getLastModified() );
		$this->assertSame( $propertyRepo->getLatestRevisionId( $id ), $response->getRevisionId() );
		$this->assertEquals(
			new EditMetadata( $editTags, $isBot, StatementEditSummary::newAddSummary( $comment, $statementWriteModel ) ),
			$propertyRepo->getLatestRevisionEditMetadata( $id )
		);
	}

	public function testGivenInvalidRequest_throwsUseCaseError(): void {
		$expectedException = $this->createStub( UseCaseError::class );
		$this->validator = $this->createStub( AddPropertyStatementValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $this->createStub( AddPropertyStatementRequest::class ) );
			$this->fail( 'Exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenPropertyNotFound_throws(): void {
		$expectedException = $this->createStub( UseCaseError::class );

		$this->assertPropertyExists->method( 'execute' )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				$this->newRequest( [ 'id' => 'P999999' ] )
			);
			$this->fail( 'Expected exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenUnauthorizedRequest_throws(): void {
		$expectedException = $this->createStub( UseCaseError::class );

		$this->assertUserIsAuthorized->method( 'execute' )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				$this->newRequest( [ 'id' => 'P321' ] )
			);
			$this->fail( 'Expected exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): AddPropertyStatement {
		return new AddPropertyStatement(
			$this->validator,
			$this->assertPropertyExists,
			$this->propertyRetriever,
			$this->guidGenerator,
			$this->propertyUpdater,
			$this->assertUserIsAuthorized
		);
	}

	private function newRequest( array $req ): AddPropertyStatementRequest {
		return new AddPropertyStatementRequest(
			$req['id'],
			$req['statement'] ?? $this->getValidNoValueStatementSerialization(),
			$req['tags'] ?? [],
			$req['bot'] ?? false,
			$req['comment'] ?? null,
			$req['user'] ?? null
		);
	}

	private function getValidNoValueStatementSerialization(): array {
		return [
			'property' => [
				'id' => 'P123',
			],
			'value' => [
				'type' => 'novalue',
			],
		];
	}

}
