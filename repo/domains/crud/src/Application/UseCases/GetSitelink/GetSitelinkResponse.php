<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelink;

use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Sitelink;

/**
 * @license GPL-2.0-or-later
 */
class GetSitelinkResponse {

	private Sitelink $sitelink;

	/**
	 * @var string timestamp in MediaWiki format 'YYYYMMDDhhmmss'
	 */
	private string $lastModified;

	private int $revisionId;

	public function __construct( Sitelink $sitelink, string $lastModified, int $revisionId ) {
		$this->sitelink = $sitelink;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getSitelink(): Sitelink {
		return $this->sitelink;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}

}
