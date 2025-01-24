<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescriptions;

use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Descriptions;

/**
 * @license GPL-2.0-or-later
 */
class GetItemDescriptionsResponse {

	private Descriptions $descriptions;
	private string $lastModified;
	private int $revisionId;

	public function __construct( Descriptions $descriptions, string $lastModified, int $revisionId ) {
		$this->descriptions = $descriptions;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getDescriptions(): Descriptions {
		return $this->descriptions;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}

}
