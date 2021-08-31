<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;

/**
 * @since 1.1
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 * @author Addshore
 */
class LanguageLabelDescriptionLookup implements LabelDescriptionLookup {

	/**
	 * @var TermLookup
	 */
	private $termLookup;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @param TermLookup $termLookup
	 * @param string $languageCode
	 */
	public function __construct( TermLookup $termLookup, $languageCode ) {
		$this->termLookup = $termLookup;
		$this->languageCode = $languageCode;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return Term|null
	 */
	public function getLabel( EntityId $entityId ) {
		try {
			$text = $this->termLookup->getLabel( $entityId, $this->languageCode );
		} catch ( TermLookupException $ex ) {
			throw new LabelDescriptionLookupException( $entityId, 'Failed to lookup label', $ex );
		}

		if ( $text === null ) {
			return null;
		}

		return new Term( $this->languageCode, $text );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return Term|null
	 */
	public function getDescription( EntityId $entityId ) {
		try {
			$text = $this->termLookup->getDescription( $entityId, $this->languageCode );
		} catch ( TermLookupException $ex ) {
			throw new LabelDescriptionLookupException( $entityId, 'Failed to lookup description', $ex );
		}

		if ( $text === null ) {
			return null;
		}

		return new Term( $this->languageCode, $text );
	}

}
