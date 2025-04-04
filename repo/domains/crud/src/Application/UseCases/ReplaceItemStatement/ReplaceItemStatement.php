<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceItemStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertItemExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ItemRedirect;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceStatement\ReplaceStatement;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceStatement\ReplaceStatementResponse;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatement {

	private ReplaceItemStatementValidator $validator;
	private AssertItemExists $assertItemExists;
	private ReplaceStatement $replaceStatement;

	public function __construct(
		ReplaceItemStatementValidator $validator,
		AssertItemExists $assertItemExists,
		ReplaceStatement $replaceStatement
	) {
		$this->validator = $validator;
		$this->assertItemExists = $assertItemExists;
		$this->replaceStatement = $replaceStatement;
	}

	/**
	 * @throws ItemRedirect
	 * @throws UseCaseError
	 */
	public function execute( ReplaceItemStatementRequest $request ): ReplaceStatementResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );

		$this->assertItemExists->execute( $deserializedRequest->getItemId() );

		return $this->replaceStatement->execute( $request );
	}
}
