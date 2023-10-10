<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetPropertyDescription;

use Wikibase\Repo\RestApi\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;

/**
 * @license GPL-2.0-or-later
 */
class SetPropertyDescription {

	private SetPropertyDescriptionValidator $validator;
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	public function __construct(
		SetPropertyDescriptionValidator $validator,
		PropertyRetriever $propertyRetriever,
		PropertyUpdater $propertyUpdater
	) {
		$this->validator = $validator;
		$this->propertyRetriever = $propertyRetriever;
		$this->propertyUpdater = $propertyUpdater;
	}

	public function execute( SetPropertyDescriptionRequest $request ): SetPropertyDescriptionResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$propertyId = $deserializedRequest->getPropertyId();
		$description = $deserializedRequest->getPropertyDescription();
		$editMetadata = $deserializedRequest->getEditMetadata();

		$property = $this->propertyRetriever->getProperty( $propertyId );
		$descriptionExists = $property->getDescriptions()->hasTermForLanguage( $request->getLanguageCode() );
		$property->getDescriptions()->setTerm( $description );

		$editSummary = $descriptionExists
			? DescriptionEditSummary::newReplaceSummary( $editMetadata->getComment(), $description )
			: DescriptionEditSummary::newAddSummary( $editMetadata->getComment(), $description );

		$revision = $this->propertyUpdater->update(
			$property, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata( $editMetadata->getTags(), $editMetadata->isBot(), $editSummary )
		);

		return new SetPropertyDescriptionResponse(
			$revision->getProperty()->getDescriptions()[$description->getLanguageCode()],
			$revision->getLastModified(),
			$revision->getRevisionId(),
			$descriptionExists
		);
	}
}
