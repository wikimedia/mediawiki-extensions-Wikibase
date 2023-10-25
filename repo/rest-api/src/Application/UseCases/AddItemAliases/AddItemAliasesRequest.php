<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddItemAliases;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemAliasesEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class AddItemAliasesRequest implements UseCaseRequest, ItemAliasesEditRequest {

	private string $itemId;
	private string $languageCode;
	private array $aliases;
	private array $editTags;
	private bool $isBot;
	private ?string $comment;

	public function __construct(
		string $itemId,
		string $languageCode,
		array $aliases,
		array $editTags,
		bool $isBot,
		?string $comment
	) {
		$this->itemId = $itemId;
		$this->languageCode = $languageCode;
		$this->aliases = $aliases;
		$this->editTags = $editTags;
		$this->isBot = $isBot;
		$this->comment = $comment;
	}

	public function getItemId(): string {
		return $this->itemId;
	}

	public function getLanguageCode(): string {
		return $this->languageCode;
	}

	public function getAliases(): array {
		return $this->aliases;
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
}
