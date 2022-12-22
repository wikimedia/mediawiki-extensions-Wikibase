<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemLabels;

use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class GetItemLabelsSuccessResponse {

	private TermList $labels;
	private string $lastModified;
	private int $revisionId;

	public function __construct( TermList $labels, string $lastModified, int $revisionId ) {
		$this->labels = $labels;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getLabels(): TermList {
		return $this->labels;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}

}
