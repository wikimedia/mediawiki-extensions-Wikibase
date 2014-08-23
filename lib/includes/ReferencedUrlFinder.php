<?php

namespace Wikibase;

use DataValues\DataValue;
use DataValues\StringValue;

/**
 * Finds URLs given a list of snaks.
 *
 * If a snaks property is not found or the type of DataValue
 * does not match the expected one for URLs, the snak is ignored
 * silently.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ReferencedUrlFinder extends ReferencedFinder {

	/**
	 * @see ReferencedFinder::getDataType
	 */
	protected function getDataType() {
		return 'url';
	}

	/**
	 * @see ReferencedFinder::getValueForDataValue
	 */
	protected function getValueForDataValue( DataValue $dataValue ) {
		if ( $dataValue instanceof StringValue ) {
			return $dataValue->getValue();
		}

		return null;
	}

}
