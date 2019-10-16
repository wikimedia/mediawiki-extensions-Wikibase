( function() {
'use strict';

var PARENT = require( './Set.js' ),
	StatementGroup = require( './StatementGroup.js' );

/**
 * Set of StatementGroup objects.
 * @class StatementGroupSet
 * @extends Set
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {StatementGroup[]} [statementGroups=[]]
 */
module.exports = util.inherit(
	'WbDataModelStatementGroupSet',
	PARENT,
	function( statementGroups ) {
		PARENT.call( this, StatementGroup, 'getKey', statementGroups );
	}
);

}() );
