<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyLabel;

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
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	public function __construct(
		RemovePropertyLabelValidator $requestValidator,
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
	public function execute( RemovePropertyLabelRequest $request ): void {
		$deserializedRequest = $this->requestValidator->validateAndDeserialize( $request );
		$propertyId = $deserializedRequest->getPropertyId();
		$languageCode = $deserializedRequest->getLanguageCode();
		$providedEditMetadata = $deserializedRequest->getEditMetadata();

		$property = $this->propertyRetriever->getProperty( $propertyId );
		$label = $property->getLabels()->getByLanguage( $languageCode );
		$property->getLabels()->removeByLanguage( $languageCode );

		$summary = LabelEditSummary::newRemoveSummary( $providedEditMetadata->getComment(), $label );

		$this->propertyUpdater->update(
			$property, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata( $providedEditMetadata->getTags(), $providedEditMetadata->isBot(), $summary )
		);
	}

}
