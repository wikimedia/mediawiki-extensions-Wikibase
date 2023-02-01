<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\DataAccess;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\Services\ItemDescriptionsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class TermLookupItemDataRetriever implements ItemLabelsRetriever, ItemDescriptionsRetriever {

	private TermLookup $termLookup;
	private ContentLanguages $termLanguages;

	public function __construct( TermLookup $termLookup, ContentLanguages $termLanguages ) {
		$this->termLookup = $termLookup;
		$this->termLanguages = $termLanguages;
	}

	public function getLabels( ItemId $itemId ): ?Labels {
		try {
			$labels = $this->termLookup->getLabels( $itemId, $this->termLanguages->getLanguages() );
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
