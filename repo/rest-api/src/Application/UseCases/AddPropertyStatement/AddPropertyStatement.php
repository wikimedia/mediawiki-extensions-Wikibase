<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement;

use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\RestApi\Domain\Services\PropertyWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class AddPropertyStatement {

	private AddPropertyStatementValidator $validator;
	private AssertPropertyExists $assertPropertyExists;
	private PropertyWriteModelRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;
	private GuidGenerator $guidGenerator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	public function __construct(
		AddPropertyStatementValidator $validator,
		AssertPropertyExists $assertPropertyExists,
		PropertyWriteModelRetriever $propertyRetriever,
		GuidGenerator $guidGenerator,
		PropertyUpdater $propertyUpdater,
		AssertUserIsAuthorized $assertUserIsAuthorized
	) {
		$this->validator = $validator;
		$this->assertPropertyExists = $assertPropertyExists;
		$this->propertyRetriever = $propertyRetriever;
		$this->guidGenerator = $guidGenerator;
		$this->propertyUpdater = $propertyUpdater;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
	}

	public function execute( AddPropertyStatementRequest $request ): AddPropertyStatementResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$propertyId = $deserializedRequest->getPropertyId();
		$statement = $deserializedRequest->getStatement();
		$editMetadata = $deserializedRequest->getEditMetadata();

		$this->assertPropertyExists->execute( $propertyId );
		$this->assertUserIsAuthorized->checkEditPermissions( $propertyId, $editMetadata->getUser() );

		$property = $this->propertyRetriever->getPropertyWriteModel( $propertyId );

		$newStatementGuid = $this->guidGenerator->newStatementId( $propertyId );
		$statement->setGuid( (string)$newStatementGuid );

		$property->getStatements()->addStatement( $statement );

		$revision = $this->propertyUpdater->update(
			$property, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata(
				$editMetadata->getTags(),
				$editMetadata->isBot(),
				StatementEditSummary::newAddSummary( $editMetadata->getComment(), $statement )
			)
		);

		return new AddPropertyStatementResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$revision->getProperty()->getStatements()->getStatementById( $newStatementGuid ),
			$revision->getLastModified(),
			$revision->getRevisionId()
		);
	}

}
