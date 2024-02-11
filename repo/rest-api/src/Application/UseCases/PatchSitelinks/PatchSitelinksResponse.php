<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks;

use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelinks;

/**
 * @license GPL-2.0-or-later
 */
class PatchSitelinksResponse {
	private Sitelinks $sitelinks;
	private string $lastModified;
	private int $revisionId;

	public function __construct( Sitelinks $sitelinks, string $lastModified, int $revisionId ) {
		$this->sitelinks = $sitelinks;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getSitelinks(): Sitelinks {
		return $this->sitelinks;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}
}
