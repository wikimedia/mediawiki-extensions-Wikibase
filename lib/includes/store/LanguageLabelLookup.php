<?php

namespace Wikibase\Lib\Store;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\LanguageFallbackChain;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LanguageLabelLookup implements LabelLookup {

	/**
	 * @var EntityTermLookup
	 */
	private $entityTermLookup;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @param EntityTermLookup $entityTermLookup
	 * @param string $languageCode
	 */
	public function __construct( EntityTermLookup $entityTermLookup, $languageCode ) {
		$this->entityTermLookup = $entityTermLookup;
		$this->languageCode = $languageCode;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws OutOfBoundsException
	 * @return string
	 */
	public function getLabel( EntityId $entityId ) {
		return $this->entityTermLookup->getLabel( $entityId, $this->languageCode );
	}

}
