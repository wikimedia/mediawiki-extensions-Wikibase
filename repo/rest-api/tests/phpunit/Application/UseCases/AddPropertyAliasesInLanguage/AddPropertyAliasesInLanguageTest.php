<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\AddPropertyAliasesInLanguage;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyAliasesInLanguage\AddPropertyAliasesInLanguage;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyAliasesInLanguage\AddPropertyAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\AliasesInLanguageEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryPropertyRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\AddPropertyAliasesInLanguage\AddPropertyAliasesInLanguage
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AddPropertyAliasesInLanguageTest extends TestCase {

	private AssertPropertyExists $assertPropertyExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
	}

	public function testCreateAliases(): void {
		$languageCode = 'en';
		$propertyId = new NumericPropertyId( 'P123' );
		$aliasesToCreate = [ 'alias 1', 'alias 2' ];
		$editTags = [ TestValidatingRequestDeserializer::ALLOWED_TAGS[0] ];
		$isBot = false;
		$comment = 'potato';

		$propertyRepo = new InMemoryPropertyRepository();
		$propertyRepo->addProperty( new Property( $propertyId, null, 'string' ) );
		$this->propertyRetriever = $propertyRepo;
		$this->propertyUpdater = $propertyRepo;

		$response = $this->newUseCase()->execute(
			new AddPropertyAliasesInLanguageRequest(
				"$propertyId",
				$languageCode,
				$aliasesToCreate,
				$editTags,
				$isBot,
				$comment,
				null
			)
		);

		$this->assertEquals( new AliasesInLanguage( $languageCode, $aliasesToCreate ), $response->getAliases() );
		$this->assertFalse( $response->wasAddedToExistingAliasGroup() );
		$this->assertSame( $propertyRepo->getLatestRevisionId( $propertyId ), $response->getRevisionId() );
		$this->assertSame( $propertyRepo->getLatestRevisionTimestamp( $propertyId ), $response->getLastModified() );
		$this->assertEquals(
			new EditMetadata(
				$editTags,
				$isBot,
				AliasesInLanguageEditSummary::newAddSummary( $comment, new AliasGroup( $languageCode, $aliasesToCreate ) )
			),
			$propertyRepo->getLatestRevisionEditMetadata( $propertyId )
		);
	}

	public function testAddToExistingAliases(): void {
		$languageCode = 'en';
		$existingAliases = [ 'alias 1', 'alias 2' ];
		$property = new Property(
			new NumericPropertyId( 'P123' ),
			new Fingerprint( null, null, new AliasGroupList( [ new AliasGroup( $languageCode, $existingAliases ) ] ) ),
			'string'
		);
		$aliasesToAdd = [ 'alias 3', 'alias 4' ];
		$request = $this->newRequest( "{$property->getId()}", $languageCode, $aliasesToAdd );

		$propertyRepo = new InMemoryPropertyRepository();
		$propertyRepo->addProperty( $property );
		$this->propertyRetriever = $propertyRepo;
		$this->propertyUpdater = $propertyRepo;

		$response = $this->newUseCase()->execute( $request );

		$this->assertEquals(
			new AliasesInLanguage( $languageCode, array_merge( $existingAliases, $aliasesToAdd ) ),
			$response->getAliases()
		);
		$this->assertTrue( $response->wasAddedToExistingAliasGroup() );
		$this->assertEquals(
			new EditMetadata(
				[],
				false,
				AliasesInLanguageEditSummary::newAddSummary( null, new AliasGroup( $languageCode, $aliasesToAdd ) )
			),
			$propertyRepo->getLatestRevisionEditMetadata( $property->getId() )
		);
	}

	public function testValidationError_throwsUseCaseError(): void {
		try {
			$this->newUseCase()->execute( $this->newRequest( 'P123', 'en', [ '' ] ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::ALIAS_EMPTY, $e->getErrorCode() );
		}
	}

	public function testGivenPropertyNotFound_throws(): void {
		$expectedError = $this->createStub( UseCaseError::class );
		$this->assertPropertyExists->method( 'execute' )
			->willThrowException( $expectedError );
		try {
			$this->newUseCase()->execute( $this->newRequest( 'P999', 'en', [ 'new alias' ] ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	public function testGivenUserUnauthorized_throws(): void {
		$expectedException = $this->createStub( UseCaseError::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $this->newRequest( 'P1', 'en', [ 'new alias' ] ) );
			$this->fail( 'expected exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): AddPropertyAliasesInLanguage {
		return new AddPropertyAliasesInLanguage(
			new TestValidatingRequestDeserializer(),
			$this->assertPropertyExists,
			$this->assertUserIsAuthorized,
			$this->propertyRetriever,
			$this->propertyUpdater
		);
	}

	private function newRequest(
		string $propertyId,
		string $languageCode,
		array $aliases,
		array $tags = [],
		bool $isBot = false,
		string $comment = null,
		string $username = null
	): AddPropertyAliasesInLanguageRequest {
		return new AddPropertyAliasesInLanguageRequest( $propertyId, $languageCode, $aliases, $tags, $isBot, $comment, $username );
	}

}
