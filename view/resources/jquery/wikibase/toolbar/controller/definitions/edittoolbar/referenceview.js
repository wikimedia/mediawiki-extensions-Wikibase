( function( $ ) {
	'use strict';

/**
 * @ignore
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'referenceview',
	selector: ':' + $.wikibase.referenceview.prototype.namespace
		+ '-' + $.wikibase.referenceview.prototype.widgetName,
	events: {
		referenceviewcreate: function( event ) {
			var $referenceview = $( event.target ),
				referenceview = $referenceview.data( 'referenceview' ),
				options = {
					interactionWidget: referenceview
				},
				$container = $referenceview.find( '.wikibase-toolbar-container' );

			if ( !referenceview.options.statementGuid ) {
				return;
			}

			if ( !$container.length ) {
				$container = $( '<div/>' ).appendTo(
					$referenceview.find( '.wikibase-referenceview-heading' )
				);
			}

			options.$container = $container;

			if ( !!referenceview.value() ) {
				options.onRemove = function() {
					var $statementview = $referenceview.closest( ':wikibase-statementview' ),
						statementview = $statementview.data( 'statementview' );
					if ( statementview ) {
						statementview.remove( referenceview );
					}
				};
			}

			var controller;
			var bridge = {
				cancelEditing: function() { return controller.cancelEditing.apply( controller, arguments ); },
				element: $referenceview,
				getHelpMessage: function() {
					return $.Deferred().resolve( referenceview.options.helpMessage ).promise();
				},
				startEditing: function() { return controller.startEditing.apply( controller, arguments ); },
				stopEditing: function() { return controller.stopEditing.apply( controller, arguments ); },
				setError: function() { return controller.setError.apply( controller, arguments ); }
			};
			options.interactionWidget = bridge;

			$referenceview.edittoolbar( options );

			var guid = referenceview.options.statementGuid;
			var referencesChanger = referenceview.options.referencesChanger;
			controller = new wikibase.Controller( {
				view: referenceview,
				toolbar: $referenceview.data( 'edittoolbar' ),
				model: {
					save: function( reference ) {
						return referencesChanger.setReference( guid, reference );
					}
				}
			} );

			$referenceview.on( 'keydown.edittoolbar', function( event ) {
				if ( referenceview.option( 'disabled' ) ) {
					return;
				}
				if ( event.keyCode === $.ui.keyCode.ESCAPE ) {
					referenceview.stopEditing( true );
				} else if ( event.keyCode === $.ui.keyCode.ENTER ) {
					referenceview.stopEditing( false );
				}
			} );
		},
		referenceviewdisable: function( event ) {
			var $referenceview = $( event.target ),
				referenceview = $referenceview.data( 'referenceview' );

			if ( !referenceview ) {
				return;
			}

			var disable = referenceview.option( 'disabled' ),
				edittoolbar = $referenceview.data( 'edittoolbar' );

			if ( !edittoolbar ) {
				return;
			}

			var btnSave = edittoolbar.getButton( 'save' ),
				enableSave = ( referenceview.isValid() && !referenceview.isInitialValue() );

			edittoolbar.option( 'disabled', disable );
			if ( !disable ) {
				btnSave.option( 'disabled', !enableSave );
			}
		}

		// Destroying the referenceview will destroy the toolbar. Trying to destroy the toolbar
		// in parallel will cause interference.
	}
} );

}( jQuery ) );
