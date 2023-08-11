<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;

/**
 * @license GPL-2.0-or-later
 */
class AddPropertyStatement {

	private AddPropertyStatementValidator $validator;
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;
	private GuidGenerator $guidGenerator;

	public function __construct(
		AddPropertyStatementValidator $validator,
		PropertyRetriever $propertyRetriever,
		GuidGenerator $guidGenerator,
		PropertyUpdater $propertyUpdater
	) {
		$this->validator = $validator;
		$this->propertyRetriever = $propertyRetriever;
		$this->guidGenerator = $guidGenerator;
		$this->propertyUpdater = $propertyUpdater;
	}

	public function execute( AddPropertyStatementRequest $request ): AddPropertyStatementResponse {
		$this->validator->assertValidRequest( $request );

		$propertyId = new NumericPropertyId( $request->getPropertyId() );
		$property = $this->propertyRetriever->getProperty( $propertyId );
		$statement = $this->validator->getValidatedStatement();

		$newStatementGuid = $this->guidGenerator->newStatementId( $propertyId );
		$statement->setGuid( (string)$newStatementGuid );

		$property->getStatements()->addStatement( $statement );

		$revision = $this->propertyUpdater->update(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$property,
			new EditMetadata(
				[],
				false,
				StatementEditSummary::newAddSummary( null, $statement )
			)
		);

		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		return new AddPropertyStatementResponse( $revision->getProperty()->getStatements()->getStatementById( $newStatementGuid ) );
	}

}
