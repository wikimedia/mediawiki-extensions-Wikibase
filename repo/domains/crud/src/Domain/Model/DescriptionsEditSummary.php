<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class DescriptionsEditSummary implements EditSummary {

	private string $editAction;
	private ?string $userComment;
	private TermList $originalDescriptions;
	private TermList $modifiedDescriptions;

	public static function newPatchSummary(
		?string $userComment,
		TermList $originalDescriptions,
		TermList $modifiedDescriptions
	): self {
		$summary = new self();
		$summary->editAction = self::PATCH_ACTION;
		$summary->userComment = $userComment;
		$summary->originalDescriptions = $originalDescriptions;
		$summary->modifiedDescriptions = $modifiedDescriptions;

		return $summary;
	}

	public function getEditAction(): string {
		return $this->editAction;
	}

	public function getUserComment(): ?string {
		return $this->userComment;
	}

	public function getOriginalDescriptions(): TermList {
		return $this->originalDescriptions;
	}

	public function getModifiedDescriptions(): TermList {
		return $this->modifiedDescriptions;
	}

}
