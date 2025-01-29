<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemStatementIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveStatement\RemoveStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
class RemoveItemStatementRequest extends RemoveStatementRequest implements ItemStatementIdRequest {

	private string $itemId;

	public function __construct(
		string $itemId,
		string $statementId,
		array $editTags,
		bool $isBot,
		?string $comment,
		?string $username
	) {
		parent::__construct( $statementId, $editTags, $isBot, $comment, $username );
		$this->itemId = $itemId;
	}

	public function getItemId(): string {
		return $this->itemId;
	}

}
