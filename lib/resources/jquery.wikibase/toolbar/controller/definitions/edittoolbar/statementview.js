( function( $ ) {
	'use strict';

/**
 * @ignore
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'statementview',
	selector: ':' + $.wikibase.statementview.prototype.namespace
		+ '-' + $.wikibase.statementview.prototype.widgetName,
	events: {
		'statementviewcreate': function( event, toolbarcontroller ) {
			var viewType = event.type.replace( /create$/, '' ),
				$view = $( event.target ),
				view = $view.data( viewType ),
				options = {
					interactionWidget: view
				},
				$container = $view.children( '.wikibase-toolbar-container' );

			if( !$container.length ) {
				$container = $( '<div/>' ).appendTo( $view );
			}

			options.$container = $container;

			if( !!view.value() ) {
				options.onRemove = function() {
					var $claimlistview = $view.closest( ':wikibase-claimlistview' ),
						claimlistview = $claimlistview.data( 'claimlistview' );
					if( claimlistview ) {
						claimlistview.remove( view );
					}
				};
			}

			$view.edittoolbar( options );

			$view.on( 'keydown.edittoolbar', function( event ) {
				if( view.option( 'disabled' ) ) {
					return;
				}
				if( event.keyCode === $.ui.keyCode.ESCAPE ) {
					view.stopEditing( true );
				} else if( event.keyCode === $.ui.keyCode.ENTER ) {
					view.stopEditing( false );
				}
			} );
		},
		'statementviewdestroy': function( event, toolbarController ) {
			var $statementview = $( event.target );
			toolbarController.destroyToolbar( $statementview.data( 'edittoolbar' ) );
			$statementview.off( '.edittoolbar' );
		},
		'statementviewchange': function( event ) {
			var $target = $( event.target ),
				viewType = event.type.replace( /change$/, '' ),
				view = $target.data( viewType ),
				edittoolbar = $target.data( 'edittoolbar' ),
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

				if( enable && snaklistviews.length ) {
					for( var i = 0; i < snaklistviews.length; i++ ) {
						if( !snaklistviews[i].isInitialValue() ) {
							areInitialQualifiers = false;
						}
					}
				}

				return enable && !( areInitialQualifiers && statementview.isInitialValue() );
			}

			btnSave[shouldEnableSaveButton( view ) ? 'enable' : 'disable']();
		},
		'statementviewdisable': function( event ) {
			var viewType = event.type.replace( /disable$/, '' ),
				$view = $( event.target ),
				view = $view.data( viewType ),
				edittoolbar = $view.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enable = view.isValid() && !view.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();
		},
		edittoolbaredit: function( event, toolbarcontroller ) {
			var $statementview = $( event.target ),
				statementview = $statementview.data( 'statementview' );

			if( !statementview ) {
				return;
			}

			statementview.focus();
		}
	}
} );

}( jQuery ) );
