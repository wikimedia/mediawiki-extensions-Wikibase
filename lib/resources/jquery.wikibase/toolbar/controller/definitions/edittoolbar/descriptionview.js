( function( $ ) {
	'use strict';

/**
 * @ignore
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'descriptionview',
	selector: '.wikibase-descriptionview:not(.wb-terms-description)',
	events: {
		descriptionviewcreate: function( event, toolbarcontroller ) {
			var $descriptionview = $( event.target ),
				descriptionview = $descriptionview.data( 'descriptionview' ),
				$container = $descriptionview.find( '.wikibase-toolbar-container' );

			if( !$container.length ) {
				$container = $( '<span/>' ).appendTo(
					$descriptionview.find( '.wikibase-descriptionview-container' )
				);
			}

			$descriptionview.edittoolbar( {
				$container: $container,
				interactionWidget: descriptionview
			} );

			$descriptionview.on( 'keyup', function( event ) {
				if( descriptionview.option( 'disabled' ) ) {
					return;
				}
				if( event.keyCode === $.ui.keyCode.ESCAPE ) {
					descriptionview.stopEditing( true );
				} else if( event.keyCode === $.ui.keyCode.ENTER ) {
					descriptionview.stopEditing( false );
				}
			} );

			if( descriptionview.value().getText() === '' ) {
				descriptionview.toEditMode();
				$descriptionview.data( 'edittoolbar' ).toEditMode();
				$descriptionview.data( 'edittoolbar' ).disable();
			}

			$descriptionview
			.off( 'descriptionviewafterstopediting.edittoolbar' )
			.on( 'descriptionviewafterstopediting.edittoolbar', function( event ) {
				var edittoolbar = $( event.target ).data( 'edittoolbar' );
				if( descriptionview.value().getText() !== '' ) {
					edittoolbar.toNonEditMode();
					edittoolbar.enable();
					edittoolbar.toggleActionMessage( function() {
						edittoolbar.getButton( 'edit' ).focus();
					} );
				} else {
					descriptionview.toEditMode();
					edittoolbar.toEditMode();
					edittoolbar.toggleActionMessage( function() {
						descriptionview.focus();
					} );
					edittoolbar.disable();
				}
			} );
		},
		'descriptionviewchange descriptionviewafterstartediting descriptionviewafterstopediting': function( event ) {
			var $descriptionview = $( event.target ),
				descriptionview = $descriptionview.data( 'descriptionview' ),
				edittoolbar = $descriptionview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enableSave = descriptionview.isValid() && !descriptionview.isInitialValue(),
				btnCancel = edittoolbar.getButton( 'cancel' ),
				currentDescription = descriptionview.value().getText(),
				disableCancel = currentDescription === '' && descriptionview.isInitialValue();

			btnSave[enableSave ? 'enable' : 'disable']();
			btnCancel[disableCancel ? 'disable' : 'enable']();

			if( event.type === 'descriptionviewchange' ) {
				if( !descriptionview.isInitialValue() ) {
					descriptionview.startEditing();
				} else if(
					descriptionview.isInitialValue()
					&& descriptionview.value().getText() === ''
				) {
					descriptionview.cancelEditing();
				}
			}
		},
		descriptionviewdisable: function( event ) {
			var $descriptionview = $( event.target ),
				descriptionview = $descriptionview.data( 'descriptionview' ),
				edittoolbar = $descriptionview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enable = descriptionview.isValid() && !descriptionview.isInitialValue(),
				currentDescription = descriptionview.value().getText();

			btnSave[enable ? 'enable' : 'disable']();

			if( descriptionview.option( 'disabled' ) || currentDescription !== '' ) {
				return;
			}

			if( currentDescription === '' ) {
				edittoolbar.disable();
			}
		},
		edittoolbaredit: function( event, toolbarcontroller ) {
			var $descriptionview = $( event.target ).closest( ':wikibase-edittoolbar' ),
				descriptionview = $descriptionview.data( 'descriptionview' );

			if( !descriptionview ) {
				return;
			}

			descriptionview.focus();
		}
	}
} );

}( jQuery ) );
