<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyDescriptions;

use Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchJson;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UpdateExceptionHandler;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\Model\DescriptionsEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyDescriptionsRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class PatchPropertyDescriptions {

	use UpdateExceptionHandler;

	private PatchPropertyDescriptionsValidator $useCaseValidator;
	private AssertPropertyExists $assertPropertyExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private PropertyDescriptionsRetriever $descriptionsRetriever;
	private DescriptionsSerializer $descriptionsSerializer;
	private PatchJson $patcher;
	private PropertyWriteModelRetriever $propertyRetriever;
	private PatchedPropertyDescriptionsValidator $patchedDescriptionsValidator;
	private PropertyUpdater $propertyUpdater;

	public function __construct(
		PatchPropertyDescriptionsValidator $useCaseValidator,
		AssertPropertyExists $assertPropertyExists,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		PropertyDescriptionsRetriever $descriptionsRetriever,
		DescriptionsSerializer $descriptionsSerializer,
		PatchJson $patcher,
		PropertyWriteModelRetriever $propertyRetriever,
		PatchedPropertyDescriptionsValidator $patchedDescriptionsValidator,
		PropertyUpdater $propertyUpdater
	) {
		$this->useCaseValidator = $useCaseValidator;
		$this->assertPropertyExists = $assertPropertyExists;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->descriptionsRetriever = $descriptionsRetriever;
		$this->descriptionsSerializer = $descriptionsSerializer;
		$this->patcher = $patcher;
		$this->propertyRetriever = $propertyRetriever;
		$this->patchedDescriptionsValidator = $patchedDescriptionsValidator;
		$this->propertyUpdater = $propertyUpdater;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( PatchPropertyDescriptionsRequest $request ): PatchPropertyDescriptionsResponse {
		$deserializedRequest = $this->useCaseValidator->validateAndDeserialize( $request );
		$propertyId = $deserializedRequest->getPropertyId();

		$this->assertPropertyExists->execute( $propertyId );

		$this->assertUserIsAuthorized->checkEditPermissions( $propertyId, $deserializedRequest->getEditMetadata()->getUser() );

		$modifiedDescriptions = $this->patcher->execute(
			iterator_to_array(
				// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
				$this->descriptionsSerializer->serialize( $this->descriptionsRetriever->getDescriptions( $propertyId ) )
			),
			$deserializedRequest->getPatch()
		);

		$property = $this->propertyRetriever->getPropertyWriteModel( $propertyId );
		$originalDescriptions = $property->getDescriptions();

		$modifiedDescriptionsAsTermList = $this->patchedDescriptionsValidator->validateAndDeserialize(
			$originalDescriptions,
			$property->getLabels(),
			$modifiedDescriptions
		);
		$property->getFingerprint()->setDescriptions( $modifiedDescriptionsAsTermList );

		$editMetadata = new EditMetadata(
			$deserializedRequest->getEditMetadata()->getTags(),
			$deserializedRequest->getEditMetadata()->isBot(),
			DescriptionsEditSummary::newPatchSummary(
				$deserializedRequest->getEditMetadata()->getComment(),
				$originalDescriptions,
				$modifiedDescriptionsAsTermList
			)
		);

		$revision = $this->executeWithExceptionHandling( fn() => $this->propertyUpdater->update(
			$property, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			$editMetadata
		) );

		return new PatchPropertyDescriptionsResponse(
			$revision->getProperty()->getDescriptions(),
			$revision->getLastModified(),
			$revision->getRevisionId()
		);
	}

}
