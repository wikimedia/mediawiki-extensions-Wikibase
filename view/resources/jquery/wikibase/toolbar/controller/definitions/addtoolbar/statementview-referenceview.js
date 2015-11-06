( function( $, mw ) {
	'use strict';

/**
 * @ignore
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	id: 'statementview-referenceview',
	selector: '.wikibase-statementview-references',
	events: {
		listviewcreate: function( event, toolbarController ) {
			var $listview = $( event.target ),
				listview = $listview.data( 'listview' ),
				lia = listview.listItemAdapter(),
				$node = $listview.parent();

			if ( !$node.hasClass( 'wikibase-statementview-references' ) ) {
				return;
			}

			$node
			.addtoolbar( {
				$container: $( '<div/>' ).appendTo( $node ),
				label: mw.msg( 'wikibase-addreference' )
			} )
			.on( 'addtoolbaradd.addtoolbar', function( e ) {
				if ( e.target !== $node.get( 0 ) ) {
					return;
				}

				$listview.closest( '.wikibase-statementview' )
					.data( 'statementview' ).startEditing();

				listview.enterNewItem().done( function( $referenceview ) {
					var referenceview = lia.liInstance( $referenceview );
					referenceview.focus();
				} );

				// Re-focus "add" button after having added or having cancelled adding a reference:
				var eventName = lia.prefixedEvent( 'afterstopediting.addtoolbar' );
				$listview.one( eventName, function( event ) {
					$node.data( 'addtoolbar' ).focus();
				} );

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'listviewdestroy',
					function( event, toolbarController ) {
						var $listview = $( event.target ),
							$node = $listview.parent();

						if ( !$node.hasClass( '.wikibase-statementview-references' ) ) {
							return;
						}

						toolbarController.destroyToolbar( $node.data( 'addtoolbar' ) );
						$node.off( 'addtoolbar' );
					}
				);
			} );

			toolbarController.registerEventHandler(
				event.data.toolbar.type,
				event.data.toolbar.id,
				'listviewdisable',
				function( event ) {
					if ( event.target !== $listview.get( 0 ) ) {
						return;
					}
					$node.data( 'addtoolbar' )[
						listview.option( 'disabled' )
							? 'disable'
							: 'enable'
					]();
				}
			);
		}
	}
} );

}( jQuery, mediaWiki ) );
