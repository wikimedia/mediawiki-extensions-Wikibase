<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLinks;

use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLinks;

/**
 * @license GPL-2.0-or-later
 */
class GetItemSiteLinksResponse {

	private SiteLinks $siteLinks;

	/**
	 * @var string timestamp in MediaWiki format 'YYYYMMDDhhmmss'
	 */
	private string $lastModified;

	private int $revisionId;

	public function __construct( SiteLinks $siteLinks, string $lastModified, int $revisionId ) {
		$this->siteLinks = $siteLinks;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getSiteLinks(): SiteLinks {
		return $this->siteLinks;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}

}
