<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement;

use Wikibase\Repo\RestApi\Application\UseCases\EditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCases\StatementIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\StatementSerializationRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class ReplaceStatementRequest implements UseCaseRequest, StatementIdRequest, StatementSerializationRequest, EditMetadataRequest {

	private string $statementId;
	private array $statement;
	private array $editTags;
	private bool $isBot;
	private ?string $comment;
	private ?string $username;

	public function __construct(
		string $statementId,
		array $statement,
		array $editTags,
		bool $isBot,
		?string $comment,
		?string $username
	) {
		$this->statementId = $statementId;
		$this->statement = $statement;
		$this->editTags = $editTags;
		$this->isBot = $isBot;
		$this->comment = $comment;
		$this->username = $username;
	}

	public function getStatementId(): string {
		return $this->statementId;
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

	public function getComment(): ?string {
		return $this->comment;
	}

	public function getUsername(): ?string {
		return $this->username;
	}

}
