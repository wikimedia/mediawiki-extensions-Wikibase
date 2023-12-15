<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyDescription;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;

/**
 * @license GPL-2.0-or-later
 */
class RemovePropertyDescription {

	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	public function __construct( PropertyRetriever $propertyRetriever, PropertyUpdater $propertyUpdater ) {
		$this->propertyRetriever = $propertyRetriever;
		$this->propertyUpdater = $propertyUpdater;
	}

	public function execute( RemovePropertyDescriptionRequest $request ): void {
		$propertyId = new NumericPropertyId( $request->getPropertyId() );

		$property = $this->propertyRetriever->getProperty( $propertyId );
		$description = $property->getDescriptions()->getByLanguage( $request->getLanguageCode() );
		$property->getDescriptions()->removeByLanguage( $request->getLanguageCode() );

		$summary = DescriptionEditSummary::newRemoveSummary( $request->getComment(), $description );
		$this->propertyUpdater->update(
			$property, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata( $request->getEditTags(), $request->isBot(), $summary )
		);
	}

}
