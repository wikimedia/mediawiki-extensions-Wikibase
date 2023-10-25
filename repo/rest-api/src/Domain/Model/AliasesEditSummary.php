<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

use Wikibase\DataModel\Term\AliasGroupList;

/**
 * @license GPL-2.0-or-later
 */
class AliasesEditSummary implements EditSummary {

	private string $editAction;
	private ?string $userComment;
	private AliasGroupList $originalAliases;
	private AliasGroupList $modifiedAliases;

	public static function newAddSummary( ?string $userComment, AliasGroupList $modifiedAliases ): self {
		$summary = new self();
		$summary->editAction = self::ADD_ACTION;
		$summary->userComment = $userComment;
		// TODO: hacky? recheck when creating the edit summary for adding aliases
		$summary->originalAliases = new AliasGroupList();
		$summary->modifiedAliases = $modifiedAliases;

		return $summary;
	}

	public static function newPatchSummary( ?string $userComment, AliasGroupList $originalAliases, AliasGroupList $patchedAliases ): self {
		$summary = new self();
		$summary->editAction = self::PATCH_ACTION;
		$summary->userComment = $userComment;
		$summary->originalAliases = $originalAliases;
		$summary->modifiedAliases = $patchedAliases;

		return $summary;
	}

	public function getEditAction(): string {
		return $this->editAction;
	}

	public function getUserComment(): ?string {
		return $this->userComment;
	}

	public function getOriginalAliases(): AliasGroupList {
		return $this->originalAliases;
	}

	public function getModifiedAliases(): AliasGroupList {
		return $this->modifiedAliases;
	}

}
