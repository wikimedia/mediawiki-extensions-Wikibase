<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

use Wikibase\DataModel\SiteLink;

/**
 * @license GPL-2.0-or-later
 */
class SitelinkEditSummary implements EditSummary {

	private string $action;
	private ?string $userComment;
	private SiteLink $sitelink;

	private function __construct( string $action, ?string $userComment, SiteLink $sitelink ) {
		$this->action = $action;
		$this->userComment = $userComment;
		$this->sitelink = $sitelink;
	}

	public static function newRemoveSummary( ?string $userComment, SiteLink $sitelink ): self {
		return new self( self::REMOVE_ACTION, $userComment, $sitelink );
	}

	public function getEditAction(): string {
		return $this->action;
	}

	public function getUserComment(): ?string {
		return $this->userComment;
	}

	public function getSitelink(): SiteLink {
		return $this->sitelink;
	}
}
