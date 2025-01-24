<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class CreatePropertyEditSummary implements EditSummary {

	private string $editAction;
	private ?string $userComment;

	private function __construct( ?string $userComment ) {
		$this->editAction = self::ADD_ACTION;
		$this->userComment = $userComment;
	}

	public static function newSummary( ?string $userComment ): self {
		return new self( $userComment );
	}

	public function getEditAction(): string {
		return $this->editAction;
	}

	public function getUserComment(): ?string {
		return $this->userComment;
	}

}
