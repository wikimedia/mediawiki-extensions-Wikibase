( function() {
'use strict';

var PARENT = require( './Group.js' ),
	StatementList = require( './StatementList.js' );

/**
 * List of Statement objects, constrained to a single property id.
 * @class StatementGroup
 * @extends Group
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {string} propertyId
 * @param {StatementList} [statementList=new StatementList()]
 */
module.exports = util.inherit(
	'WbDataModelStatementGroup',
	PARENT,
	function WbDataModelStatementGroup( propertyId, statementList ) {
		PARENT.call( this, propertyId, StatementList, 'getPropertyIds', statementList );
	}
);

}() );
