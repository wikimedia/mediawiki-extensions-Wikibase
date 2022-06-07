<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\AddItemStatement;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class AddItemStatement {

	private $validator;
	private $revisionMetadataRetriever;
	private $itemRetriever;
	private $itemUpdater;
	private $guidGenerator;

	public function __construct(
		AddItemStatementValidator $validator,
		ItemRevisionMetadataRetriever $revisionMetadataRetriever,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater,
		GuidGenerator $guidGenerator
	) {
		$this->validator = $validator;
		$this->revisionMetadataRetriever = $revisionMetadataRetriever;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->guidGenerator = $guidGenerator;
	}

	public function execute( AddItemStatementRequest $request ): AddItemStatementSuccessResponse {
		$this->validator->validate( $request ); // TODO T309847
		$statement = $this->validator->getValidatedStatement();
		$itemId = new ItemId( $request->getItemId() );

		$latestRevision = $this->revisionMetadataRetriever->getLatestRevisionMetadata( $itemId );
		if ( !$latestRevision->itemExists() || $latestRevision->isRedirect() ) {
			throw new \Exception(); // TODO T309852
		}

		$newStatementGuid = $this->guidGenerator->newGuid( $itemId );
		$statement->setGuid( $newStatementGuid );

		$item = $this->itemRetriever->getItem( $itemId );
		$item->getStatements()->addStatement( $statement );

		$newRevision = $this->itemUpdater->update( $item, new EditMetadata( $request->getEditTags(), $request->isBot() ) );

		return new AddItemStatementSuccessResponse(
			$newRevision->getItem()->getStatements()->getFirstStatementWithGuid( $newStatementGuid ),
			$newRevision->getLastModified(),
			$newRevision->getRevisionId()
		);
	}

}
