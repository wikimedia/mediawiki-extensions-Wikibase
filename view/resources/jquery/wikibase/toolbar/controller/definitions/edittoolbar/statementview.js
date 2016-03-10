( function( $ ) {
	'use strict';

/**
 * @ignore
 *
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'statementview',
	selector: ':' + $.wikibase.statementview.prototype.namespace
		+ '-' + $.wikibase.statementview.prototype.widgetName,
	events: {
		statementviewcreate: function( event, toolbarcontroller ) {
			var $statementview = $( event.target ),
				statementview = $statementview.data( 'statementview' ),
				options = {
					interactionWidget: statementview
				},
				$container = $statementview.children( '.wikibase-toolbar-container' );

			if ( !$container.length ) {
				$container = $( '<div/>' ).appendTo( $statementview );
			}

			options.$container = $container;

			if ( statementview.option( 'value' ) ) {
				options.onRemove = function() {
					var $statementlistview
							= $statementview.closest( ':wikibase-statementlistview' ),
						statementlistview = $statementlistview.data( 'statementlistview' );
					if ( statementlistview ) {
						statementlistview.remove( statementview );
					}
				};
			}

			$statementview.edittoolbar( options );

			$statementview.on( 'keydown.edittoolbar', function( event ) {
				if ( statementview.option( 'disabled' ) ) {
					return;
				}
				if ( event.keyCode === $.ui.keyCode.ESCAPE ) {
					statementview.stopEditing( true );
				} else if ( event.keyCode === $.ui.keyCode.ENTER ) {
					statementview.stopEditing( false );
				}
			} );
		},
		statementviewdestroy: function( event, toolbarController ) {
			var $statementview = $( event.target );
			toolbarController.destroyToolbar( $statementview.data( 'edittoolbar' ) );
			$statementview.off( '.edittoolbar' );
		},
		'statementviewchange statementviewafterstartediting': function( event ) {
			var $statementview = $( event.target ),
				statementview = $statementview.data( 'statementview' ),
				edittoolbar = $statementview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' );

			/**
			 * statementview.isValid() validates the qualifiers already. However, the information
			 * whether all qualifiers (grouped by property) have changed, needs to be gathered
			 * separately which, in addition, is done by this function.
			 *
			 * @param {jQuery.wikibase.statementview} statementview
			 * @return {boolean}
			 */
			function shouldEnableSaveButton( statementview ) {
				var enable = statementview.isValid() && !statementview.isInitialValue(),
					snaklistviews = ( statementview._qualifiers )
						? statementview._qualifiers.value()
						: [],
					areInitialQualifiers = true;

				if ( enable && snaklistviews.length ) {
					for ( var i = 0; i < snaklistviews.length; i++ ) {
						if ( !snaklistviews[i].isInitialValue() ) {
							areInitialQualifiers = false;
						}
					}
				}

				return enable && !( areInitialQualifiers && statementview.isInitialValue() );
			}

			btnSave[shouldEnableSaveButton( statementview ) ? 'enable' : 'disable']();
		},
		statementviewdisable: function( event ) {
			var $statementview = $( event.target ),
				statementview = $statementview.data( 'statementview' ),
				edittoolbar = $statementview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enable = statementview.isInEditMode() && statementview.isValid() && !statementview.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();
		},
		edittoolbaredit: function( event, toolbarcontroller ) {
			var $statementview = $( event.target ),
				statementview = $statementview.data( 'statementview' );

			if ( !statementview ) {
				return;
			}

			statementview.focus();
		}
	}
} );

}( jQuery ) );
