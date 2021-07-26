<?php

namespace Wikibase\Repo\Rdf;

use Exception;

/**
 * @license GPL-2.0-or-later
 */
class UnknownFlavorException extends Exception {

	/** @var string */
	private $unknownFlavor;

	/** @var string[] */
	private $knownFlavors;

	public function __construct( string $unknownFlavor, array $knownFlavors ) {
		$this->unknownFlavor = $unknownFlavor;
		$this->knownFlavors = $knownFlavors;
		parent::__construct(
			"Invalid flavor $unknownFlavor: must be one of " .
			implode( ', ', $knownFlavors )
		);
	}

	public function getUnknownFlavor(): string {
		return $this->unknownFlavor;
	}

	/** @return string[] */
	public function getKnownFlavors(): array {
		return $this->knownFlavors;
	}

}
