( function( util, wb ) {

'use strict';

var SELF = wb.ValueFormatterFactory = function() {
};

/**
 * Returns a ValueFormatter instance for the given DataType id and output type
 *
 * @param {string|null} dataTypeId
 * @param {string} outputType
 * @return {valueFormatters.ValueFormatter}
 */
SELF.prototype.getFormatter = util.abstractMember;

}( util, wikibase ) );
