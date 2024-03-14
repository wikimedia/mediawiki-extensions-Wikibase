<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class PropertyEditSummary implements EditSummary {

	private string $editAction;
	private ?string $userComment;

	public function __construct( string $editAction, ?string $userComment ) {
		$this->editAction = $editAction;
		$this->userComment = $userComment;
	}

	public static function newPatchSummary( ?string $userComment ): self {
		return new self( self::PATCH_ACTION, $userComment );
	}

	public function getEditAction(): string {
		return $this->editAction;
	}

	public function getUserComment(): ?string {
		return $this->userComment;
	}

}
