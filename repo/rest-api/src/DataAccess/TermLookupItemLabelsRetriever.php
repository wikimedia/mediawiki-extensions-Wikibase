<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\DataAccess;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class TermLookupItemLabelsRetriever implements ItemLabelsRetriever {

	private TermLookup $termLookup;
	private ContentLanguages $termLanguages;

	public function __construct( TermLookup $termLookup, ContentLanguages $termLanguages ) {
		$this->termLookup = $termLookup;
		$this->termLanguages = $termLanguages;
	}

	public function getLabels( ItemId $itemId ): ?TermList {
		try {
			$labels = $this->termLookup->getLabels( $itemId, $this->termLanguages->getLanguages() );
		} catch ( TermLookupException $e ) {
			// this probably means that the item does not exist, which should be checked prior to calling this method
			return null;
		}

		return new TermList( array_map(
			fn( string $language, string $label ) => new Term( $language, $label ),
			array_keys( $labels ),
			$labels
		) );
	}

}
