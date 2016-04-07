( function( wb ) {
'use strict';

var PARENT = wb.datamodel.List;

/**
 * List of Reference objects.
 * @class wikibase.datamodel.ReferenceList
 * @extends wikibase.datamodel.List
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {wikibase.datamodel.Reference[]} [references=[]]
 */
wb.datamodel.ReferenceList = util.inherit(
	'wbDataModelReferenceList',
	PARENT,
	function( references ) {
		PARENT.call( this, wikibase.datamodel.Reference, references );
	}
);

}( wikibase ) );
