<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\RemoveItemStatement;

/**
 * @license GPL-2.0-or-later
 */
class RemoveItemStatementRequest {

	private string $statementId;
	private array $editTags;
	private bool $isBot;
	private ?string $comment;
	private ?string $username;
	private ?string $itemId;

	public function __construct(
		string $statementId,
		array $editTags,
		bool $isBot,
		?string $comment,
		?string $username,
		string $itemId = null
	) {
		$this->statementId = $statementId;
		$this->editTags = $editTags;
		$this->isBot = $isBot;
		$this->comment = $comment;
		$this->username = $username;
		$this->itemId = $itemId;
	}

	public function getStatementId(): string {
		return $this->statementId;
	}

	public function getEditTags(): array {
		return $this->editTags;
	}

	public function isBot(): bool {
		return $this->isBot;
	}

	public function getComment(): ?string {
		return $this->comment;
	}

	public function hasUser(): bool {
		return $this->username !== null;
	}

	public function getUsername(): ?string {
		return $this->username;
	}

	public function getItemId(): ?string {
		return $this->itemId;
	}
}
