( function( $ ) {
	'use strict';

/**
 * @ignore
 *
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'sitelinkgroupview',
	selector: ':' + $.wikibase.sitelinkgroupview.prototype.namespace
		+ '-' + $.wikibase.sitelinkgroupview.prototype.widgetName,
	events: {
		sitelinkgroupviewcreate: function( event ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' ),
				$headingContainer = $sitelinkgroupview.find(
					'.wikibase-sitelinkgroupview-heading-container'
				),
				$container = $headingContainer.children( '.wikibase-toolbar-container' );

			if ( !$container.length ) {
				$container = $( '<div/>' ).appendTo( $headingContainer );
			}

			$sitelinkgroupview.edittoolbar( {
				$container: $container,
				interactionWidget: sitelinkgroupview
			} );

			$sitelinkgroupview.on( 'keydown.edittoolbar', function( event ) {
				if ( sitelinkgroupview.option( 'disabled' ) ) {
					return;
				}
				if ( event.keyCode === $.ui.keyCode.ESCAPE ) {
					sitelinkgroupview.stopEditing( true );
				} else if ( event.keyCode === $.ui.keyCode.ENTER ) {
					sitelinkgroupview.stopEditing( false );
				}
			} );
		},
		'sitelinkgroupviewchange sitelinkgroupviewafterstartediting': function( event ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' ),
				edittoolbar = $sitelinkgroupview.data( 'edittoolbar' );

			if ( !edittoolbar ) {
				return;
			}

			var btnSave = edittoolbar.getButton( 'save' ),
				enable = sitelinkgroupview.isValid() && !sitelinkgroupview.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();
		},
		sitelinkgroupviewdisable: function( event ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' ),
				edittoolbar = $sitelinkgroupview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enable = sitelinkgroupview.isValid() && !sitelinkgroupview.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();
		},
		edittoolbaredit: function( event, toolbarcontroller ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' );

			if ( !sitelinkgroupview ) {
				return;
			}

			sitelinkgroupview.focus();
		}
	}
} );

}( jQuery ) );
