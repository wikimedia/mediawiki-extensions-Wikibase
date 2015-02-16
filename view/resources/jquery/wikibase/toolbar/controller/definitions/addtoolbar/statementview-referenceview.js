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
	selector: ':' + $.wikibase.statementview.prototype.namespace
		+ '-' + $.wikibase.statementview.prototype.widgetName,
	events: {
		'statementviewafterstartediting listviewcreate': function( event, toolbarcontroller ) {
			var $statementview,
				statementview,
				$listview;

			if( event.type.indexOf( 'statementview' ) === 0 ) {
				$statementview = $( event.target );
				statementview = $statementview.data( 'statementview' );
				$listview = statementview.$references.children( ':wikibase-listview' );
			} else {
				$listview = $( event.target );
				$statementview = $listview.closest( ':wikibase-statementview' );
				statementview = $statementview.data( 'statementview' );

				if( !statementview.isInEditMode()
					|| $listview.parent()[0] !== statementview.$references
				) {
					return;
				}
			}

			var listview = $listview.data( 'listview' ),
				lia = listview.listItemAdapter();

			statementview.$references
			.addtoolbar( {
				$container: $( '<div/>' ).appendTo( statementview.$references ),
				label: mw.msg( 'wikibase-addreference' )
			} )
			.on( 'addtoolbaradd.addtoolbar', function( event ) {
				if( event.target !== statementview.$references[0] ) {
					return;
				}

				listview.enterNewItem().done( function( $referenceview ) {
					var referenceview = lia.liInstance( $referenceview );
					referenceview.focus();
				} );

				// Re-focus "add" button after having added or having cancelled adding a reference:
				var eventName = lia.prefixedEvent( 'afterstopediting.addtoolbar' );
				$listview.one( eventName, function( event ) {
					statementview.$references.data( 'addtoolbar' ).focus();
				} );
			} );
		},
		'statementviewafterstopediting listviewdestroy': function( event, toolbarcontroller ) {
			var $statementview,
				statementview;

			if( event.type.indexOf( 'statementview' ) === 0 ) {
				$statementview = $( event.target );
				statementview = $statementview.data( 'statementview' );
			} else {
				var $listview = $( event.target );
				$statementview = $listview.closest( ':wikibase-statementview' );
				statementview = $statementview.data( 'statementview' );

				if( $listview.parent()[0] !== statementview.$references ) {
					return;
				}
			}

			var addtoolbar = statementview.$references.data( 'addtoolbar' );

			if( addtoolbar ) {
				toolbarcontroller.destroyToolbar( addtoolbar );
			}
			statementview.$references.off( '.addtoolbar' );
		},
		listviewdisable: function( event ) {
			var $listview = $( event.target ),
				$statementview = $listview.closest( ':wikibase-statementview' ),
				statementview = $statementview.data( 'statementview' );

			if( $listview.parent()[0] !== statementview.$references[0] ) {
				return;
			}

			var listview = $listview.data( 'listview' ),
				addtoolbar = statementview.$references.data( 'addtoolbar' );

			if( addtoolbar ) {
				addtoolbar.option( 'disabled', listview.option( 'disabled' ) );
			}
		}
	}
} );

}( jQuery, mediaWiki ) );
