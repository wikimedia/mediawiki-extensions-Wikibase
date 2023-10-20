<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels;

use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
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
	private PatchPropertyLabelsValidator $useCaseValidator;
	private AssertPropertyExists $assertPropertyExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		PropertyLabelsRetriever $labelsRetriever,
		LabelsSerializer $labelsSerializer,
		JsonPatcher $patcher,
		LabelsDeserializer $labelsDeserializer,
		PropertyRetriever $propertyRetriever,
		PropertyUpdater $propertyUpdater,
		PatchPropertyLabelsValidator $useCaseValidator,
		AssertPropertyExists $assertPropertyExists,
		AssertUserIsAuthorized $assertUserIsAuthorized
	) {
		$this->labelsRetriever = $labelsRetriever;
		$this->labelsSerializer = $labelsSerializer;
		$this->patcher = $patcher;
		$this->labelsDeserializer = $labelsDeserializer;
		$this->propertyRetriever = $propertyRetriever;
		$this->propertyUpdater = $propertyUpdater;
		$this->useCaseValidator = $useCaseValidator;
		$this->assertPropertyExists = $assertPropertyExists;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( PatchPropertyLabelsRequest $request ): PatchPropertyLabelsResponse {
		$deserializedRequest = $this->useCaseValidator->validateAndDeserialize( $request );
		$propertyId = $deserializedRequest->getPropertyId();

		$this->assertPropertyExists->execute( $propertyId );

		$this->assertUserIsAuthorized->execute(
			$deserializedRequest->getPropertyId(),
			$deserializedRequest->getEditMetadata()->getUser()->getUsername()
		);

		$property = $this->propertyRetriever->getProperty( $propertyId );
		$originalLabels = $property->getLabels();

		$labels = $this->labelsRetriever->getLabels( $propertyId );
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$serialization = $this->labelsSerializer->serialize( $labels );

		$modifiedLabels = $this->patcher->patch( iterator_to_array( $serialization ), $deserializedRequest->getPatch() );
		$modifiedLabelsAsTermList = $this->labelsDeserializer->deserialize( $modifiedLabels );

		$property->getFingerprint()->setLabels( $modifiedLabelsAsTermList );

		$editMetadata = new EditMetadata(
			$deserializedRequest->getEditMetadata()->getTags(),
			$deserializedRequest->getEditMetadata()->isBot(),
			LabelsEditSummary::newPatchSummary(
				$deserializedRequest->getEditMetadata()->getComment(),
				$originalLabels,
				$modifiedLabelsAsTermList
			)
		);

		$revision = $this->propertyUpdater->update(
			$property, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			$editMetadata
		);

		return new PatchPropertyLabelsResponse(
			$revision->getProperty()->getLabels(),
			$revision->getLastModified(),
			$revision->getRevisionId()
		);
	}

}
