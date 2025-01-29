<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

use Wikibase\DataModel\Term\Term;

/**
 * @license GPL-2.0-or-later
 */
class DescriptionEditSummary implements EditSummary {

	private string $editAction;
	private ?string $userComment;
	private Term $description;

	public function __construct( string $editAction, ?string $userComment, Term $description ) {
		$this->editAction = $editAction;
		$this->userComment = $userComment;
		$this->description = $description;
	}

	public static function newAddSummary( ?string $userComment, Term $description ): self {
		return new self( self::ADD_ACTION, $userComment, $description );
	}

	public static function newReplaceSummary( ?string $userComment, Term $description ): self {
		return new self( self::REPLACE_ACTION, $userComment, $description );
	}

	public static function newRemoveSummary( ?string $userComment, Term $description ): self {
		return new self( self::REMOVE_ACTION, $userComment, $description );
	}

	public function getEditAction(): string {
		return $this->editAction;
	}

	public function getUserComment(): ?string {
		return $this->userComment;
	}

	public function getDescription(): Term {
		return $this->description;
	}
}
