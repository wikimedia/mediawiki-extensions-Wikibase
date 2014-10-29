/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb ) {
'use strict';

var PARENT = wb.datamodel.Group;

/**
 * Ordered list of Statement objects, each featuring the same property id.
 * @constructor
 * @since 1.0
 *
 * @param {wikibase.datamodel.StatementList} [statementList]
 */
wb.datamodel.StatementGroup = util.inherit(
	'WbDataModelStatementGroup',
	PARENT,
	function( propertyId, statementList ) {
		PARENT.call( this, propertyId, wb.datamodel.StatementList, 'getPropertyIds', statementList );
	}
);

}( wikibase ) );
