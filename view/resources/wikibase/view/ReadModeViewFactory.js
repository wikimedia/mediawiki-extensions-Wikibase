wikibase.view.ReadModeViewFactory = ( function( wb ) {
'use strict';

var PARENT = wb.view.ViewFactory;

var SELF = util.inherit( 'ReadModeViewFactory', PARENT, {} );

SELF.prototype.getStatementGroupListView = function( entity, $statementgrouplistview ) {
	/* Skip constructing statement views entirely */
};

return SELF;

}( wikibase ) );
