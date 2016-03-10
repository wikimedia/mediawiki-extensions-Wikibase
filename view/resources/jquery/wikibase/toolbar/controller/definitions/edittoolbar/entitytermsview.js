( function( $, mw ) {
	'use strict';

/**
 * @ignore
 *
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'entitytermsview',
	selector: ':' + $.wikibase.entitytermsview.prototype.namespace
		+ '-' + $.wikibase.entitytermsview.prototype.widgetName,
	events: {
		entitytermsviewcreate: function( event, toolbarcontroller ) {
			var $entitytermsview = $( event.target ),
				entitytermsview = $entitytermsview.data( 'entitytermsview' ),
				$container = $entitytermsview.children( '.wikibase-toolbar-container' );

			if ( !$container.length ) {
				$container = $( '<div/>' ).appendTo( $container );
			}

			$entitytermsview.edittoolbar( {
				$container: $container,
				interactionWidget: entitytermsview
			} );

			$entitytermsview.data( 'edittoolbar' ).option( '$container' )
			.sticknode( {
				$container: entitytermsview.$entitytermsforlanguagelistview,
				autoWidth: true,
				zIndex: 2
			} )
			.on( 'sticknodeupdate', function( event ) {
				if ( !$( event.target ).data( 'sticknode' ).isFixed() ) {
					$entitytermsview.data( 'edittoolbar' )
						.option( '$container' ).css( 'width', 'auto' );
				}
			} );

			$entitytermsview.on( 'keyup.edittoolbar', function( event ) {
				if ( entitytermsview.option( 'disabled' ) ) {
					return;
				}
				if ( event.keyCode === $.ui.keyCode.ESCAPE ) {
					entitytermsview.stopEditing( true );
				} else if ( event.keyCode === $.ui.keyCode.ENTER ) {
					entitytermsview.stopEditing( false );
				}
			} );
		},
		'entitytermsviewchange entitytermsviewafterstartediting': function( event ) {
			var $entitytermsview = $( event.target ),
				entitytermsview = $entitytermsview.data( 'entitytermsview' ),
				edittoolbar = $entitytermsview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enable = entitytermsview.isValid() && !entitytermsview.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();

			$entitytermsview.data( 'edittoolbar' )
				.option( '$container' ).data( 'sticknode' ).refresh();
		},
		entitytermsviewafterstopediting: function( event ) {
			var $entitytermsview = $( event.target ),
				entitytermsview = $entitytermsview.data( 'entitytermsview' ),
				showEntitytermslistviewValue = mw.user.isAnon()
					? $.cookie( 'wikibase-entitytermsview-showEntitytermslistview' )
					: mw.user.options.get( 'wikibase-entitytermsview-showEntitytermslistview' ),
				showEntitytermslistview = ( showEntitytermslistviewValue === 'true'
					|| showEntitytermslistviewValue === '1'
					|| showEntitytermslistviewValue === null );

			if ( entitytermsview.$entitytermsforlanguagelistviewContainer.is( ':visible' )
				&& !showEntitytermslistview
			) {
				entitytermsview.$entitytermsforlanguagelistviewContainer.slideUp( {
					complete: function() {
						entitytermsview.$entitytermsforlanguagelistviewToggler.data( 'toggler' )
							.refresh();
					},
					duration: 'fast'
				} );
			}

			$entitytermsview.data( 'edittoolbar' )
				.option( '$container' ).data( 'sticknode' ).refresh();
		},
		entitytermsviewdisable: function( event ) {
			var $entitytermsview = $( event.target ),
				entitytermsview = $entitytermsview.data( 'entitytermsview' ),
				edittoolbar = $entitytermsview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enable = entitytermsview.isValid() && !entitytermsview.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();
		},
		edittoolbaredit: function( event, toolbarcontroller ) {
			var $entitytermsview = $( event.target ),
				entitytermsview = $entitytermsview.data( 'entitytermsview' );

			if ( !entitytermsview ) {
				return;
			}

			if ( !entitytermsview.$entitytermsforlanguagelistviewContainer.is( ':visible' ) ) {
				entitytermsview.$entitytermsforlanguagelistviewContainer.slideDown( {
					complete: function() {
						entitytermsview.$entitytermsforlanguagelistview
							.data( 'entitytermsforlanguagelistview' ).updateInputSize();
						entitytermsview.$entitytermsforlanguagelistviewToggler.data( 'toggler' )
							.refresh();
					},
					duration: 'fast'
				} );
			}

			entitytermsview.focus();
		}
	}
} );

}( jQuery, mediaWiki ) );
