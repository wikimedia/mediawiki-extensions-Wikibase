<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class AssertItemExists {

	private GetLatestItemRevisionMetadata $getLatestItemRevisionMetadata;

	public function __construct( GetLatestItemRevisionMetadata $getLatestItemRevisionMetadata ) {
		$this->getLatestItemRevisionMetadata = $getLatestItemRevisionMetadata;
	}

	/**
	 * @throws ItemRedirect if the item is a redirect
	 * @throws UseCaseError if the item does not exist
	 */
	public function execute( ItemId $id ): void {
		$this->getLatestItemRevisionMetadata->execute( $id );
	}

}
