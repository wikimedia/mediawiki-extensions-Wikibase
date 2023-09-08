<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement;

use Wikibase\Repo\RestApi\Application\UseCases\EditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\StatementSerializationRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class AddItemStatementRequest implements UseCaseRequest, ItemIdRequest, StatementSerializationRequest, EditMetadataRequest {

	private string $itemId;
	private array $statement;
	private array $editTags;
	private bool $isBot;
	private ?string $comment;
	private ?string $username;

	public function __construct( string $itemId, array $statement, array $editTags, bool $isBot, ?string $comment, ?string $username ) {
		$this->statement = $statement;
		$this->editTags = $editTags;
		$this->isBot = $isBot;
		$this->itemId = $itemId;
		$this->comment = $comment;
		$this->username = $username;
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

	public function getComment(): ?string {
		return $this->comment;
	}

	public function getUsername(): ?string {
		return $this->username;
	}
}
