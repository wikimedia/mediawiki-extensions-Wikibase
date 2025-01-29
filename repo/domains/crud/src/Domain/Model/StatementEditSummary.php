<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 */
class StatementEditSummary implements EditSummary {

	private string $editAction;
	private ?string $userComment;
	private Statement $statement;

	public static function newAddSummary( ?string $userComment, Statement $statement ): self {
		$summary = new self();
		$summary->editAction = self::ADD_ACTION;
		$summary->userComment = $userComment;
		$summary->statement = $statement;

		return $summary;
	}

	public static function newPatchSummary( ?string $userComment, Statement $statement ): self {
		$summary = new self();
		$summary->editAction = self::PATCH_ACTION;
		$summary->userComment = $userComment;
		$summary->statement = $statement;

		return $summary;
	}

	public static function newReplaceSummary( ?string $userComment, Statement $statement ): self {
		$summary = new self();
		$summary->editAction = self::REPLACE_ACTION;
		$summary->userComment = $userComment;
		$summary->statement = $statement;

		return $summary;
	}

	public static function newRemoveSummary( ?string $userComment, Statement $statement ): self {
		$summary = new self();
		$summary->editAction = self::REMOVE_ACTION;
		$summary->userComment = $userComment;
		$summary->statement = $statement;

		return $summary;
	}

	public function getEditAction(): string {
		return $this->editAction;
	}

	public function getUserComment(): ?string {
		return $this->userComment;
	}

	public function getStatement(): Statement {
		return $this->statement;
	}

}
