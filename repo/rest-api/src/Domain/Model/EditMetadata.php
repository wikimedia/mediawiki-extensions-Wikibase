<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class EditMetadata {

	private $tags;
	private $isBot;

	public function __construct( array $tags, bool $isBot ) {
		$this->tags = $tags;
		$this->isBot = $isBot;
	}

	public function getTags(): array {
		return $this->tags;
	}

	public function isBot(): bool {
		return $this->isBot;
	}

}
