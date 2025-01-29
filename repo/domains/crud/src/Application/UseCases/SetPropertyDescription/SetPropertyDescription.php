<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyDescription;

use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UpdateExceptionHandler;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class SetPropertyDescription {

	use UpdateExceptionHandler;

	private SetPropertyDescriptionValidator $validator;
	private PropertyWriteModelRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;
	private AssertPropertyExists $assertPropertyExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		SetPropertyDescriptionValidator $validator,
		PropertyWriteModelRetriever $propertyRetriever,
		PropertyUpdater $propertyUpdater,
		AssertPropertyExists $assertPropertyExists,
		AssertUserIsAuthorized $assertUserIsAuthorized
	) {
		$this->validator = $validator;
		$this->propertyRetriever = $propertyRetriever;
		$this->propertyUpdater = $propertyUpdater;
		$this->assertPropertyExists = $assertPropertyExists;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( SetPropertyDescriptionRequest $request ): SetPropertyDescriptionResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$propertyId = $deserializedRequest->getPropertyId();
		$description = $deserializedRequest->getPropertyDescription();
		$editMetadata = $deserializedRequest->getEditMetadata();

		$this->assertPropertyExists->execute( $propertyId );

		$this->assertUserIsAuthorized->checkEditPermissions( $propertyId, $editMetadata->getUser() );

		$property = $this->propertyRetriever->getPropertyWriteModel( $propertyId );
		$descriptionExists = $property->getDescriptions()->hasTermForLanguage( $request->getLanguageCode() );
		$property->getDescriptions()->setTerm( $description );

		$editSummary = $descriptionExists
			? DescriptionEditSummary::newReplaceSummary( $editMetadata->getComment(), $description )
			: DescriptionEditSummary::newAddSummary( $editMetadata->getComment(), $description );

		$revision = $this->executeWithExceptionHandling( fn() => $this->propertyUpdater->update(
			$property, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata( $editMetadata->getTags(), $editMetadata->isBot(), $editSummary )
		) );

		return new SetPropertyDescriptionResponse(
			$revision->getProperty()->getDescriptions()[$description->getLanguageCode()],
			$revision->getLastModified(),
			$revision->getRevisionId(),
			$descriptionExists
		);
	}
}
