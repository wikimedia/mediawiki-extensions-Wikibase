<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\AddItemStatement;

/**
 * @license GPL-2.0-or-later
 */
class AddItemStatementRequest {

	private $itemId;
	private $statement;
	private $editTags;
	private $isBot;

	public function __construct( string $itemId, array $statement, array $editTags, bool $isBot ) {
		$this->statement = $statement;
		$this->editTags = $editTags;
		$this->isBot = $isBot;
		$this->itemId = $itemId;
	}

	public function getStatement(): array {
		return $this->statement;
	}

	public function getEditTags(): array {
		return $this->editTags;
	}

	public function isBot(): bool {
		return $this->isBot;
	}

	public function getItemId(): string {
		return $this->itemId;
	}
}
