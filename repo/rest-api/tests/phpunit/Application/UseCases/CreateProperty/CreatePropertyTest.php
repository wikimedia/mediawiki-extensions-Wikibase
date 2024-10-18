<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\CreateProperty;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesInLanguageDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\CreateProperty\CreateProperty;
use Wikibase\Repo\RestApi\Application\UseCases\CreateProperty\CreatePropertyRequest;
use Wikibase\Repo\RestApi\Application\UseCases\CreateProperty\CreatePropertyValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\CreatePropertyEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\PropertyCreator;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryPropertyRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\CreateProperty\CreateProperty
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CreatePropertyTest extends TestCase {

	private PropertyCreator $propertyCreator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	protected function setUp(): void {
		parent::setUp();

		$this->propertyCreator = new InMemoryPropertyRepository();
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
	}

	public function testHappyPath(): void {
		$propertySerialization = [ 'data_type' => 'string' ];
		$editTags = [ TestValidatingRequestDeserializer::ALLOWED_TAGS[0] ];
		$isBot = false;
		$comment = 'comment';

		$propertyRepo = new InMemoryPropertyRepository();
		$this->propertyCreator = $propertyRepo;

		$response = $this->newUseCase()->execute(
			new CreatePropertyRequest(
				$propertySerialization,
				$editTags,
				$isBot,
				$comment,
				null
			)
		);

		$newProperty = $response->getProperty();
		$newPropertyId = $newProperty->getId();

		$this->assertEquals(
			new Property( $newPropertyId, new Fingerprint(), 'string', null ),
			$propertyRepo->getPropertyWriteModel( $newPropertyId )
		);
		$this->assertEquals( $propertyRepo->getProperty( $newPropertyId ), $newProperty );
		$this->assertSame( $propertyRepo->getLatestRevisionId( $newPropertyId ), $response->getRevisionId() );
		$this->assertSame( $propertyRepo->getLatestRevisionTimestamp( $newPropertyId ), $response->getLastModified() );
		$this->assertEquals(
			new EditMetadata( $editTags, $isBot, CreatePropertyEditSummary::newSummary( $comment ) ),
			$propertyRepo->getLatestRevisionEditMetadata( $newPropertyId )
		);
	}

	public function testGivenUserUnauthorized_throws(): void {
		$expectedException = $this->createStub( UseCaseError::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'checkCreatePropertyPermissions' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new CreatePropertyRequest( [ 'data_type' => 'string' ], [], false, null, null ) );
			$this->fail( 'expected exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): CreateProperty {
		$propValPairDeserializer = $this->createStub( PropertyValuePairDeserializer::class );
		$propValPairDeserializer->method( 'deserialize' )->willReturnCallback(
			fn( array $p ) => new PropertySomeValueSnak( new NumericPropertyId( $p[ 'property' ][ 'id' ] ) )
		);

		return new CreateProperty(
			new CreatePropertyValidator( new PropertyDeserializer(
				new LabelsDeserializer(),
				new DescriptionsDeserializer(),
				new AliasesDeserializer( new AliasesInLanguageDeserializer() ),
				new StatementDeserializer( $propValPairDeserializer, $this->createStub( ReferenceDeserializer::class ) )
			) ),
			$this->propertyCreator,
			$this->assertUserIsAuthorized
		);
	}

}
