<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class DescriptionsEditSummary implements EditSummary {

	private ?string $userComment;

	public static function newPatchSummary( ?string $userComment ): self {
		$summary = new self();
		$summary->userComment = $userComment;

		return $summary;
	}

	public function getEditAction(): string {
		return '';
	}

	public function getUserComment(): ?string {
		return $this->userComment;
	}

}
