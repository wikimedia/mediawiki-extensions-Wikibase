wikibase.view.ReadModeViewFactory = ( function ( wb ) {
	'use strict';

	var PARENT = wb.view.ViewFactory;

	var SELF = util.inherit( 'ReadModeViewFactory', PARENT, {} );

	SELF.prototype.getSitelinkGroupListView = function ( sitelinkSet, $sitelinkgrouplistview ) {
		/* Skip constructing sitelink views entirely */
	};

	SELF.prototype.getAdderWithStartEditing = function () {
		return function () {
			return null;
		};
	};

	return SELF;

}( wikibase ) );
