( function() {
'use strict';

var PARENT = require( './List.js' ),
	Reference = require( './Reference.js' );

/**
 * List of Reference objects.
 * @class ReferenceList
 * @extends List
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Reference[]} [references=[]]
 */
module.exports = util.inherit(
	'WbDataModelReferenceList',
	PARENT,
	function( references ) {
		PARENT.call( this, Reference, references );
	}
);

}() );
