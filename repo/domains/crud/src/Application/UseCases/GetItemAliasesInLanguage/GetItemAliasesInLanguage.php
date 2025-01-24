<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemAliasesInLanguage;

use Wikibase\Repo\Domains\Crud\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ItemRedirect;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemAliasesInLanguageRetriever;

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
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();
		$languageCode = $deserializedRequest->getLanguageCode();

		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $itemId );

		$aliases = $this->itemAliasesInLanguageRetriever->getAliasesInLanguage( $itemId, $languageCode );
		if ( !$aliases ) {
			throw UseCaseError::newResourceNotFound( 'aliases' );
		}

		return new GetItemAliasesInLanguageResponse( $aliases, $lastModified, $revisionId );
	}

}
