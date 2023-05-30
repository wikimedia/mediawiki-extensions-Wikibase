<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\ItemAliasesInLanguageRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemAliasesInLanguage {

	private GetLatestItemRevisionMetadata $getLatestRevisionMetadata;
	private ItemAliasesInLanguageRetriever $itemAliasesInLanguageRetriever;
	private GetItemAliasesInLanguageValidator $validator;

	public function __construct(
		GetLatestItemRevisionMetadata $getLatestRevisionMetadata,
		ItemAliasesInLanguageRetriever $itemAliasesInLanguageRetriever,
		GetItemAliasesInLanguageValidator $validator
	) {
		$this->getLatestRevisionMetadata = $getLatestRevisionMetadata;
		$this->itemAliasesInLanguageRetriever = $itemAliasesInLanguageRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 *
	 * @throws ItemRedirect
	 */
	public function execute( GetItemAliasesInLanguageRequest $request ): GetItemAliasesInLanguageResponse {
		$this->validator->assertValidRequest( $request );

		$itemId = new ItemId( $request->getItemId() );

		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $itemId );

		$aliases = $this->itemAliasesInLanguageRetriever->getAliasesInLanguage( $itemId, $request->getLanguageCode() );

		if ( !$aliases ) {
			throw new UseCaseError(
				UseCaseError::ALIASES_NOT_DEFINED,
				"Item with the ID {$request->getItemId()} does not have aliases in the language: {$request->getLanguageCode()}"
			);
		}

		return new GetItemAliasesInLanguageResponse(
			$aliases,
			$lastModified,
			$revisionId,
		);
	}
}
