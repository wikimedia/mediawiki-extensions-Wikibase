<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

use Wikibase\DataModel\Term\Term;

/**
 * @license GPL-2.0-or-later
 */
class DescriptionEditSummary implements EditSummary {

	private ?string $userComment;
	private Term $description;

	public function __construct( Term $description, ?string $userComment ) {
		$this->userComment = $userComment;
		$this->description = $description;
	}

	public function getEditAction(): string {
		return self::REPLACE_ACTION;
	}

	public function getUserComment(): ?string {
		return $this->userComment;
	}

	public function getDescription(): Term {
		return $this->description;
	}
}
