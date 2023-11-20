<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyDescriptions;

use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;

/**
 * @license GPL-2.0-or-later
 */
class PatchPropertyDescriptionsResponse {

	private Descriptions $Descriptions;
	private string $lastModified;
	private int $revisionId;

	public function __construct( Descriptions $Descriptions, string $lastModified, int $revisionId ) {
		$this->Descriptions = $Descriptions;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getDescriptions(): Descriptions {
		return $this->Descriptions;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}

}
