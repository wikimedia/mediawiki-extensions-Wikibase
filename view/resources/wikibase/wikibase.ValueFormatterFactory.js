( function( util, wb ) {

'use strict';

var SELF = wb.ValueFormatterFactory = function() {
};

/**
 * Returns a ValueFormatter instance for the given DataType or DataValue type
 *
 * @param {string} dataTypeId
 * @param {string} dataValueType
 * @return {valueFormatters.ValueFormatter}
 */
SELF.prototype.getFormatter = util.abstractMember;

}( util, wikibase ) );
