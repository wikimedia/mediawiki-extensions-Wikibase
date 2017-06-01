( function ( util, wb ) {

	'use strict';

	var SELF = wb.ValueFormatterFactory = function () {
	};

	/**
	 * Returns a ValueFormatter instance for the given DataType ID or Property ID and output type.
	 *
	 * @param {string|null} dataTypeId
	 * @param {string|null} propertyId
	 * @param {string} outputType
	 * @return {valueFormatters.ValueFormatter}
	 */
	SELF.prototype.getFormatter = util.abstractMember;

}( util, wikibase ) );
