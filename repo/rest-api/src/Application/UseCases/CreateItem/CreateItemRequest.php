<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\CreateItem;

/**
 * @license GPL-2.0-or-later
 */
class CreateItemRequest {

	private array $item;
	private array $editTags;
	private bool $isBot;
	private ?string $comment;
	private ?string $username;

	public function __construct( array $item, array $editTags, bool $isBot, ?string $comment, ?string $username ) {
		$this->item = $item;
		$this->editTags = $editTags;
		$this->isBot = $isBot;
		$this->comment = $comment;
		$this->username = $username;
	}

	public function getItem(): array {
		return $this->item;
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
