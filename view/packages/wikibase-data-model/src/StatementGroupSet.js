/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb ) {
'use strict';

var PARENT = wb.datamodel.Set;

/**
 * @constructor
 * @since 1.0
 *
 * @param {wikibase.datamodel.StatementGroup[]} [statementGroups]
 */
wb.datamodel.StatementGroupSet = util.inherit(
	'WbDataModelStatementGroupSet',
	PARENT,
	function( statementGroups ) {
		PARENT.call( this, wb.datamodel.StatementGroup, 'getKey', statementGroups );
	}
);

}( wikibase ) );
