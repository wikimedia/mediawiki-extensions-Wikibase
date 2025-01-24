<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemStatementIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchStatement\PatchStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemStatementRequest extends PatchStatementRequest implements ItemStatementIdRequest {

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
