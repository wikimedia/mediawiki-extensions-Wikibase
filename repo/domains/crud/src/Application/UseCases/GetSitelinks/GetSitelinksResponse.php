<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelinks;

use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Sitelinks;

/**
 * @license GPL-2.0-or-later
 */
class GetSitelinksResponse {

	private Sitelinks $sitelinks;

	/**
	 * @var string timestamp in MediaWiki format 'YYYYMMDDhhmmss'
	 */
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
