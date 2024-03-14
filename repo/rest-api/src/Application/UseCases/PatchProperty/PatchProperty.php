<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchProperty;

use Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyPartsSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\ConvertArrayObjectsToArray;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\PropertyEditSummary;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyParts;
use Wikibase\Repo\RestApi\Domain\Services\PropertyPartsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;

/**
 * @license GPL-2.0-or-later
 */
class PatchProperty {

	private PatchPropertyValidator $validator;
	private PropertyPartsRetriever $propertyRetriever;
	private PropertyPartsSerializer $propertySerializer;
	private PatchJson $patchJson;
	private PropertyDeserializer $propertyDeserializer;
	private PropertyUpdater $propertyUpdater;

	public function __construct(
		PatchPropertyValidator $validator,
		PropertyPartsRetriever $propertyRetriever,
		PropertyPartsSerializer $propertySerializer,
		PatchJson $patchJson,
		PropertyDeserializer $propertyDeserializer,
		PropertyUpdater $propertyUpdater
	) {
		$this->validator = $validator;
		$this->propertyRetriever = $propertyRetriever;
		$this->propertySerializer = $propertySerializer;
		$this->patchJson = $patchJson;
		$this->propertyDeserializer = $propertyDeserializer;
		$this->propertyUpdater = $propertyUpdater;
	}

	public function execute( PatchPropertyRequest $request ): PatchPropertyResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$providedMetadata = $deserializedRequest->getEditMetadata();

		$patchedProperty = $this->propertyDeserializer->deserialize(
			$this->patchJson->execute(
				ConvertArrayObjectsToArray::execute(
					$this->propertySerializer->serialize(
						// TODO: create a (read model) property retriever instead of using property parts?
						// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
						$this->propertyRetriever->getPropertyParts(
							$deserializedRequest->getPropertyId(),
							PropertyParts::VALID_FIELDS
						)
					)
				),
				$deserializedRequest->getPatch()
			),
		);

		$propertyRevision = $this->propertyUpdater->update(
			$patchedProperty,
			new EditMetadata(
				$providedMetadata->getTags(),
				$providedMetadata->isBot(),
				PropertyEditSummary::newPatchSummary( $providedMetadata->getComment() )
			)
		);

		return new PatchPropertyResponse(
			$propertyRevision->getProperty(),
			$propertyRevision->getLastModified(),
			$propertyRevision->getRevisionId()
		);
	}

}
