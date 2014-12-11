( function( $ ) {
	'use strict';

/**
 * @ignore
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'fingerprintgroupview',
	selector: ':' + $.wikibase.fingerprintgroupview.prototype.namespace
		+ '-' + $.wikibase.fingerprintgroupview.prototype.widgetName,
	events: {
		fingerprintgroupviewcreate: function( event, toolbarcontroller ) {
			var $fingerprintgroupview = $( event.target ),
				fingerprintgroupview = $fingerprintgroupview.data( 'fingerprintgroupview' ),
				$headingContainer = $fingerprintgroupview.find(
					'.wikibase-fingerprintgroupview-heading-container'
				),
				$container = $headingContainer.children( '.wikibase-toolbar-container' );

			if( !$container.length ) {
				$container = $( '<div/>' ).appendTo( $headingContainer );
			}

			$fingerprintgroupview.edittoolbar( {
				$container: $container,
				interactionWidget: fingerprintgroupview
			} );

			$fingerprintgroupview.on( 'keyup.edittoolbar', function( event ) {
				if( fingerprintgroupview.option( 'disabled' ) ) {
					return;
				}
				if( event.keyCode === $.ui.keyCode.ESCAPE ) {
					fingerprintgroupview.stopEditing( true );
				} else if( event.keyCode === $.ui.keyCode.ENTER ) {
					fingerprintgroupview.stopEditing( false );
				}
			} );
		},
		'fingerprintgroupviewchange fingerprintgroupviewafterstartediting': function( event ) {
			var $fingerprintgroupview = $( event.target ),
				fingerprintgroupview = $fingerprintgroupview.data( 'fingerprintgroupview' ),
				edittoolbar = $fingerprintgroupview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enable = fingerprintgroupview.isValid() && !fingerprintgroupview.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();
		},
		fingerprintgroupviewdisable: function( event ) {
			var $fingerprintgroupview = $( event.target ),
				fingerprintgroupview = $fingerprintgroupview.data( 'fingerprintgroupview' ),
				edittoolbar = $fingerprintgroupview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enable = fingerprintgroupview.isValid() && !fingerprintgroupview.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();
		},
		toolbareditgroupedit: function( event, toolbarcontroller ) {
			var $fingerprintgroupview = $( event.target ),
				fingerprintgroupview = $fingerprintgroupview.data( 'fingerprintgroupview' );

			if( !fingerprintgroupview ) {
				return;
			}

			fingerprintgroupview.focus();
		}
	}
} );


}( jQuery ) );
