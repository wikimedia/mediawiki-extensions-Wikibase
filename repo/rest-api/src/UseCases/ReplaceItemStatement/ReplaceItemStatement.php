<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement;

use Exception;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatement {

	private $validator;
	private $revisionMetadataRetriever;
	private $itemRetriever;
	private $itemUpdater;

	public function __construct(
		ReplaceItemStatementValidator $validator,
		ItemRevisionMetadataRetriever $revisionMetadataRetriever,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater
	) {
		$this->validator = $validator;
		$this->revisionMetadataRetriever = $revisionMetadataRetriever;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
	}

	/**
	 * @throws Exception
	 */
	public function execute( ReplaceItemStatementRequest $request ): ReplaceItemStatementSuccessResponse {
		$this->validator->validate( $request ); // T313021: complete validation

		$requestedItemId = $request->getItemId();
		$statementIdParser = new StatementGuidParser( new ItemIdParser() );
		$statementGuid = $statementIdParser->parse( $request->getStatementId() );
		/** @var ItemId $itemId */
		$itemId = $requestedItemId ? new ItemId( $requestedItemId ) : $statementGuid->getEntityId();
		'@phan-var ItemId $itemId';

		$latestRevision = $this->revisionMetadataRetriever->getLatestRevisionMetadata( $itemId );
		if ( !$latestRevision->itemExists() || $latestRevision->isRedirect() ) {
			throw new \Exception(); // T313022: handle item not found and item redirect
		}

		$newStatement = $this->validator->getValidatedStatement();

		$item = $this->itemRetriever->getItem( $itemId );
		$item->getStatements()->replaceStatement( $statementGuid, $newStatement );

		$newRevision = $this->itemUpdater->update(
			$item,
			new EditMetadata( $request->getEditTags(), $request->isBot(), $request->getComment() )
		);

		return new ReplaceItemStatementSuccessResponse(
			$newRevision->getItem()->getStatements()->getFirstStatementWithGuid( (string)$statementGuid ),
			$newRevision->getLastModified(),
			$newRevision->getRevisionId()
		);
	}

}
