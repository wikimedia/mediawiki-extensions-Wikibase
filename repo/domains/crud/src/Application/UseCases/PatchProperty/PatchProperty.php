<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchProperty;

use Wikibase\Repo\Domains\Crud\Application\Serialization\PropertySerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ConvertArrayObjectsToArray;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchJson;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UpdateExceptionHandler;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Model\PatchPropertyEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class PatchProperty {

	use UpdateExceptionHandler;

	private PatchPropertyValidator $validator;
	private AssertPropertyExists $assertPropertyExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private PropertyRetriever $propertyRetriever;
	private PropertySerializer $propertySerializer;
	private PatchJson $patchJson;
	private PropertyUpdater $propertyUpdater;
	private PropertyWriteModelRetriever $propertyRetrieverWriteModel;
	private PatchedPropertyValidator $patchedPropertyValidator;

	public function __construct(
		PatchPropertyValidator $validator,
		AssertPropertyExists $assertPropertyExists,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		PropertyRetriever $propertyRetriever,
		PropertySerializer $propertySerializer,
		PatchJson $patchJson,
		PropertyUpdater $propertyUpdater,
		PropertyWriteModelRetriever $propertyRetrieverWriteModel,
		PatchedPropertyValidator $patchedPropertyValidator
	) {
		$this->validator = $validator;
		$this->assertPropertyExists = $assertPropertyExists;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->propertyRetriever = $propertyRetriever;
		$this->propertySerializer = $propertySerializer;
		$this->patchJson = $patchJson;
		$this->propertyUpdater = $propertyUpdater;
		$this->propertyRetrieverWriteModel = $propertyRetrieverWriteModel;
		$this->patchedPropertyValidator = $patchedPropertyValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( PatchPropertyRequest $request ): PatchPropertyResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$propertyId = $deserializedRequest->getPropertyId();
		$providedMetadata = $deserializedRequest->getEditMetadata();

		$this->assertPropertyExists->execute( $propertyId );
		$this->assertUserIsAuthorized->checkEditPermissions( $propertyId, $providedMetadata->getUser() );

		$originalSerialization = ConvertArrayObjectsToArray::execute(
			$this->propertySerializer->serialize(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
				$this->propertyRetriever->getProperty( $propertyId )
			)
		);
		$patchedPropertySerialization = $this->patchJson->execute( $originalSerialization, $deserializedRequest->getPatch() );

		$originalProperty = $this->propertyRetrieverWriteModel->getPropertyWriteModel( $propertyId );
		$patchedProperty = $this->patchedPropertyValidator->validateAndDeserialize(
			$patchedPropertySerialization,
			$originalProperty, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			$originalSerialization
		);

		$propertyRevision = $this->executeWithExceptionHandling( fn() => $this->propertyUpdater->update(
			$patchedProperty,
			new EditMetadata(
				$providedMetadata->getTags(),
				$providedMetadata->isBot(),
				// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
					PatchPropertyEditSummary::newSummary( $providedMetadata->getComment(), $originalProperty, $patchedProperty )
			)
		) );

		return new PatchPropertyResponse(
			$propertyRevision->getProperty(),
			$propertyRevision->getLastModified(),
			$propertyRevision->getRevisionId()
		);
	}

}
