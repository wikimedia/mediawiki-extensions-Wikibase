<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemLabels;

use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;

/**
 * @license GPL-2.0-or-later
 */
class GetItemLabelsResponse {

	private Labels $labels;
	private string $lastModified;
	private int $revisionId;

	public function __construct( Labels $labels, string $lastModified, int $revisionId ) {
		$this->labels = $labels;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getLabels(): Labels {
		return $this->labels;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}

}
