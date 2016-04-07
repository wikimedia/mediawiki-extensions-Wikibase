( function( wb ) {
'use strict';

var PARENT = wb.datamodel.Set;

/**
 * Set of StatementGroup objects.
 * @class wikibase.datamodel.StatementGroupSet
 * @extends wikibase.datamodel.Set
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {wikibase.datamodel.StatementGroup[]} [statementGroups=[]]
 */
wb.datamodel.StatementGroupSet = util.inherit(
	'WbDataModelStatementGroupSet',
	PARENT,
	function( statementGroups ) {
		PARENT.call( this, wb.datamodel.StatementGroup, 'getKey', statementGroups );
	}
);

}( wikibase ) );
