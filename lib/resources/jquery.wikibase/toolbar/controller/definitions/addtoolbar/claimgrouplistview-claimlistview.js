( function( $ ) {
	'use strict';

/**
 * @ignore
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	id: 'claimgrouplistview-claimlistview',
	selector: ':' + $.wikibase.claimgrouplistview.prototype.namespace
		+ '-' + $.wikibase.claimgrouplistview.prototype.widgetName,
	events: {
		claimgrouplistviewcreate: function( event, toolbarcontroller ) {
			var $claimgrouplistview = $( event.target ),
				claimgrouplistview = $claimgrouplistview.data( 'claimgrouplistview' );

			$claimgrouplistview.addtoolbar( {
				$container: $( '<div/>' ).appendTo( $claimgrouplistview )
			} )
			.on( 'addtoolbaradd.addtoolbar', function( e ) {
				if( e.target !== $claimgrouplistview.get( 0 ) ) {
					return;
				}

				claimgrouplistview.enterNewItem().done( function( $claimlistview ) {
					var claimlistview = $claimlistview.data( 'claimlistview' ),
						listview = claimlistview.$listview.data( 'listview' );
					listview.listItemAdapter().liInstance( listview.items() ).focus();
				} );

				toolbarcontroller.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'claimgrouplistviewdestroy',
					function( event, toolbarController ) {
						toolbarController.destroyToolbar( $( event.target ).data( 'addtoolbar' ) );
					}
				);
			} );

			// TODO: Integrate state management into addtoolbar
			toolbarcontroller.registerEventHandler(
				event.data.toolbar.type,
				event.data.toolbar.id,
				'claimgrouplistviewdisable',
				function() {
					$claimgrouplistview.data( 'addtoolbar' )[
						claimgrouplistview.option( 'disabled' )
						? 'disable'
						: 'enable'
					]();
				}
			);
		}
	}
} );

}( jQuery ) );
