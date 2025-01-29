<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescription;

use Wikibase\Repo\RestApi\Domain\ReadModel\Description;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyDescriptionResponse {

	private Description $description;
	private string $lastModified;
	private int $revisionId;

	public function __construct( Description $description, string $lastModified, int $revisionId ) {
		$this->description = $description;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
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

}
