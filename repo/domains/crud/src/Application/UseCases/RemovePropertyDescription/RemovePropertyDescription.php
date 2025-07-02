<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyDescription;

use OutOfBoundsException;
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
class RemovePropertyDescription {
	use UpdateExceptionHandler;

	private RemovePropertyDescriptionValidator $requestValidator;
	private AssertPropertyExists $assertPropertyExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private PropertyWriteModelRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	public function __construct(
		RemovePropertyDescriptionValidator $requestValidator,
		AssertPropertyExists $assertPropertyExists,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		PropertyWriteModelRetriever $propertyRetriever,
		PropertyUpdater $propertyUpdater
	) {
		$this->requestValidator = $requestValidator;
		$this->assertPropertyExists = $assertPropertyExists;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->propertyRetriever = $propertyRetriever;
		$this->propertyUpdater = $propertyUpdater;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( RemovePropertyDescriptionRequest $request ): void {
		$deserializedRequest = $this->requestValidator->validateAndDeserialize( $request );

		$propertyId = $deserializedRequest->getPropertyId();
		$languageCode = $deserializedRequest->getLanguageCode();
		$providedEditMetadata = $deserializedRequest->getEditMetadata();

		$this->assertPropertyExists->execute( $propertyId );
		$this->assertUserIsAuthorized->checkEditPermissions( $propertyId, $providedEditMetadata->getUser() );

		$property = $this->propertyRetriever->getPropertyWriteModel( $propertyId );

		try {
			$description = $property->getDescriptions()->getByLanguage( $languageCode );
		} catch ( OutOfBoundsException ) {
			throw UseCaseError::newResourceNotFound( 'description' );
		}

		$property->getDescriptions()->removeByLanguage( $languageCode );

		$summary = DescriptionEditSummary::newRemoveSummary( $providedEditMetadata->getComment(), $description );
		$this->executeWithExceptionHandling( fn() => $this->propertyUpdater->update(
			$property, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata( $providedEditMetadata->getTags(), $providedEditMetadata->isBot(), $summary )
		) );
	}

}
