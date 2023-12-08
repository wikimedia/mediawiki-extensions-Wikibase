<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyLabel;

use OutOfBoundsException;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\LabelEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;

/**
 * @license GPL-2.0-or-later
 */
class RemovePropertyLabel {

	private RemovePropertyLabelValidator $requestValidator;
	private AssertPropertyExists $assertPropertyExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	public function __construct(
		RemovePropertyLabelValidator $requestValidator,
		AssertPropertyExists $assertPropertyExists,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		PropertyRetriever $propertyRetriever,
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
	public function execute( RemovePropertyLabelRequest $request ): void {
		$deserializedRequest = $this->requestValidator->validateAndDeserialize( $request );
		$propertyId = $deserializedRequest->getPropertyId();
		$languageCode = $deserializedRequest->getLanguageCode();
		$providedEditMetadata = $deserializedRequest->getEditMetadata();

		$this->assertPropertyExists->execute( $propertyId );
		$this->assertUserIsAuthorized->execute( $propertyId, $providedEditMetadata->getUser() );

		$property = $this->propertyRetriever->getProperty( $propertyId );

		try {
			$label = $property->getLabels()->getByLanguage( $languageCode );
		} catch ( OutOfBoundsException $e ) {
			throw new UseCaseError(
				UseCaseError::LABEL_NOT_DEFINED,
				"Property with the ID $propertyId does not have a label in the language: $languageCode"
			);
		}

		$property->getLabels()->removeByLanguage( $languageCode );

		$summary = LabelEditSummary::newRemoveSummary( $providedEditMetadata->getComment(), $label );

		$this->propertyUpdater->update(
			$property, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata( $providedEditMetadata->getTags(), $providedEditMetadata->isBot(), $summary )
		);
	}

}
