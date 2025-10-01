<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class Labels {
	private array $labels;

	public function __construct( Label ...$labels ) {
		$this->labels = array_combine(
			array_map( fn( Label $l ) => $l->languageCode, $labels ),
			$labels
		);
	}

	public function getLabelInLanguage( string $languageCode ): ?Label {
		return $this->labels[$languageCode] ?? null;
	}
}
