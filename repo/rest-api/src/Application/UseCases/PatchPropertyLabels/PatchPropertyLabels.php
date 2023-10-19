<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\LabelsEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\JsonPatcher;
use Wikibase\Repo\RestApi\Domain\Services\PropertyLabelsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;

/**
 * @license GPL-2.0-or-later
 */
class PatchPropertyLabels {

	private PropertyLabelsRetriever $labelsRetriever;
	private LabelsSerializer $labelsSerializer;
	private JsonPatcher $patcher;
	private LabelsDeserializer $labelsDeserializer;
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	public function __construct(
		PropertyLabelsRetriever $labelsRetriever,
		LabelsSerializer $labelsSerializer,
		JsonPatcher $patcher,
		LabelsDeserializer $labelsDeserializer,
		PropertyRetriever $propertyRetriever,
		PropertyUpdater $propertyUpdater
	) {
		$this->labelsRetriever = $labelsRetriever;
		$this->labelsSerializer = $labelsSerializer;
		$this->patcher = $patcher;
		$this->labelsDeserializer = $labelsDeserializer;
		$this->propertyRetriever = $propertyRetriever;
		$this->propertyUpdater = $propertyUpdater;
	}

	public function execute( PatchPropertyLabelsRequest $request ): PatchPropertyLabelsResponse {
		$propertyId = new NumericPropertyId( $request->getPropertyId() );
		$property = $this->propertyRetriever->getProperty( $propertyId );
		$originalLabels = $property->getLabels();

		$labels = $this->labelsRetriever->getLabels( $propertyId );
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$serialization = $this->labelsSerializer->serialize( $labels );

		$modifiedLabels = $this->patcher->patch( iterator_to_array( $serialization ), $request->getPatch() );
		$modifiedLabelsAsTermList = $this->labelsDeserializer->deserialize( $modifiedLabels );

		$property->getFingerprint()->setLabels( $modifiedLabelsAsTermList );

		$revision = $this->propertyUpdater->update(
			$property, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata(
				[],
				false,
				LabelsEditSummary::newPatchSummary( '', $originalLabels, $modifiedLabelsAsTermList )
			)
		);

		return new PatchPropertyLabelsResponse(
			$revision->getProperty()->getLabels(),
			$revision->getLastModified(),
			$revision->getRevisionId()
		);
	}

}
