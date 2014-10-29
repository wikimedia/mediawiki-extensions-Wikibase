/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb ) {
'use strict';

var PARENT = wb.datamodel.List;

/**
 * @constructor
 * @since 1.0
 *
 * @param {wikibase.datamodel.Reference[]} [references]
 */
wb.datamodel.ReferenceList = util.inherit(
	'wbDataModelReferenceList',
	PARENT,
	function( references ) {
		PARENT.call( this, wikibase.datamodel.Reference, references );
	}
);

}( wikibase ) );
