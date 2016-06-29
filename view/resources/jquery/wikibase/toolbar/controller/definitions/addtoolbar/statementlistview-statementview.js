( function( $, mw ) {
	'use strict';

/**
 * @ignore
 *
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	id: 'statementlistview-statementview',
	selector: ':' + $.wikibase.statementlistview.prototype.namespace
		+ '-' + $.wikibase.statementlistview.prototype.widgetName,
	events: {
		statementlistviewcreate: function( event, toolbarcontroller ) {
			var $statementlistview = $( event.target ),
				statementlistview = $statementlistview.data( 'statementlistview' ),
				$container = $statementlistview.children( '.wikibase-toolbar-wrapper' )
					.children( '.wikibase-toolbar-container' );

			if ( !$container.length ) {
				// TODO: Remove layout-specific toolbar wrapper
				$container = $( '<div/>' ).appendTo(
					mw.wbTemplate( 'wikibase-toolbar-wrapper', '' ).appendTo( $statementlistview )
				);
			}

			$statementlistview.addtoolbar( {
				$container: $container
			} )
			.on( 'addtoolbaradd.addtoolbar', function( e ) {
				if ( e.target !== $statementlistview.get( 0 ) ) {
					return;
				}

				statementlistview.enterNewItem().done( function( $view ) {
					$view.one( 'statementviewafterstartediting.addtoolbar', function() {
						var listview = statementlistview.$listview.data( 'listview' ),
							lia = listview.listItemAdapter();
						lia.liInstance( $view ).focus();
					} );
				} );

				// Re-focus "add" button after having added or having cancelled adding a statement:
				var eventName = 'statementlistviewafterstopediting.addtoolbar';
				$statementlistview.one( eventName, function( event ) {
					$statementlistview.data( 'addtoolbar' ).focus();
				} );

				toolbarcontroller.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'statementlistviewdestroy',
					function( event, toolbarcontroller ) {
						toolbarcontroller.destroyToolbar( $( event.target ).data( 'addtoolbar' ) );
					}
				);
			} );

			toolbarcontroller.registerEventHandler(
				event.data.toolbar.type,
				event.data.toolbar.id,
				'statementlistviewdisable',
				function() {
					var addtoolbar = $statementlistview.data( 'addtoolbar' );
					if ( addtoolbar ) {
						addtoolbar[statementlistview.option( 'disabled' ) ? 'disable' : 'enable']();
					}
				}
			);
		}
	}
} );

}( jQuery, mediaWiki ) );
