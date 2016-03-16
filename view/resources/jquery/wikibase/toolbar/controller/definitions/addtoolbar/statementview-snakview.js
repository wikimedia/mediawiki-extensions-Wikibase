( function( $, mw ) {
	'use strict';

/**
 * @ignore
 *
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	id: 'statementview-snakview',
	selector: '.wikibase-statementview-qualifiers',
	events: {
		'listviewcreate snaklistviewafterstartediting': function( event, toolbarController ) {
			var $target = $( event.target ),
				$statementview = $target.closest( '.wikibase-statementview' ),
				$qualifiers = $target.closest( '.wikibase-statementview-qualifiers' ),
				listview = $target.closest( ':wikibase-listview' ).data( 'listview' ),
				listviewInited = event.type === 'listviewcreate' && listview.items().length === 0;

			if ( ( listviewInited || event.type === 'snaklistviewafterstartediting' )
				&& !$qualifiers.data( 'addtoolbar' )
				&& $statementview.data( 'statementview' ).isInEditMode()
			) {
				$qualifiers
				.addtoolbar( {
					$container: $( '<div/>' ).appendTo( $qualifiers ),
					label: mw.msg( 'wikibase-addqualifier' )
				} )
				.off( '.addtoolbar' )
				.on( 'addtoolbaradd.addtoolbar', function( e ) {
					listview.enterNewItem();

					var snaklistview = listview.value()[listview.value().length - 1];
					snaklistview.enterNewItem().done( function() {
						snaklistview.focus();
					} );
				} );

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'listviewdestroy snaklistviewafterstopediting',
					function( event, toolbarcontroller ) {
						var $target = $( event.target ),
							$qualifiers = $target.closest( '.wikibase-statementview-qualifiers' );

						if ( $target.parent().get( 0 ) !== $qualifiers.get( 0 ) ) {
							// Not the qualifiers main listview.
							return;
						}

						toolbarcontroller.destroyToolbar( $qualifiers.data( 'addtoolbar' ) );
					}
				);

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'snaklistviewchange',
					function( event ) {
						var $target = $( event.target ),
							$qualifiers = $target.closest( '.wikibase-statementview-qualifiers' ),
							addToolbar = $qualifiers.data( 'addtoolbar' ),
							$listview = $target.closest( ':wikibase-listview' ),
							snaklistviews = $listview.data( 'listview' ).value();

						if ( addToolbar ) {
							addToolbar.enable();
							for ( var i = 0; i < snaklistviews.length; i++ ) {
								if ( !snaklistviews[i].isValid() ) {
									addToolbar.disable();
									break;
								}
							}
						}
					}
				);

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					// FIXME: When there are qualifiers, no state change events will be thrown.
					'listviewdisable',
					function( event ) {
						var $qualifiers = $( event.target )
								.closest( '.wikibase-statementview-qualifiers' ),
							addToolbar = $qualifiers.data( 'addtoolbar' ),
							$statementview = $qualifiers.closest( ':wikibase-statementview' ),
							statementview = $statementview.data( 'statementview' );

						// Toolbar might be removed from the DOM already after having stopped edit
						// mode.
						if ( addToolbar ) {
							addToolbar[statementview.option( 'disabled' ) ? 'disable' : 'enable']();
						}
					}
				);

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'listviewitemadded listviewitemremoved',
					function( event ) {
						// Enable "add" link when all qualifiers have been removed:
						var $listviewNode = $( event.target ),
							listview = $listviewNode.data( 'listview' ),
							$snaklistviewNode = $listviewNode.closest( '.wikibase-snaklistview' ),
							snaklistview = $snaklistviewNode.data( 'snaklistview' ),
							addToolbar = $snaklistviewNode.data( 'addtoolbar' );

						// Toolbar is not within the DOM when (re-)constructing the list in
						// non-edit-mode.
						if ( !addToolbar ) {
							return;
						}

						// Disable "add" toolbar when the last qualifier has been removed:
						if ( !snaklistview.isValid() && listview.items().length ) {
							addToolbar.disable();
						} else {
							addToolbar.enable();
						}
					}
				);

			}
		}
	}
} );

}( jQuery, mediaWiki ) );
