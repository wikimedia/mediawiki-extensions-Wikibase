<?php

namespace Wikibase\Repo;

use ValueValidators\ValueValidator;

/**
 * A factory providing ValueValidators based on DataType id.
 *
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
interface DataTypeValidatorFactory {

	/**
	 *
	 * @param string $dataTypeId
	 *
	 * @return ValueValidator[]
	 */
	public function getValidators( $dataTypeId );

}
