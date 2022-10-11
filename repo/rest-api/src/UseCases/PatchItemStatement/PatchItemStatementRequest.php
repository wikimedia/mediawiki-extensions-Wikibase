<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\PatchItemStatement;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemStatementRequest {

	private ?string $itemId;
	private string $statementId;
	private array $patch;
	private array $editTags;
	private bool $isBot;
	private ?string $comment;
	private ?string $username;

	public function __construct(
		string $statementId,
		array $patch,
		array $editTags,
		bool $isBot,
		?string $comment,
		?string $username,
		string $itemId = null
	) {
		$this->statementId = $statementId;
		$this->patch = $patch;
		$this->editTags = $editTags;
		$this->isBot = $isBot;
		$this->comment = $comment;
		$this->username = $username;
		$this->itemId = $itemId;
	}

	public function getItemId(): ?string {
		return $this->itemId;
	}

	public function getStatementId(): string {
		return $this->statementId;
	}

	public function getPatch(): array {
		return $this->patch;
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

	public function getUsername(): ?string {
		return $this->username;
	}

}
