( function( wb ) {
'use strict';

var PARENT = wb.datamodel.Group;

/**
 * List of Statement objects, constrained to a single property id.
 * @class wikibase.datamodel.StatementGroup
 * @extends wikibase.datamodel.Group
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {string} propertyId
 * @param {wikibase.datamodel.StatementList} [statementList=new wikibase.datamodel.StatementList()]
 */
wb.datamodel.StatementGroup = util.inherit(
	'WbDataModelStatementGroup',
	PARENT,
	function WbDataModelStatementGroup( propertyId, statementList ) {
		PARENT.call( this, propertyId, wb.datamodel.StatementList, 'getPropertyIds', statementList );
	}
);

}( wikibase ) );
