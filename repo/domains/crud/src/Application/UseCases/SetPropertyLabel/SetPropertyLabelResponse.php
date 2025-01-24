<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetPropertyLabel;

use Wikibase\Repo\RestApi\Domain\ReadModel\Label;

/**
 * @license GPL-2.0-or-later
 */
class SetPropertyLabelResponse {

	private Label $label;
	private string $lastModified;
	private int $revisionId;
	private bool $replaced;

	public function __construct( Label $label, string $lastModified, int $revisionId, bool $replaced ) {
		$this->label = $label;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
		$this->replaced = $replaced;
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

	public function wasReplaced(): bool {
		return $this->replaced;
	}

}
