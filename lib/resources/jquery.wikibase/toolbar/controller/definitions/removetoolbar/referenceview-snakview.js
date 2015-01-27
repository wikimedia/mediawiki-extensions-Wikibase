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
		'snakviewafterstartediting snakviewchange referenceviewitemremoved': function( event, toolbarController ) {
			var $target = $( event.target ),
				$referenceview = $target.closest( ':wikibase-referenceview' ),
				referenceview = $referenceview.data( 'referenceview' );

			if( !referenceview ) {
				return;
			}

			if ( event.type === 'snakviewafterstartediting' ) {
				var $snaklistview = $target.closest( ':wikibase-snaklistview' ),
					snaklistview = $snaklistview.data( 'snaklistview' ),
					snakviewPropertyGroupListview = snaklistview._listview;

				$target.removetoolbar( {
					$container: $( '<div/>' ).appendTo( $target )
				} )
				.on( 'removetoolbarremove.removetoolbar', function( event ) {
					if( event.target === $target.get( 0 ) ) {
						snakviewPropertyGroupListview.removeItem( $target );
					}
				} );

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'referenceviewafterstopediting',
					function( event, toolbarcontroller ) {
						// Destroy the snakview toolbars:
						var $referenceviewNode = $( event.target );
						$.each( $referenceviewNode.find( '.wikibase-snakview' ), function( i, snakviewNode ) {
							toolbarcontroller.destroyToolbar( $( snakviewNode ).data( 'removetoolbar' ) );
						} );
					}
				);

				toolbarController.registerEventHandler(
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

						var $snaklistviews = referenceview._listview.items(),
							lia = referenceview.options.listItemAdapter;

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

			// If there is only one snakview widget, disable its "remove" link:
			var $listview = referenceview.$listview,
				listview = $listview.data( 'listview' ),
				$snaklistviews = listview.items();

			if( !$snaklistviews.length ) {
				return;
			}

			var $firstSnaklistview = $snaklistviews.first(),
				referenceviewLia = referenceview.options.listItemAdapter,
				firstSnaklistview = referenceviewLia.liInstance( $firstSnaklistview ),
				$firstSnakview = firstSnaklistview.$listview.data( 'listview' ).items().first(),
				removetoolbar = $firstSnakview.data( 'removetoolbar' ),
				numberOfSnakviews = 0;

			for( var i = 0; i < $snaklistviews.length; i++ ) {
				var snaklistviewWidget = referenceviewLia.liInstance( $snaklistviews.eq( i ) ),
					snaklistviewListview = snaklistviewWidget.$listview.data( 'listview' ),
					snaklistviewListviewLia = snaklistviewListview.listItemAdapter(),
					$snakviews = snaklistviewListview.items();

				for( var j = 0; j < $snakviews.length; j++ ) {
					var snakview = snaklistviewListviewLia.liInstance( $snakviews.eq( j ) );
					if( snakview.snak() ) {
						numberOfSnakviews++;
					}
				}
			}

			if( removetoolbar ) {
				removetoolbar[
					( event.type === 'snakviewafterstartediting' && numberOfSnakviews > 0 || numberOfSnakviews > 1 )
						? 'enable'
						: 'disable'
				]();
			}
		}
	}
} );

}( jQuery ) );
