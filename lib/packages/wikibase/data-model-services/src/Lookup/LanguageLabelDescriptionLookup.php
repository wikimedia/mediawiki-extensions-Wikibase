<?php

namespace Wikibase\DataModel\Services\Lookup;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;

/**
 * @since 1.1
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
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
	 * @throws OutOfBoundsException if no such label or entity could be found
	 * @return Term
	 */
	public function getLabel( EntityId $entityId ) {
		$text = $this->termLookup->getLabel( $entityId, $this->languageCode );
		return new Term( $this->languageCode, $text );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws OutOfBoundsException if no such description or entity could be found
	 * @return Term
	 */
	public function getDescription( EntityId $entityId ) {
		$text = $this->termLookup->getDescription( $entityId, $this->languageCode );
		return new Term( $this->languageCode, $text );
	}

}
