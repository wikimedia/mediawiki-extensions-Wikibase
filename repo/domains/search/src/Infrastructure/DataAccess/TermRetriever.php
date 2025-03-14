<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\Repo\Domains\Search\Domain\Model\Description;
use Wikibase\Repo\Domains\Search\Domain\Model\Label;

/**
 * @license GPL-2.0-or-later
 */
class TermRetriever {

	private TermLookup $termLookup;

	public function __construct( TermLookup $termLookup ) {
		$this->termLookup = $termLookup;
	}

	public function getLabel( EntityId $entityId, string $languageCode ): ?Label {
		try {
			$labelText = $this->termLookup->getLabel( $entityId, $languageCode );
		} catch ( TermLookupException $e ) {
			// this probably means that the entity does not exist
			return null;
		}

		return $labelText !== null ? new Label( $languageCode, $labelText ) : null;
	}

	public function getDescription( EntityId $entityId, string $languageCode ): ?Description {
		try {
			$descriptionText = $this->termLookup->getDescription( $entityId, $languageCode );
		} catch ( TermLookupException $e ) {
			// this probably means that the entity does not exist
			return null;
		}
		return $descriptionText !== null ? new Description( $languageCode, $descriptionText ) : null;
	}

}
