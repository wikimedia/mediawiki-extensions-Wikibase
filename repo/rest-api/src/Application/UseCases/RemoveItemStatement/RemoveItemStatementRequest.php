<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement;

use Wikibase\Repo\RestApi\Application\UseCases\EditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\StatementIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class RemoveItemStatementRequest implements UseCaseRequest, ItemIdRequest, StatementIdRequest, EditMetadataRequest {

	private string $itemId;
	private string $statementId;
	private array $editTags;
	private bool $isBot;
	private ?string $comment;
	private ?string $username;

	public function __construct(
		string $itemId,
		string $statementId,
		array $editTags,
		bool $isBot,
		?string $comment,
		?string $username
	) {
		$this->itemId = $itemId;
		$this->statementId = $statementId;
		$this->editTags = $editTags;
		$this->isBot = $isBot;
		$this->comment = $comment;
		$this->username = $username;
	}

	public function getItemId(): string {
		return $this->itemId;
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

}
