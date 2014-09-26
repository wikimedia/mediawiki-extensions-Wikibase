/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb ) {
'use strict';

var PARENT = wb.datamodel.List;

/**
 * Ordered set of Reference objects.
 * @constructor
 * @since 0.4
 *
 * @param {wikibase.datamodel.Reference[]} [references]
 */
wb.datamodel.ReferenceList = util.inherit(
	'wbReferenceList',
	PARENT,
	function( references ) {
		PARENT.call( this, wikibase.datamodel.Reference, references );
	}
);

}( wikibase ) );
