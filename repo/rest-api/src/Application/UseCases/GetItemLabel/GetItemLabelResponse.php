<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel;

use Wikibase\Repo\RestApi\Domain\ReadModel\Label;

/**
 * @license GPL-2.0-or-later
 */
class GetItemLabelResponse {

	private Label $label;
	private string $lastModified;
	private int $revisionId;

	public function __construct( Label $label, string $lastModified, int $revisionId ) {
		$this->label = $label;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getLabel(): Label {
		return $this->label;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}

}
