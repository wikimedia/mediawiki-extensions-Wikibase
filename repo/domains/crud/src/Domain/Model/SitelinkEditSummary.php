<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\Model;

use Wikibase\DataModel\SiteLink;

/**
 * @license GPL-2.0-or-later
 */
class SitelinkEditSummary implements EditSummary {

	private string $action;
	private ?string $userComment;
	private SiteLink $sitelink;
	private bool $badgesOnly;

	private function __construct( string $action, ?string $userComment, SiteLink $sitelink, bool $badgesOnly = false ) {
		$this->action = $action;
		$this->userComment = $userComment;
		$this->sitelink = $sitelink;
		$this->badgesOnly = $badgesOnly;
	}

	public static function newAddSummary( ?string $userComment, SiteLink $sitelink ): self {
		return new self( self::ADD_ACTION, $userComment, $sitelink );
	}

	public static function newReplaceSummary( ?string $userComment, SiteLink $sitelink, SiteLink $replacedSitelink ): self {
		$badgesOnly = $sitelink->getPageName() === $replacedSitelink->getPageName();
		return new self( self::REPLACE_ACTION, $userComment, $sitelink, $badgesOnly );
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

	public function hasBadges(): bool {
		return (bool)$this->sitelink->getBadges();
	}

	public function isBadgesOnly(): bool {
		return $this->badgesOnly;
	}

}
