<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

/**
 * @license GPL-2.0-or-later
 */
class Reference {

	private string $hash;
	private array $parts;

	/**
	 * @param PropertyValuePair[] $parts
	 */
	public function __construct( string $hash, array $parts ) {
		$this->hash = $hash;
		$this->parts = $parts;
	}

	public function getHash(): string {
		return $this->hash;
	}

	public function getParts(): array {
		return $this->parts;
	}

}
