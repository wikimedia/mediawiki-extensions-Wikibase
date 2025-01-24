<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure;

use Wikibase\Lib\DataTypeFactory;
use Wikibase\Repo\Domains\Crud\Domain\Services\ValueTypeLookup;

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
