( function( $, mw ) {
	'use strict';

/**
 * @ignore
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'labelview',
	selector: '.wikibase-labelview:not(.wb-terms-label)',
	events: {
		labelviewcreate: function( event, toolbarcontroller ) {
			var $labelview = $( event.target ),
				labelview = $labelview.data( 'labelview' ),
				$container = $labelview.find( '.wikibase-toolbar-container' );

			// TODO: Remove toolbar-wrapper that is firstHeading specific (required to reset font
			// size)
			if( !$container.length ) {
				$container = $( '<span/>' ).appendTo(
					mw.wbTemplate( 'wikibase-toolbar-wrapper', '' )
					.appendTo( $labelview.find( '.wikibase-labelview-container' ) )
				);
			}

			$labelview.edittoolbar( {
				$container: $container,
				interactionWidget: labelview
			} );

			$labelview.on( 'keyup', function( event ) {
				if( labelview.option( 'disabled' ) ) {
					return;
				}
				if( event.keyCode === $.ui.keyCode.ESCAPE ) {
					labelview.stopEditing( true );
				} else if( event.keyCode === $.ui.keyCode.ENTER ) {
					labelview.stopEditing( false );
				}
			} );

			if( labelview.value().getText() === '' ) {
				labelview.toEditMode();
				$labelview.data( 'edittoolbar' ).toEditMode();
				$labelview.data( 'edittoolbar' ).disable();
			}

			$labelview
			.off( 'labelviewafterstopediting.edittoolbar' )
			.on( 'labelviewafterstopediting.edittoolbar', function( event ) {
				var edittoolbar = $( event.target ).data( 'edittoolbar' );
				if( labelview.value().getText() !== '' ) {
					edittoolbar.toNonEditMode();
					edittoolbar.enable();
					edittoolbar.toggleActionMessage( function() {
						edittoolbar.getButton( 'edit' ).focus();
					} );
				} else {
					labelview.toEditMode();
					edittoolbar.toEditMode();
					edittoolbar.toggleActionMessage( function() {
						labelview.focus();
					} );
					edittoolbar.disable();
				}
			} );
		},
		'labelviewchange labelviewafterstartediting labelviewafterstopediting': function( event ) {
			var $labelview = $( event.target ),
				labelview = $labelview.data( 'labelview' ),
				edittoolbar = $labelview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enableSave = labelview.isValid() && !labelview.isInitialValue(),
				btnCancel = edittoolbar.getButton( 'cancel' ),
				currentLabel = labelview.value().getText(),
				disableCancel = currentLabel === '' && labelview.isInitialValue();

			btnSave[enableSave ? 'enable' : 'disable']();
			btnCancel[disableCancel ? 'disable' : 'enable']();

			if( event.type === 'labelviewchange' ) {
				if( !labelview.isInitialValue() ) {
					labelview.startEditing();
				} else if( labelview.isInitialValue() && labelview.value().getText() === '' ) {
					labelview.cancelEditing();
				}
			}
		},
		labelviewdisable: function( event ) {
			var $labelview = $( event.target ),
				labelview = $labelview.data( 'labelview' ),
				edittoolbar = $labelview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enable = labelview.isValid() && !labelview.isInitialValue(),
				currentLabel = labelview.value().getText();

			btnSave[enable ? 'enable' : 'disable']();

			if( labelview.option( 'disabled' ) || currentLabel !== '' ) {
				return;
			}

			if( currentLabel === '' ) {
				edittoolbar.disable();
			}
		},
		edittoolbaredit: function( event, toolbarcontroller ) {
			var $labelview = $( event.target ),
				labelview = $labelview.data( 'labelview' );

			if( !labelview ) {
				return;
			}

			labelview.focus();
		}
	}
} );

}( jQuery, mediaWiki ) );
