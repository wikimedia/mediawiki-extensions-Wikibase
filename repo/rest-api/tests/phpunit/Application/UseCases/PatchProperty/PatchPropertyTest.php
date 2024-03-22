<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchProperty;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property as PropertyWriteModel;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertySerializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementsDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\PatchProperty\PatchProperty;
use Wikibase\Repo\RestApi\Application\UseCases\PatchProperty\PatchPropertyRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchProperty\PatchPropertyValidator;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property as PropertyReadModel;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryPropertyRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchProperty\PatchProperty
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchPropertyTest extends TestCase {

	private PatchPropertyValidator $validator;
	private PatchJson $patchJson;
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->patchJson = new PatchJson( new JsonDiffJsonPatcher() );
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
	}

	public function testHappyPath(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'statement replaced by ' . __METHOD__;

		$propertyRepo = new InMemoryPropertyRepository();
		$propertyRepo->addProperty(
			new PropertyWriteModel(
				$propertyId,
				new Fingerprint( new TermList( [ new Term( 'en', 'potato' ), new Term( 'de', 'Kartoffel' ) ] ) ),
				'string',
				null
			)
		);
		$this->propertyRetriever = $this->propertyUpdater = $propertyRepo;

		$response = $this->newUseCase()->execute(
			new PatchPropertyRequest(
				"$propertyId",
				[
					[ 'op' => 'add', 'path' => '/descriptions/en', 'value' => 'staple food' ],
					[ 'op' => 'replace', 'path' => '/labels/en', 'value' => 'Solanum tuberosum' ],
					[ 'op' => 'remove', 'path' => '/labels/de' ],
				],
				$editTags,
				$isBot,
				$comment,
				null
			)
		);

		$this->assertSame( $propertyRepo->getLatestRevisionId( $propertyId ), $response->getRevisionId() );
		$this->assertSame( $propertyRepo->getLatestRevisionTimestamp( $propertyId ), $response->getLastModified() );
		$this->assertEquals(
			new PropertyReadModel(
				$propertyId,
				'string',
				new Labels( new Label( 'en', 'Solanum tuberosum' ) ),
				new Descriptions( new Description( 'en', 'staple food' ) ),
				new Aliases(),
				new StatementList()
			),
			$response->getProperty()
		);
	}

	private function newUseCase(): PatchProperty {
		return new PatchProperty(
			$this->validator,
			$this->propertyRetriever,
			new PropertySerializer(
				new LabelsSerializer(),
				new DescriptionsSerializer(),
				new AliasesSerializer(),
				$this->createStub( StatementListSerializer::class )
			),
			$this->patchJson,
			$this->newPropertyDeserializer(),
			$this->propertyUpdater
		);
	}

	private function newPropertyDeserializer(): PropertyDeserializer {
		$propValPairDeserializer = $this->createStub( PropertyValuePairDeserializer::class );
		$propValPairDeserializer->method( 'deserialize' )->willReturnCallback(
			fn( array $p ) => new PropertySomeValueSnak( new NumericPropertyId( $p[ 'property' ][ 'id' ] ) )
		);

		return new PropertyDeserializer(
			new LabelsDeserializer(),
			new DescriptionsDeserializer(),
			new AliasesDeserializer(),
			new StatementsDeserializer(
				new StatementDeserializer( $propValPairDeserializer, $this->createStub( ReferenceDeserializer::class ) )
			)
		);
	}

}
