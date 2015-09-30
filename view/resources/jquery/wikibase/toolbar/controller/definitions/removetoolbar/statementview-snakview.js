( function( $ ) {
	'use strict';

/**
 * @ignore
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'removetoolbar', {
	id: 'statementview-snakview',
	selector: '.wikibase-statementview-qualifiers',
	events: {
		snakviewafterstartediting: function( event, toolbarController ) {
			var $snakview = $( event.target ),
				$snaklistview = $snakview.closest( '.wikibase-snaklistview' ),
				snaklistview = $snaklistview.data( 'snaklistview' );

			if ( !snaklistview ) {
				return;
			}

			var qualifierPorpertyGroupListview = snaklistview._listview;

			// Create toolbar for each snakview widget:
			$snakview
			.removetoolbar( {
				$container: $( '<div/>' ).appendTo( $snakview )
			} )
			.on( 'removetoolbarremove.removetoolbar', function( event ) {
				if ( event.target === $snakview.get( 0 ) ) {
					qualifierPorpertyGroupListview.removeItem( $snakview );
				}
			} );

			toolbarController.registerEventHandler(
				event.data.toolbar.type,
				event.data.toolbar.id,
				'snaklistviewafterstopediting',
				function( event, toolbarcontroller ) {
					// Destroy the snakview toolbars:
					var $snaklistviewNode = $( event.target ),
						listview = $snaklistviewNode.data( 'snaklistview' )._listview,
						lia = listview.listItemAdapter();

					$.each( listview.items(), function( i, item ) {
						var snakview = lia.liInstance( $( item ) );
						toolbarcontroller.destroyToolbar(
							snakview.element.data( 'removetoolbar' )
						);
					} );
				}
			);

			toolbarController.registerEventHandler(
				event.data.toolbar.type,
				event.data.toolbar.id,
				'snaklistviewdisable',
				function( event ) {
					var $snaklistviewNode = $( event.target ),
						listview = $snaklistviewNode.data( 'snaklistview' )._listview,
						lia = listview.listItemAdapter(),
						$statementview = $snaklistviewNode.closest( ':wikibase-statementview' ),
						statementview = $statementview.data( 'statementview' );

					$.each( listview.items(), function( i, node ) {
						var $snakview = $( node ),
							snakview = lia.liInstance( $snakview ),
							removeToolbar = $snakview.data( 'removetoolbar' );

						// Item might be about to be removed not being a list item instance.
						if ( !snakview || !removeToolbar ) {
							return;
						}

						$snakview.data( 'removetoolbar' )[statementview.option( 'disabled' )
							? 'disable'
							: 'enable'
						]();
					} );
				}
			);

		}
	}
} );

}( jQuery ) );
