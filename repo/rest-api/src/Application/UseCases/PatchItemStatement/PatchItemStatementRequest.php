<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemStatementRequest extends PatchStatementRequest implements ItemIdRequest {

	private string $itemId;

	public function __construct(
		string $propertyId,
		string $statementId,
		array $patch,
		array $editTags,
		bool $isBot,
		?string $comment,
		?string $username
	) {
		parent::__construct( $statementId, $patch, $editTags, $isBot, $comment, $username );
		$this->itemId = $propertyId;
	}

	public function getItemId(): string {
		return $this->itemId;
	}

}
