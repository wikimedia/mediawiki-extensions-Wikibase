<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class LabelsEditSummary implements EditSummary {
	private string $editAction;
	private ?string $userComment;
	private TermList $labels;

	public static function newPatchSummary( ?string $userComment, TermList $labels ): self {
		$summary = new self();
		$summary->editAction = self::PATCH_ACTION;
		$summary->userComment = $userComment;
		$summary->labels = $labels;

		return $summary;
	}

	public function getEditAction(): string {
		return $this->editAction;
	}

	public function getUserComment(): ?string {
		return $this->userComment;
	}

	public function getLabels(): TermList {
		return $this->labels;
	}
}
