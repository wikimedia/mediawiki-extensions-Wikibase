<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\SetPropertyDescription;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\Property as DataModelProperty;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\SetPropertyDescription\SetPropertyDescription;
use Wikibase\Repo\RestApi\Application\UseCases\SetPropertyDescription\SetPropertyDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetPropertyDescription\SetPropertyDescriptionValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property as ReadModelProperty;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\SetPropertyDescription\SetPropertyDescription
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SetPropertyDescriptionTest extends TestCase {

	use EditMetadataHelper;

	private SetPropertyDescriptionValidator $validator;
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;
	private AssertPropertyExists $assertPropertyExists;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
	}

	public function testAddDescription(): void {
		$language = 'en';
		$description = 'Hello world again.';
		$propertyId = 'P123';
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'add description edit comment';

		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyRetriever->method( 'getProperty' )->willReturn( new DataModelProperty( null, null, 'string' ) );

		$updatedProperty = new ReadModelProperty(
			new Labels(),
			new Descriptions( new Description( $language, $description ) ),
			new Aliases(),
			new StatementList()
		);
		$revisionId = 123;
		$lastModified = '20221212040506';

		$this->propertyUpdater = $this->createMock( PropertyUpdater::class );
		$this->propertyUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->callback(
					fn( DataModelProperty $property ) => $property->getDescriptions()->toTextArray() === [ $language => $description ]
				),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::ADD_ACTION )
			)
			->willReturn( new PropertyRevision( $updatedProperty, $lastModified, $revisionId ) );

		$response = $this->newUseCase()->execute(
			new SetPropertyDescriptionRequest( $propertyId, $language, $description, $editTags, $isBot, $comment, null )
		);

		$this->assertEquals( new Description( $language, $description ), $response->getDescription() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
		$this->assertSame( $lastModified, $response->getLastModified() );
		$this->assertFalse( $response->wasReplaced() );
	}

	public function testReplaceDescription(): void {
		$language = 'en';
		$newDescription = 'Hello world again.';
		$propertyId = 'P123';
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;

		$property = Property::newFromType( 'string' );
		$property->setId( new NumericPropertyId( $propertyId ) );
		$property->setDescription( $language, 'Hello world' );

		$comment = 'replace description edit comment';

		$this->propertyRetriever = $this->createMock( PropertyRetriever::class );
		$this->propertyRetriever
			->expects( $this->once() )
			->method( 'getProperty' )
			->with( $propertyId )
			->willReturn( $property );

		$updatedProperty = new ReadModelProperty(
			new Labels(),
			new Descriptions( new Description( $language, $newDescription ) ),
			new Aliases(),
			new StatementList()
		);
		$revisionId = 123;
		$lastModified = '20221212040506';

		$this->propertyUpdater = $this->createMock( PropertyUpdater::class );
		$this->propertyUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->callback(
					fn( DataModelProperty $property ) => $property->getDescriptions()->toTextArray() === [ $language => $newDescription ]
				),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::REPLACE_ACTION )
			)
			->willReturn( new PropertyRevision( $updatedProperty, $lastModified, $revisionId ) );

		$response = $this->newUseCase()->execute(
			new SetPropertyDescriptionRequest( $propertyId, $language, $newDescription, $editTags, $isBot, $comment, null )
		);

		$this->assertEquals( new Description( $language, $newDescription ), $response->getDescription() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
		$this->assertSame( $lastModified, $response->getLastModified() );
		$this->assertTrue( $response->wasReplaced() );
	}

	public function testGivenInvalidRequest_throwsUseCaseException(): void {
		$expectedException = new UseCaseException( 'invalid-description-test' );
		$this->validator = $this->createStub( SetPropertyDescriptionValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute(
				new SetPropertyDescriptionRequest( 'P123', 'en', 'description', [], false, null, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenPropertyNotFound_throws(): void {
		$expectedException = $this->createStub( UseCaseException::class );
		$this->assertPropertyExists->method( 'execute' )
			->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute( new SetPropertyDescriptionRequest( 'P999', 'en', 'test description', [], false, null, null ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): SetPropertyDescription {
		return new SetPropertyDescription(
			$this->validator,
			$this->propertyRetriever,
			$this->propertyUpdater,
			$this->assertPropertyExists
		);
	}

}
