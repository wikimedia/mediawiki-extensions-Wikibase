<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLink;

use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLink;

/**
 * @license GPL-2.0-or-later
 */
class GetItemSiteLinkResponse {

	private SiteLink $siteLink;

	/**
	 * @var string timestamp in MediaWiki format 'YYYYMMDDhhmmss'
	 */
	private string $lastModified;

	private int $revisionId;

	public function __construct( SiteLink $siteLink, string $lastModified, int $revisionId ) {
		$this->siteLink = $siteLink;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getSiteLink(): SiteLink {
		return $this->siteLink;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}

}
