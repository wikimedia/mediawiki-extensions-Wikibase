<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatementRequest extends ReplaceStatementRequest implements ItemIdRequest {

	private string $itemId;

	public function __construct(
		string $itemId,
		string $statementId,
		array $statement,
		array $editTags,
		bool $isBot,
		?string $comment,
		?string $username
	) {
		parent::__construct( $statementId, $statement, $editTags, $isBot, $comment, $username );
		$this->itemId = $itemId;
	}

	public function getItemId(): string {
		return $this->itemId;
	}
}
