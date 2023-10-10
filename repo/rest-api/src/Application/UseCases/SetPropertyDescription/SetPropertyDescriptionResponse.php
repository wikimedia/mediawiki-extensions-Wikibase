<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetPropertyDescription;

use Wikibase\Repo\RestApi\Domain\ReadModel\Description;

/**
 * @license GPL-2.0-or-later
 */
class SetPropertyDescriptionResponse {

	private Description $description;
	private string $lastModified;
	private int $revisionId;
	private bool $replaced;

	public function __construct( Description $description, string $lastModified, int $revisionId, bool $replaced ) {
		$this->description = $description;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
		$this->replaced = $replaced;
	}

	public function getDescription(): Description {
		return $this->description;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}

	public function wasReplaced(): bool {
		return $this->replaced;
	}

}
