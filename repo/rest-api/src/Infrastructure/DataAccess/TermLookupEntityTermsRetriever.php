<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\Services\ItemDescriptionRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemDescriptionsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyLabelsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class TermLookupEntityTermsRetriever implements
	ItemLabelRetriever,
	ItemLabelsRetriever,
	ItemDescriptionRetriever,
	ItemDescriptionsRetriever,
	PropertyLabelsRetriever
{

	private TermLookup $termLookup;
	private ContentLanguages $termLanguages;

	public function __construct( TermLookup $termLookup, ContentLanguages $termLanguages ) {
		$this->termLookup = $termLookup;
		$this->termLanguages = $termLanguages;
	}

	public function getLabel( ItemId $itemId, string $languageCode ): ?Label {
		try {
			$labelText = $this->termLookup->getLabel( $itemId, $languageCode );
		} catch ( TermLookupException $e ) {
			// this probably means that the item does not exist, which should be checked prior to calling this method
			return null;
		}

		return $labelText !== null ? new Label( $languageCode, $labelText ) : null;
	}

	public function getLabels( EntityId $entityId ): ?Labels {
		try {
			$labels = $this->termLookup->getLabels( $entityId, $this->termLanguages->getLanguages() );
		} catch ( TermLookupException $e ) {
			// this probably means that the item does not exist, which should be checked prior to calling this method
			return null;
		}

		return new Labels( ...array_map(
			fn( $text, $language ) => new Label( $language, $text ),
			$labels,
			array_keys( $labels )
		) );
	}

	public function getDescription( ItemId $itemId, string $languageCode ): ?Description {
		try {
			$descriptionText = $this->termLookup->getDescription( $itemId, $languageCode );
		} catch ( TermLookupException $e ) {
			// this probably means that the item does not exist, which should be checked prior to calling this method
			return null;
		}
		return $descriptionText !== null ? new Description( $languageCode, $descriptionText ) : null;
	}

	public function getDescriptions( ItemId $itemId ): ?Descriptions {
		try {
			$descriptions = $this->termLookup->getDescriptions( $itemId, $this->termLanguages->getLanguages() );
		} catch ( TermLookupException $e ) {
			// this probably means that the item does not exist, which should be checked prior to calling this method
			return null;
		}

		return new Descriptions( ...array_map(
			fn( $text, $language ) => new Description( $language, $text ),
			$descriptions,
			array_keys( $descriptions )
		) );
	}

}
