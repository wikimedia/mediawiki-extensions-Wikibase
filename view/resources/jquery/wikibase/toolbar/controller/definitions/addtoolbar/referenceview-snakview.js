( function( $ ) {
	'use strict';

/**
 * @ignore
 *
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	id: 'referenceview-snakview',
	selector: '.wikibase-statementview-references .wikibase-referenceview',
	events: {
		referenceviewafterstartediting: function( event, toolbarController ) {
			var $referenceview = $( event.target ),
				referenceview = $referenceview.data( 'referenceview' ),
				lia = referenceview.$listview.data( 'listview' ).listItemAdapter();

			$referenceview.addtoolbar( {
				$container: $( '<div/>' ).appendTo( $referenceview )
			} )
			.on( 'addtoolbaradd.addtoolbar', function() {
				$referenceview.data( 'referenceview' ).enterNewItem()
					.done( function( $snaklistview ) {
						lia.liInstance( $snaklistview ).focus();
					} );
			} );

			toolbarController.registerEventHandler(
				event.data.toolbar.type,
				event.data.toolbar.id,
				'referenceviewafterstopediting',
				function( event, toolbarController ) {
					var $referenceview = $( event.target );
					toolbarController.destroyToolbar( $referenceview.data( 'addtoolbar' ) );
					$referenceview.off( '.addtoolbar' );
				}
			);

			toolbarController.registerEventHandler(
				event.data.toolbar.type,
				event.data.toolbar.id,
				'referenceviewchange',
				function( event ) {
					var $referenceview = $( event.target ).closest( ':wikibase-referenceview' ),
						referenceview = $referenceview.data( 'referenceview' ),
						addToolbar = $referenceview.data( 'addtoolbar' );
					if ( addToolbar ) {
						addToolbar[referenceview.isValid() ? 'enable' : 'disable']();
					}
				}
			);

			toolbarController.registerEventHandler(
				event.data.toolbar.type,
				event.data.toolbar.id,
				'referenceviewdisable',
				function( event ) {
					var referenceview = $( event.target ).data( 'referenceview' ),
						addToolbar = $( event.target ).data( 'addtoolbar' );

					if ( addToolbar ) {
						addToolbar[referenceview.option( 'disabled' )
							? 'disable'
							: 'enable'
						]();
					}
				}
			);
		}
	}
} );

}( jQuery ) );
