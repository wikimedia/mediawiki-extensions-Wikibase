<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddPropertyAliasesInLanguage;

use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\AliasesInLanguageEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;

/**
 * @license GPL-2.0-or-later
 */
class AddPropertyAliasesInLanguage {

	private AddPropertyAliasesInLanguageValidator $validator;
	private AssertPropertyExists $assertPropertyExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	public function __construct(
		AddPropertyAliasesInLanguageValidator $validator,
		AssertPropertyExists $assertPropertyExists,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		PropertyRetriever $propertyRetriever,
		PropertyUpdater $propertyUpdater
	) {
		$this->validator = $validator;
		$this->assertPropertyExists = $assertPropertyExists;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
		$this->propertyRetriever = $propertyRetriever;
		$this->propertyUpdater = $propertyUpdater;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( AddPropertyAliasesInLanguageRequest $request ): AddPropertyAliasesInLanguageResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );

		$propertyId = $deserializedRequest->getPropertyId();
		$languageCode = $deserializedRequest->getLanguageCode();
		$newAliases = $deserializedRequest->getPropertyAliasesInLanguage();
		$editMetadata = $deserializedRequest->getEditMetadata();

		$this->assertPropertyExists->execute( $propertyId );
		$this->assertUserIsAuthorized->execute( $propertyId, $editMetadata->getUser() );

		$property = $this->propertyRetriever->getProperty( $propertyId );
		$aliasesExist = $property->getAliasGroups()->hasGroupForLanguage( $languageCode );
		$originalAliases = $aliasesExist ? $property->getAliasGroups()->getByLanguage( $languageCode )->getAliases() : [];

		$property->getAliasGroups()->setAliasesForLanguage( $languageCode, array_merge( $originalAliases, $newAliases ) );

		$newRevision = $this->propertyUpdater->update(
			$property, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata(
				$editMetadata->getTags(),
				$editMetadata->isBot(),
				AliasesInLanguageEditSummary::newAddSummary( $editMetadata->getComment(), new AliasGroup( $languageCode, $newAliases ) )
			)
		);

		return new AddPropertyAliasesInLanguageResponse(
			$newRevision->getProperty()->getAliases()[ $languageCode ],
			$aliasesExist,
			$newRevision->getLastModified(),
			$newRevision->getRevisionId()
		);
	}

}
