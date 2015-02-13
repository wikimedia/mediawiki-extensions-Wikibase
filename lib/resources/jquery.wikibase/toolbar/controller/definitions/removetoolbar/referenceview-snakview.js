( function( $ ) {
	'use strict';

/**
 * @ignore
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'removetoolbar', {
	id: 'referenceview-snakview',
	selector: '.wikibase-statementview-references .wikibase-referenceview',
	events: {
		'snakviewafterstartediting snakviewchange': function( event, toolbarcontroller ) {
			var $snakview = $( event.target ),
				$referenceview = $snakview.closest( ':wikibase-referenceview' ),
				referenceview = $referenceview.data( 'referenceview' );

			if( !referenceview ) {
				return;
			}

			if ( event.type === 'snakviewafterstartediting' ) {
				var $snaklistview = $snakview.closest( ':wikibase-snaklistview' ),
					snaklistview = $snaklistview.data( 'snaklistview' ),
					snakviewPropertyGroupListview = snaklistview._listview;

				$snakview.removetoolbar( {
					$container: $( '<div/>' ).appendTo( $snakview )
				} )
				.on( 'removetoolbarremove.removetoolbar', function( event ) {
					if( event.target === $snakview[0] ) {
						snakviewPropertyGroupListview.removeItem( $snakview );
					}
				} );

				toolbarcontroller.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'referenceviewafterstopediting',
					function( event, toolbarcontroller ) {
						// Destroy the snakview toolbars:
						var $referenceviewNode = $( event.target );
						$.each(
							$referenceviewNode.find( '.wikibase-snakview' ),
							function( i, snakviewNode ) {
								toolbarcontroller.destroyToolbar(
									$( snakviewNode ).data( 'removetoolbar' )
								);
							}
						);
					}
				);

				toolbarcontroller.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'referenceviewdisable listviewitemremoved',
					function( event ) {
						var $referenceview = event.type.indexOf( 'referenceview' ) !== -1
							? $( event.target )
							: $( event.target ).closest( ':wikibase-referenceview' );

						var referenceview = $referenceview.data( 'referenceview' );

						if( !referenceview ) {
							return;
						}

						var listview = referenceview.$listview.data( 'listview' ),
							lia = listview.listItemAdapter(),
							$snaklistviews = listview.items();

						for( var i = 0; i < $snaklistviews.length; i++ ) {
							var snaklistview = lia.liInstance( $snaklistviews.eq( i ) );

							// Item might be about to be removed not being a list item instance.
							if( snaklistview ) {
								var $snakviews = snaklistview._listview.items();

								for( var j = 0; j < $snakviews.length; j++ ) {
									var $snakview = $snakviews.eq( j ),
										removetoolbar = $snakview.data( 'removetoolbar' );

									if( removetoolbar ) {
										removetoolbar[
											referenceview.option( 'disabled' )
											|| $snakviews.length === 1 && $snaklistviews.length === 1
												? 'disable'
												: 'enable'
										]();
									}
								}
							}
						}
					}
				);

			}

			var listview = referenceview.$listview.data( 'listview' ),
				lia = listview.listItemAdapter();

			// If there is only one snakview widget, disable its "remove" link:
			if( listview.items().length === 0 ) {
				return;
			}

			var $snaklistviews = listview.items(),
				$firstSnaklistview = $snaklistviews.first(),
				firstSnaklistview = lia.liInstance( $firstSnaklistview ),
				$firstSnakview = firstSnaklistview.$listview.data( 'listview' ).items().first(),
				removetoolbar = $firstSnakview.data( 'removetoolbar' ),
				numberOfSnakviews = 0;

			for( var i = 0; i < $snaklistviews.length; i++ ) {
				var snaklistviewWidget = lia.liInstance( $snaklistviews.eq( i ) ),
					snaklistviewListview = snaklistviewWidget._listview,
					snaklistviewListviewLia = snaklistviewListview.listItemAdapter(),
					$snakviews = snaklistviewWidget._listview.items();

				for( var j = 0; j < $snakviews.length; j++ ) {
					var snakview = snaklistviewListviewLia.liInstance( $snakviews.eq( j ) );
					if( snakview.snak() ) {
						numberOfSnakviews++;
					}
				}
			}

			if( removetoolbar ) {
				removetoolbar[ numberOfSnakviews > 1 ? 'enable' : 'disable' ]();
			}
		}
	}
} );

}( jQuery ) );
