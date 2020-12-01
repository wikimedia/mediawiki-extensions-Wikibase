<?php

namespace Wikibase\Repo;

use ValueValidators\ValueValidator;

/**
 * A factory providing ValueValidators based on DataType id.
 *
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @license GPL-2.0-or-later
 */
interface DataTypeValidatorFactory {

	/**
	 * Returns the validators associated with the given $dataTypeId.
	 * For unknown $dataTypeIds, an empty array is returned.
	 *
	 * @param string $dataTypeId
	 *
	 * @return ValueValidator[]
	 */
	public function getValidators( $dataTypeId );

}
