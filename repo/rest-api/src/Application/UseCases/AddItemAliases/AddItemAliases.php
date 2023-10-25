<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddItemAliases;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\AliasesEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class AddItemAliases {

	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	public function __construct( ItemRetriever $itemRetriever, ItemUpdater $itemUpdater ) {
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( AddItemAliasesRequest $request ): AddItemAliasesResponse {
		// TODO: validate
		$itemId = new ItemId( $request->getItemId() );
		$languageCode = $request->getLanguageCode();

		// TODO: existence check
		// TODO: deserialize edit metadata
		// TODO: assert user is authorized

		$item = $this->itemRetriever->getItem( $itemId );
		$aliasesExist = $item->getAliasGroups()->hasGroupForLanguage( $languageCode );
		$originalAliases = $aliasesExist ? $item->getAliasGroups()->getByLanguage( $languageCode )->getAliases() : [];
		$modifiedAliases = array_merge( $originalAliases, $request->getAliases() );

		// TODO: check duplicate aliases

		$item->getAliasGroups()->setAliasesForLanguage( $languageCode, $modifiedAliases );
		$newRevision = $this->itemUpdater->update(
			$item, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata(
				[],
				false,
				// TODO: edit summary
				AliasesEditSummary::newAddSummary( '', new AliasGroupList() )
			)
		);

		return new AddItemAliasesResponse(
			$newRevision->getItem()->getAliases()[ $languageCode ],
			$newRevision->getLastModified(),
			$newRevision->getRevisionId(),
			$aliasesExist
		);
	}

}
