<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyDescription;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;

/**
 * @license GPL-2.0-or-later
 */
class RemovePropertyDescription {

	private RemovePropertyDescriptionValidator $requestValidator;
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	public function __construct(
		RemovePropertyDescriptionValidator $requestValidator,
		PropertyRetriever $propertyRetriever,
		PropertyUpdater $propertyUpdater
	) {
		$this->requestValidator = $requestValidator;
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

		$property = $this->propertyRetriever->getProperty( $propertyId );
		$description = $property->getDescriptions()->getByLanguage( $languageCode );
		$property->getDescriptions()->removeByLanguage( $languageCode );

		$summary = DescriptionEditSummary::newRemoveSummary( $providedEditMetadata->getComment(), $description );
		$this->propertyUpdater->update(
			$property, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata( $providedEditMetadata->getTags(), $providedEditMetadata->isBot(), $summary )
		);
	}

}
