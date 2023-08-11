<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use Wikibase\DataModel\Entity\NumericPropertyId;

/**
 * @license GPL-2.0-or-later
 */
class AssertPropertyExists {

	private GetLatestPropertyRevisionMetadata $getLatestPropertyRevisionMetadata;

	public function __construct( GetLatestPropertyRevisionMetadata $getLatestPropertyRevisionMetadata ) {
		$this->getLatestPropertyRevisionMetadata = $getLatestPropertyRevisionMetadata;
	}

	/**
	 * @throws UseCaseError if the property does not exist
	 */
	public function execute( NumericPropertyId $id ): void {
		$this->getLatestPropertyRevisionMetadata->execute( $id );
	}

}
