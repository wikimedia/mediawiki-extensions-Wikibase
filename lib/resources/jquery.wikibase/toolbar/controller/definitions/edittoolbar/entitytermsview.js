( function( $ ) {
	'use strict';

/**
 * @ignore
 *
 * @licence GNU GPL v2+
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
				$headingContainer = $entitytermsview.find(
					'.wikibase-entitytermsview-heading-container'
				),
				$container = $headingContainer.children( '.wikibase-toolbar-container' );

			if( !$container.length ) {
				$container = $( '<div/>' ).appendTo( $headingContainer );
			}

			$entitytermsview.edittoolbar( {
				$container: $container,
				interactionWidget: entitytermsview
			} );

			$entitytermsview.on( 'keyup.edittoolbar', function( event ) {
				if( entitytermsview.option( 'disabled' ) ) {
					return;
				}
				if( event.keyCode === $.ui.keyCode.ESCAPE ) {
					entitytermsview.stopEditing( true );
				} else if( event.keyCode === $.ui.keyCode.ENTER ) {
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
		},
		entitytermsviewdisable: function( event ) {
			var $entitytermsview = $( event.target ),
				entitytermsview = $entitytermsview.data( 'entitytermsview' ),
				edittoolbar = $entitytermsview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enable = entitytermsview.isValid() && !entitytermsview.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();
		},
		toolbareditgroupedit: function( event, toolbarcontroller ) {
			var $entitytermsview = $( event.target ),
				entitytermsview = $entitytermsview.data( 'entitytermsview' );

			if( !entitytermsview ) {
				return;
			}

			entitytermsview.focus();
		}
	}
} );

}( jQuery ) );
