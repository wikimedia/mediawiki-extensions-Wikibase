<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\AddPropertyStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property as DataModelProperty;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;
use Wikibase\Repo\Tests\RestApi\Domain\ReadModel\NewStatementReadModel;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AddPropertyStatementTest extends TestCase {

	use EditMetadataHelper;

	private AssertPropertyExists $assertPropertyExists;
	private AddPropertyStatementValidator $validator;
	private PropertyRetriever $propertyRetriever;
	private GuidGenerator $guidGenerator;
	private PropertyUpdater $propertyUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->validator = $this->createStub( AddPropertyStatementValidator::class );
		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->guidGenerator = new GuidGenerator();
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
	}

	public function testAddStatement(): void {
		$id = new NumericPropertyId( 'P321' );
		$newGuid = new StatementGuid( $id, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = 'edit comment';
		$statementWriteModel = NewStatement::noValueFor( 'P123' )->build();
		$statementReadModel = NewStatementReadModel::noValueFor( 'P123' )
			->withGuid( $newGuid )
			->build();

		$property = new DataModelProperty( $id, null, 'string' );

		$this->validator = $this->createStub( AddPropertyStatementValidator::class );
		$this->validator->method( 'getValidatedStatement' )->willReturn( $statementWriteModel );

		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyRetriever->method( 'getProperty' )->willReturn( $property );

		$this->guidGenerator = $this->createStub( GuidGenerator::class );
		$this->guidGenerator->method( 'newStatementId' )->willReturn( $newGuid );

		$this->propertyUpdater = $this->createMock( PropertyUpdater::class );
		$this->propertyUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->callback(
					fn( DataModelProperty $p ) => $p->getStatements()->getFirstStatementWithGuid( (string)$newGuid ) !== null
				),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::ADD_ACTION )
			)
			->willReturn( new PropertyRevision( new Property( new StatementList( $statementReadModel ) ), '', 321 ) );

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

		$this->assertSame( $statementReadModel, $response->getStatement() );
	}

	public function testGivenPropertyNotFound_throws(): void {
		$expectedException = $this->createStub( UseCaseError::class );

		$this->assertPropertyExists->method( 'execute' )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new AddPropertyStatementRequest(
					'P99999',
					$this->getValidNoValueStatementSerialization(),
					[],
					false,
					null,
					null
				)
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
			$this->propertyUpdater
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
