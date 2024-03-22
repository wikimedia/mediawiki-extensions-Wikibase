<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchProperty;

use Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertySerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\ConvertArrayObjectsToArray;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\PropertyEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;

/**
 * @license GPL-2.0-or-later
 */
class PatchProperty {

	private PatchPropertyValidator $validator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private PropertyRetriever $propertyRetriever;
	private PropertySerializer $propertySerializer;
	private PatchJson $patchJson;
	private PropertyDeserializer $propertyDeserializer;
	private PropertyUpdater $propertyUpdater;

	public function __construct(
		PatchPropertyValidator $validator,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		PropertyRetriever $propertyRetriever,
		PropertySerializer $propertySerializer,
		PatchJson $patchJson,
		PropertyDeserializer $propertyDeserializer,
		PropertyUpdater $propertyUpdater
	) {
		$this->validator = $validator;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->propertyRetriever = $propertyRetriever;
		$this->propertySerializer = $propertySerializer;
		$this->patchJson = $patchJson;
		$this->propertyDeserializer = $propertyDeserializer;
		$this->propertyUpdater = $propertyUpdater;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( PatchPropertyRequest $request ): PatchPropertyResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$providedMetadata = $deserializedRequest->getEditMetadata();

		$this->assertUserIsAuthorized->checkEditPermissions(
			$deserializedRequest->getPropertyId(),
			$providedMetadata->getUser()
		);

		$patchedProperty = $this->propertyDeserializer->deserialize(
			$this->patchJson->execute(
				ConvertArrayObjectsToArray::execute(
					$this->propertySerializer->serialize(
						// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
						$this->propertyRetriever->getProperty( $deserializedRequest->getPropertyId() )
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
