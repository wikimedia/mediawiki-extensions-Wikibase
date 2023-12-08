<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddPropertyAliasesInLanguage;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\AliasesInLanguageEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;

/**
 * @license GPL-2.0-or-later
 */
class AddPropertyAliasesInLanguage {

	private AssertPropertyExists $assertPropertyExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	public function __construct(
		AssertPropertyExists $assertPropertyExists,
		AssertUserIsAuthorized $assertUserIsAuthorized,
		PropertyRetriever $propertyRetriever,
		PropertyUpdater $propertyUpdater
	) {
		$this->propertyRetriever = $propertyRetriever;
		$this->propertyUpdater = $propertyUpdater;
		$this->assertPropertyExists = $assertPropertyExists;
		$this->assertUserIsAuthorized = $assertUserIsAuthorized;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( AddPropertyAliasesInLanguageRequest $request ): AddPropertyAliasesInLanguageResponse {
		$propertyId = new NumericPropertyId( $request->getPropertyId() );
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$user = $request->getUsername() === null ? User::newAnonymous() : User::withUsername( $request->getUsername() );

		$this->assertPropertyExists->execute( $propertyId );
		$this->assertUserIsAuthorized->execute( $propertyId, $user );

		$property = $this->propertyRetriever->getProperty( $propertyId );
		$aliasesExist = $property->getAliasGroups()->hasGroupForLanguage( $request->getLanguageCode() );
		$originalAliases = $aliasesExist ? $property->getAliasGroups()->getByLanguage( $request->getLanguageCode() )->getAliases() : [];

		$property->getAliasGroups()->setAliasesForLanguage(
			$request->getLanguageCode(),
			array_merge( $originalAliases, $request->getAliasesInLanguage() )
		);

		$newRevision = $this->propertyUpdater->update(
			$property, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata(
				$request->getEditTags(),
				$request->isBot(),
				AliasesInLanguageEditSummary::newAddSummary(
					$request->getComment(),
					new AliasGroup( $request->getLanguageCode(), $request->getAliasesInLanguage() )
				)
			)
		);

		return new AddPropertyAliasesInLanguageResponse(
			$newRevision->getProperty()->getAliases()[$request->getLanguageCode()],
			$aliasesExist,
			$newRevision->getLastModified(),
			$newRevision->getRevisionId()
		);
	}

}
