<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
class ReplacePropertyStatementRequest extends ReplaceStatementRequest implements PropertyIdRequest {

	private string $propertyId;

	public function __construct(
		string $propertyId,
		string $statementId,
		array $statement,
		array $editTags,
		bool $isBot,
		?string $comment,
		?string $username
	) {
		parent::__construct( $statementId, $statement, $editTags, $isBot, $comment, $username );
		$this->propertyId = $propertyId;
	}

	public function getPropertyId(): string {
		return $this->propertyId;
	}
}
