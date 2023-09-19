<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
class PatchPropertyStatementRequest extends PatchStatementRequest implements PropertyIdRequest {

	private string $propertyId;

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
		$this->propertyId = $propertyId;
	}

	public function getPropertyId(): string {
		return $this->propertyId;
	}

}
