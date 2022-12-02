<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Wikibase\Lib\DataTypeFactory;
use Wikibase\Repo\RestApi\Domain\Services\ValueTypeLookup;

/**
 * @license GPL-2.0-or-later
 */
class DataTypeFactoryValueTypeLookup implements ValueTypeLookup {

	private DataTypeFactory $dataTypeFactory;

	public function __construct( DataTypeFactory $dataTypeFactory ) {
		$this->dataTypeFactory = $dataTypeFactory;
	}

	public function getValueType( string $dataTypeId ): string {
		return $this->dataTypeFactory->getType( $dataTypeId )->getDataValueType();
	}

}
