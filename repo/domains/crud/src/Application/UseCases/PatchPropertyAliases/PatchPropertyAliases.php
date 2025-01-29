<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyAliases;

use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchJson;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UpdateExceptionHandler;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\Model\AliasesEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyAliasesRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class PatchPropertyAliases {

	use UpdateExceptionHandler;

	private PatchPropertyAliasesValidator $validator;
	private AssertPropertyExists $assertPropertyExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private PropertyAliasesRetriever $aliasesRetriever;
	private AliasesSerializer $aliasesSerializer;
	private PatchJson $patchJson;
	private PatchedPropertyAliasesValidator $patchedAliasesValidator;
	private PropertyWriteModelRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	public function __construct(
		PatchPropertyAliasesValidator $validator,
		AssertPropertyExists $assertPropertyExists,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		PropertyAliasesRetriever $aliasesRetriever,
		AliasesSerializer $aliasesSerializer,
		PatchJson $patchJson,
		PatchedPropertyAliasesValidator $patchedAliasesValidator,
		PropertyWriteModelRetriever $propertyRetriever,
		PropertyUpdater $propertyUpdater
	) {
		$this->validator = $validator;
		$this->assertPropertyExists = $assertPropertyExists;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->aliasesRetriever = $aliasesRetriever;
		$this->aliasesSerializer = $aliasesSerializer;
		$this->patchJson = $patchJson;
		$this->patchedAliasesValidator = $patchedAliasesValidator;
		$this->propertyRetriever = $propertyRetriever;
		$this->propertyUpdater = $propertyUpdater;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( PatchPropertyAliasesRequest $request ): PatchPropertyAliasesResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$editMetadata = $deserializedRequest->getEditMetadata();

		$this->assertPropertyExists->execute( $deserializedRequest->getPropertyId() );
		$this->assertUserIsAuthorized->checkEditPermissions( $deserializedRequest->getPropertyId(), $editMetadata->getUser() );

		$patchedAliases = $this->patchedAliasesValidator->validateAndDeserialize( $this->patchJson->execute(
			iterator_to_array( $this->aliasesSerializer->serialize(
				// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
				$this->aliasesRetriever->getAliases( $deserializedRequest->getPropertyId() )
			) ),
			$deserializedRequest->getPatch()
		) );

		$property = $this->propertyRetriever->getPropertyWriteModel( $deserializedRequest->getPropertyId() );
		$originalAliases = $property->getAliasGroups();
		$property->getFingerprint()->setAliasGroups( $patchedAliases );

		$revision = $this->executeWithExceptionHandling( fn() => $this->propertyUpdater->update(
			$property, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata(
				$editMetadata->getTags(),
				$editMetadata->isBot(),
				AliasesEditSummary::newPatchSummary( $editMetadata->getComment(), $originalAliases, $patchedAliases )
			)
		) );

		return new PatchPropertyAliasesResponse(
			$revision->getProperty()->getAliases(),
			$revision->getLastModified(),
			$revision->getRevisionId()
		);
	}

}
