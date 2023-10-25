<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddItemAliases;

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
	private AddItemAliasesValidator $validator;

	public function __construct(
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater,
		AddItemAliasesValidator $validator
	) {
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( AddItemAliasesRequest $request ): AddItemAliasesResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );

		$itemId = $deserializedRequest->getItemId();
		$languageCode = $deserializedRequest->getLanguageCode();
		$newAliases = $deserializedRequest->getItemAliases();

		// TODO: existence check
		// TODO: deserialize edit metadata
		// TODO: assert user is authorized

		$item = $this->itemRetriever->getItem( $itemId );
		$aliasesExist = $item->getAliasGroups()->hasGroupForLanguage( $languageCode );
		$originalAliases = $aliasesExist ? $item->getAliasGroups()->getByLanguage( $languageCode )->getAliases() : [];

		$item->getAliasGroups()->setAliasesForLanguage( $languageCode, $this->addAliases( $originalAliases, $newAliases ) );
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

	private function addAliases( array $originalAliases, array $newAliases ): array {
		$duplicates = array_intersect( $newAliases, $originalAliases );
		if ( !empty( $duplicates ) ) {
			throw new UseCaseError(
				UseCaseError::ALIAS_DUPLICATE,
				"Alias list contains a duplicate alias: '${duplicates[0]}'",
				[ UseCaseError::CONTEXT_ALIAS => $duplicates[0] ]
			);
		}

		return array_merge( $originalAliases, $newAliases );
	}

}
