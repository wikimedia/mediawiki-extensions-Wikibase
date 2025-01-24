<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\SetSitelink;

use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Sitelink;

/**
 * @license GPL-2.0-or-later
 */
class SetSitelinkResponse {

	private SiteLink $sitelink;
	private string $lastModified;
	private int $revisionId;
	private bool $replaced;

	public function __construct( SiteLink $sitelink, string $lastModified, int $revisionId, bool $replaced ) {
		$this->sitelink = $sitelink;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
		$this->replaced = $replaced;
	}

	public function getSitelink(): SiteLink {
		return $this->sitelink;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}

	public function wasReplaced(): bool {
		return $this->replaced;
	}

}
