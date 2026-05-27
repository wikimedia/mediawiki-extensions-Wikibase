<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\DataAccess;

use MediaWiki\Language\Language;
use MediaWiki\Request\WebRequest;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Repo\Api\CombinedEntitySearchHelper;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\Api\PropertyDataTypeSearchHelper;

/**
 * @license GPL-2.0-or-later
 */
class PropertySearchHelperFactory implements EntitySearchHelperFactory {

	public function __construct(
		private readonly EntitySearchHelperFactory $inner,
		private readonly PropertyDataTypeLookup $propertyDataTypeLookup,
		private readonly ?EntitySearchHelper $federatedPropertySearch,
	) {
	}

	public function newEntitySearchHelper( string $entityType, Language $language, WebRequest $request ): EntitySearchHelper {
		$helper = new PropertyDataTypeSearchHelper(
			$this->inner->newEntitySearchHelper( $entityType, $language, $request ),
			$this->propertyDataTypeLookup
		);
		if ( $this->federatedPropertySearch !== null ) {
			return new CombinedEntitySearchHelper( [ $helper, $this->federatedPropertySearch ] );
		}
		return $helper;
	}

}
