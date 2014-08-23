<?php

namespace Wikibase;

use DataValues\DataValue;
use DataValues\StringValue;

/**
 * Find file inclusion of images given a list of snaks.
 *
 * If a snaks property is not found or the type of DataValue
 * does not match the expected one for Commons media files, the snak is ignored
 * silently.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ReferencedImageFinder extends ReferencedFinder {

	/**
	 * @see ReferencedFinder::getDataType
	 */
	protected function getDataType() {
		return 'commonsMedia';
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
