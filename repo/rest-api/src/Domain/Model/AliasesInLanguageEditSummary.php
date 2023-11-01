<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

use Wikibase\DataModel\Term\AliasGroup;

/**
 * @license GPL-2.0-or-later
 */
class AliasesInLanguageEditSummary implements EditSummary {

	private string $editAction;
	private ?string $userComment;
	private AliasGroup $aliases;

	public static function newAddSummary( ?string $userComment, AliasGroup $newAliases ): self {
		$summary = new self();
		$summary->editAction = self::ADD_ACTION;
		$summary->userComment = $userComment;
		$summary->aliases = $newAliases;

		return $summary;
	}

	public function getEditAction(): string {
		return $this->editAction;
	}

	public function getUserComment(): ?string {
		return $this->userComment;
	}

	public function getAliases(): AliasGroup {
		return $this->aliases;
	}

}
