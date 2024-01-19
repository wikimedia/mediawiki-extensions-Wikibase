<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

use Wikibase\DataModel\SiteLink;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinkEditSummary implements EditSummary {

	private string $action;
	private ?string $userComment;
	private SiteLink $siteLink;

	private function __construct( string $action, ?string $userComment, SiteLink $siteLink ) {
		$this->action = $action;
		$this->userComment = $userComment;
		$this->siteLink = $siteLink;
	}

	public static function newRemoveSummary( ?string $userComment, SiteLink $siteLink ): self {
		return new self( self::REMOVE_ACTION, $userComment, $siteLink );
	}

	public function getEditAction(): string {
		return $this->action;
	}

	public function getUserComment(): ?string {
		return $this->userComment;
	}

	public function getSiteLink(): SiteLink {
		return $this->siteLink;
	}
}
