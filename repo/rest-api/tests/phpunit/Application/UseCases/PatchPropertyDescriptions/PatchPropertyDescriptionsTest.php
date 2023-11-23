<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchPropertyDescriptions;

use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property as DataModelProperty;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyDescriptions\PatchPropertyDescriptions;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyDescriptions\PatchPropertyDescriptionsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyDescriptions\PatchPropertyDescriptionsValidator;
use Wikibase\Repo\RestApi\Domain\Model\LabelsEditSummary;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\PropertyDescriptionsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyDescriptions\PatchPropertyDescriptions
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchPropertyDescriptionsTest extends TestCase {

	use EditMetadataHelper;

	private PatchPropertyDescriptionsValidator $validator;
	private DescriptionsSerializer $descriptionsSerializer;
	private PropertyDescriptionsRetriever $descriptionsRetriever;
	private PatchJson $patcher;
	private PropertyRetriever $propertyRetriever;
	private DescriptionsDeserializer $descriptionsDeserializer;
	private PropertyUpdater $propertyUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->descriptionsRetriever = $this->createStub( PropertyDescriptionsRetriever::class );
		$this->descriptionsSerializer = new DescriptionsSerializer();
		$this->patcher = new PatchJson( new JsonDiffJsonPatcher() );
		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->descriptionsDeserializer = new DescriptionsDeserializer();
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
	}

	public function testHappyPath(): void {
		$propertyId = new NumericPropertyId( 'P31' );
		$property = new DataModelProperty( $propertyId, null, 'string' );

		$newDescriptionText = 'وصف عربي جديد';
		$newDescriptionLanguage = 'ar';

		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyRetriever->method( 'getProperty' )->willReturn( $property );

		$revisionId = 657;
		$lastModified = '20221212040506';

		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'descriptions patched by ' . __method__;

		$updatedProperty = new Property(
			new Labels(),
			new Descriptions( new Description( $newDescriptionLanguage, $newDescriptionText ) ),
			new Aliases(),
			new StatementList()
		);

		$this->descriptionsRetriever = $this->createStub( PropertyDescriptionsRetriever::class );
		$this->descriptionsRetriever->method( 'getDescriptions' )->willReturn( new Descriptions() );

		$this->propertyUpdater = $this->createMock( PropertyUpdater::class );
		$this->propertyUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->expectPropertyWithDescription( $newDescriptionLanguage, $newDescriptionText ),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, LabelsEditSummary::PATCH_ACTION )
			)
			->willReturn( new PropertyRevision( $updatedProperty, $lastModified, $revisionId ) );

		$response = $this->newUseCase()->execute(
			new PatchPropertyDescriptionsRequest(
				"$propertyId",
				[ [ 'op' => 'add', 'path' => "/$newDescriptionLanguage", 'value' => $newDescriptionText ] ],
				$editTags,
				$isBot,
				$comment,
				null
			)
		);

		$this->assertSame( $response->getDescriptions(), $updatedProperty->getDescriptions() );
		$this->assertSame( $lastModified, $response->getLastModified() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
	}

	private function newUseCase(): PatchPropertyDescriptions {
		return new PatchPropertyDescriptions(
			$this->validator,
			$this->descriptionsRetriever,
			$this->descriptionsSerializer,
			$this->patcher,
			$this->propertyRetriever,
			$this->descriptionsDeserializer,
			$this->propertyUpdater
		);
	}

	private function expectPropertyWithDescription( string $languageCode, string $description ): Callback {
		return $this->callback(
			fn( DataModelProperty $property ) => $property->getDescriptions()->getByLanguage( $languageCode )->getText() === $description
		);
	}

}
