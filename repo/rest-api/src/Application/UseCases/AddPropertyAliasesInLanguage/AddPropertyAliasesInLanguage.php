<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddPropertyAliasesInLanguage;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\Repo\RestApi\Domain\Model\AliasesInLanguageEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;

/**
 * @license GPL-2.0-or-later
 */
class AddPropertyAliasesInLanguage {

	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	public function __construct( PropertyRetriever $propertyRetriever, PropertyUpdater $propertyUpdater ) {
		$this->propertyRetriever = $propertyRetriever;
		$this->propertyUpdater = $propertyUpdater;
	}

	public function execute( AddPropertyAliasesInLanguageRequest $request ): AddPropertyAliasesInLanguageResponse {
		$property = $this->propertyRetriever->getProperty( new NumericPropertyId( $request->getPropertyId() ) );
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
			$newRevision->getLastModified(),
			$newRevision->getRevisionId()
		);
	}

}
