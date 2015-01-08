( function( $, mw ) {
	'use strict';

/**
 * @ignore
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	id: 'claimlistview-statementview',
	selector: ':' + $.wikibase.claimlistview.prototype.namespace
		+ '-' + $.wikibase.claimlistview.prototype.widgetName,
	events: {
		claimlistviewcreate: function( event, toolbarcontroller ) {
			var $claimlistview = $( event.target ),
				claimlistview = $claimlistview.data( 'claimlistview' ),
				$container = $claimlistview.children( '.wikibase-toolbar-wrapper' )
					.children( '.wikibase-toolbar-container' );

			if( !$container.length ) {
				// TODO: Remove layout-specific toolbar wrapper
				$container = $( '<div/>' ).appendTo(
					mw.wbTemplate( 'wikibase-toolbar-wrapper', '' ).appendTo( $claimlistview )
				);
			}

			if( !claimlistview.value() ) {
				return;
			}

			$claimlistview.addtoolbar( {
				$container: $container
			} )
			.on( 'addtoolbaradd.addtoolbar', function( e ) {
				if( e.target !== $claimlistview.get( 0 ) ) {
					return;
				}

				claimlistview.enterNewItem().done( function( $view ) {
					$view.one( 'statementviewafterstartediting.addtoolbar', function() {
						var listview = claimlistview.$listview.data( 'listview' ),
							lia = listview.listItemAdapter();
						lia.liInstance( $view ).focus();
					} );
				} );

				// Re-focus "add" button after having added or having cancelled adding a statement:
				var eventName = 'claimlistviewafterstopediting.addtoolbar';
				$claimlistview.one( eventName, function( event ) {
					$claimlistview.data( 'addtoolbar' ).focus();
				} );

				toolbarcontroller.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'claimlistviewdestroy',
					function( event, toolbarcontroller ) {
						toolbarcontroller.destroyToolbar( $( event.target ).data( 'addtoolbar' ) );
					}
				);
			} );

			toolbarcontroller.registerEventHandler(
				event.data.toolbar.type,
				event.data.toolbar.id,
				'claimlistviewdisable',
				function() {
					var addtoolbar = $claimlistview.data( 'addtoolbar' );
					if( addtoolbar ) {
						addtoolbar[claimlistview.option( 'disabled' ) ? 'disable' : 'enable']();
					}
				}
			);
		}
	}
} );

}( jQuery, mediaWiki ) );
