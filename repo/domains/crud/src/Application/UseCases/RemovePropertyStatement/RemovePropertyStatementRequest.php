<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyStatementIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveStatement\RemoveStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
class RemovePropertyStatementRequest extends RemoveStatementRequest implements PropertyStatementIdRequest {

	private string $propertyId;

	public function __construct(
		string $propertyId,
		string $statementId,
		array $editTags,
		bool $isBot,
		?string $comment,
		?string $username
	) {
		parent::__construct( $statementId, $editTags, $isBot, $comment, $username );
		$this->propertyId = $propertyId;
	}

	public function getPropertyId(): string {
		return $this->propertyId;
	}

}
