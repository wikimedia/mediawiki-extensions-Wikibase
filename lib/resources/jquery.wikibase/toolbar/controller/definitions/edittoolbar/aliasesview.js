( function( $ ) {
	'use strict';

/**
 * @ignore
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'aliasesview',
	events: {
		aliasesviewcreate: function( event, toolbarcontroller ) {
			var $aliasesview = $( event.target ),
				aliasesview = $aliasesview.data( 'aliasesview' ),
				$container = $aliasesview.find( 'ul' ).next( 'span' );

			if( !$container.length ) {
				$container = $( '<span/>' ).insertAfter( $aliasesview.find( 'ul' ) );
			}

			$aliasesview.edittoolbar( {
				$container: $container,
				interactionWidget: aliasesview
			} );

			$aliasesview.on( 'keyup', function( event ) {
				if( aliasesview.option( 'disabled' ) ) {
					return;
				}
				if( event.keyCode === $.ui.keyCode.ESCAPE ) {
					aliasesview.stopEditing( true );
				} else if( event.keyCode === $.ui.keyCode.ENTER ) {
					aliasesview.stopEditing( false );
				}
			} );

			$aliasesview.one( 'edittoolbaredit', function() {
				toolbarcontroller.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					aliasesview.widgetEventPrefix + 'change',
					function( event ) {
						var $aliasesview = $( event.target ),
							aliasesview = $aliasesview.data( 'aliasesview' ),
							edittoolbar = $aliasesview.data( 'edittoolbar' ),
							btnSave = edittoolbar.getButton( 'save' ),
							enable = aliasesview.isValid() && !aliasesview.isInitialValue();

						btnSave[enable ? 'enable' : 'disable']();
					}
				);
			} );
		},
		aliasesviewdisable: function( event ) {
			var $aliasesview = $( event.target ),
				aliasesview = $aliasesview.data( 'aliasesview' ),
				edittoolbar = $aliasesview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enable = aliasesview.isValid() && !aliasesview.isInitialValue(),
				currentAliases = aliasesview.value();

			btnSave[enable ? 'enable' : 'disable']();

			if( aliasesview.option( 'disabled' ) || currentAliases && currentAliases.length ) {
				return;
			}

			if( !currentAliases ) {
				edittoolbar.disable();
			}
		},
		edittoolbaredit: function( event, toolbarcontroller ) {
			var $aliasesview = $( event.target ),
				aliasesview = $aliasesview.data( 'aliasesview' );

			if( !aliasesview ) {
				return;
			}

			aliasesview.focus();
		}
	}
} );

}( jQuery ) );
