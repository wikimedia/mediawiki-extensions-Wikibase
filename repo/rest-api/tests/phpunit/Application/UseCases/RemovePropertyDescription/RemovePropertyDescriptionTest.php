<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RemovePropertyDescription;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property as DataModelProperty;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyDescription\RemovePropertyDescription;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyDescription\RemovePropertyDescriptionRequest;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryPropertyRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyDescription\RemovePropertyDescription
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 *
 */
class RemovePropertyDescriptionTest extends TestCase {

	use EditMetadataHelper;

	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
	}

	public function testHappyPath(): void {
		$propertyId = new NumericPropertyId( 'P1' );
		$languageCode = 'en';
		$descriptionToRemove = new Term( $languageCode, 'Description to remove' );
		$descriptionToKeep = new Term( 'fr', 'Description to keep' );
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'test comment';

		$propertyRepo = new InMemoryPropertyRepository();
		$propertyRepo->addProperty(
			new DataModelProperty(
				$propertyId,
				new Fingerprint( null, new TermList( [ $descriptionToRemove, $descriptionToKeep ] ) ),
				'string'
			)
		);
		$this->propertyRetriever = $this->propertyUpdater = $propertyRepo;

		$this->newUseCase()->execute(
			new RemovePropertyDescriptionRequest( "$propertyId", $languageCode, $editTags, $isBot, $comment, null )
		);

		$this->assertEquals(
			new TermList( [ $descriptionToKeep ] ),
			$propertyRepo->getProperty( $propertyId )->getDescriptions()
		);
		$this->assertEquals(
			new EditMetadata(
				$editTags,
				$isBot,
				DescriptionEditSummary::newRemoveSummary( $comment, $descriptionToRemove )
			),
			$propertyRepo->getLatestRevisionEditMetadata( $propertyId )
		);
	}

	private function newUseCase(): RemovePropertyDescription {
		return new RemovePropertyDescription( $this->propertyRetriever, $this->propertyUpdater );
	}

}
