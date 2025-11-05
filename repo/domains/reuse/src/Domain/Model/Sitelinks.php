<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class Sitelinks {
	private array $sitelinks;

	public function __construct( Sitelink ...$sitelinks ) {
		$this->sitelinks = array_combine(
			array_map( fn( Sitelink $s ) => $s->site, $sitelinks ),
			$sitelinks
		);
	}

	public function getSitelinkForSite( string $siteId ): ?Sitelink {
		return $this->sitelinks[$siteId] ?? null;
	}
}
