<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCases\SetPropertyDescription;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyDescription\SetPropertyDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyDescription\SetPropertyDescriptionRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyDescription\SetPropertyDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseException;
use Wikibase\Repo\Domains\Crud\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Model\User;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Description;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyWriteModelRetriever;
use Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess\InMemoryPropertyRepository;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyDescription\SetPropertyDescription
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SetPropertyDescriptionTest extends TestCase {

	private SetPropertyDescriptionValidator $validator;
	private PropertyWriteModelRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;
	private AssertPropertyExists $assertPropertyExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->propertyRetriever = $this->createStub( PropertyWriteModelRetriever::class );
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
	}

	public function testAddDescription(): void {
		$language = 'en';
		$description = 'Hello world again.';
		$propertyId = new NumericPropertyId( 'P123' );
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'add description edit comment';

		$propertyRepo = new InMemoryPropertyRepository();
		$propertyRepo->addProperty( new Property(
			$propertyId,
			null,
			'string'
		) );
		$this->propertyRetriever = $propertyRepo;
		$this->propertyUpdater = $propertyRepo;

		$response = $this->newUseCase()->execute(
			new SetPropertyDescriptionRequest( "$propertyId", $language, $description, $editTags, $isBot, $comment, null )
		);

		$this->assertEquals( new Description( $language, $description ), $response->getDescription() );
		$this->assertSame( $propertyRepo->getLatestRevisionId( $propertyId ), $response->getRevisionId() );
		$this->assertSame( $propertyRepo->getLatestRevisionTimestamp( $propertyId ), $response->getLastModified() );
		$this->assertEquals(
			new EditMetadata(
				$editTags,
				$isBot,
				DescriptionEditSummary::newAddSummary( $comment, new Term( $language, $description ) )
			),
			$propertyRepo->getLatestRevisionEditMetadata( $propertyId )
		);
		$this->assertFalse( $response->wasReplaced() );
	}

	public function testReplaceDescription(): void {
		$language = 'en';
		$newDescription = 'Hello world again.';
		$propertyId = new NumericPropertyId( 'P123' );
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'replace description edit comment';

		$property = Property::newFromType( 'string' );
		$property->setId( $propertyId );
		$property->setDescription( $language, 'Hello world' );

		$propertyRepo = new InMemoryPropertyRepository();
		$propertyRepo->addProperty( $property );
		$this->propertyRetriever = $propertyRepo;
		$this->propertyUpdater = $propertyRepo;

		$response = $this->newUseCase()->execute(
			new SetPropertyDescriptionRequest( "$propertyId", $language, $newDescription, $editTags, $isBot, $comment, null )
		);

		$this->assertEquals( new Description( $language, $newDescription ), $response->getDescription() );
		$this->assertSame( $propertyRepo->getLatestRevisionId( $propertyId ), $response->getRevisionId() );
		$this->assertSame( $propertyRepo->getLatestRevisionTimestamp( $propertyId ), $response->getLastModified() );
		$this->assertEquals(
			new EditMetadata(
				$editTags,
				$isBot,
				DescriptionEditSummary::newReplaceSummary( $comment, new Term( $language, $newDescription ) )
			),
			$propertyRepo->getLatestRevisionEditMetadata( $propertyId )
		);
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

	public function testGivenEditIsUnauthorized_throwsUseCaseError(): void {
		$propertyId = new NumericPropertyId( 'P123' );

		$expectedError = new UseCaseError(
			UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON,
			'You have no permission to edit this item.'
		);
		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'checkEditPermissions' )
			->with( $propertyId, User::newAnonymous() )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute(
				new SetPropertyDescriptionRequest( "$propertyId", 'en', 'test description', [], false, null, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	private function newUseCase(): SetPropertyDescription {
		return new SetPropertyDescription(
			$this->validator,
			$this->propertyRetriever,
			$this->propertyUpdater,
			$this->assertPropertyExists,
			$this->assertUserIsAuthorized
		);
	}

}
