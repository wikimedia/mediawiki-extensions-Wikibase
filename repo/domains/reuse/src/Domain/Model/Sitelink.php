<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class Sitelink {
	public function __construct(
		public readonly string $site,
		public readonly string $title,
		public readonly string $url,
	) {
	}
}
