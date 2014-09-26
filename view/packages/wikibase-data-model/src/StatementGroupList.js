/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb ) {
'use strict';

var PARENT = wb.datamodel.Set;

/**
 * Unordered set of StatementGroup objects.
 * @constructor
 * @since 0.4
 *
 * @param {wikibase.datamodel.StatementGroup[]} [statementGroups]
 */
wb.datamodel.StatementGroupList = util.inherit(
	'wbStatementGroupList',
	PARENT,
	function( statementGroups ) {
		PARENT.call( this, wb.datamodel.StatementGroup, 'getKey', statementGroups );
	}
);

}( wikibase ) );
