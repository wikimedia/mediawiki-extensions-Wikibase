<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class Descriptions {
	private array $descriptions;

	public function __construct( Description ...$descriptions ) {
		$this->descriptions = array_combine(
			array_map( fn( Description $d ) => $d->languageCode, $descriptions ),
			$descriptions
		);
	}

	public function getDescriptionInLanguage( string $languageCode ): ?Description {
		return $this->descriptions[$languageCode] ?? null;
	}
}
