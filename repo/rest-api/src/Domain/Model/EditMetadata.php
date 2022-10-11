<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class EditMetadata {

	private array $tags;
	private bool $isBot;
	private EditSummary $summary;

	public function __construct( array $tags, bool $isBot, EditSummary $summary ) {
		$this->tags = $tags;
		$this->isBot = $isBot;
		$this->summary = $summary;
	}

	public function getTags(): array {
		return $this->tags;
	}

	public function isBot(): bool {
		return $this->isBot;
	}

	public function getSummary(): EditSummary {
		return $this->summary;
	}

}
