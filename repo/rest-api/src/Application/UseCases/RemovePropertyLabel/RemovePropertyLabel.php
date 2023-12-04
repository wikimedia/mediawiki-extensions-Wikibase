<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyLabel;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\LabelEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;

/**
 * @license GPL-2.0-or-later
 */
class RemovePropertyLabel {

	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	public function __construct( PropertyRetriever $propertyRetriever, PropertyUpdater $propertyUpdater ) {
		$this->propertyRetriever = $propertyRetriever;
		$this->propertyUpdater = $propertyUpdater;
	}

	public function execute( RemovePropertyLabelRequest $request ): void {
		$propertyId = new NumericPropertyId( $request->getPropertyId() );

		$property = $this->propertyRetriever->getProperty( $propertyId );
		$label = $property->getLabels()->getByLanguage( $request->getLanguageCode() );
		$property->getLabels()->removeByLanguage( $request->getLanguageCode() );

		$summary = LabelEditSummary::newRemoveSummary( $request->getComment(), $label );

		$this->propertyUpdater->update(
			$property, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata( $request->getEditTags(), $request->isBot(), $summary )
		);
	}

}
