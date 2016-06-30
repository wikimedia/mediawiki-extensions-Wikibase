( function( $ ) {
	'use strict';

/**
 * @ignore
 *
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
$.wikibase.toolbarcontroller.definition( 'removetoolbar', {
	id: 'sitelinkgroupview-sitelinkview',
	selector: ':' + $.wikibase.sitelinkgroupview.prototype.namespace
		+ '-' + $.wikibase.sitelinkgroupview.prototype.widgetName,
	events: {
		'sitelinkgroupviewafterstartediting sitelinkgroupviewchange': function( event ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' ),
				$sitelinklistview = sitelinkgroupview.$sitelinklistview,
				sitelinklistview = $sitelinklistview.data( 'sitelinklistview' ),
				sitelinklistviewListview = sitelinklistview.$listview.data( 'listview' );

			if ( !$sitelinkgroupview.length || !sitelinkgroupview.isInEditMode() ) {
				return;
			}

			sitelinklistviewListview.items().each( function() {
				var $sitelinkview = $( this ),
					sitelinkview = $sitelinkview.data( 'sitelinkview' );

				if ( !$sitelinkview.data( 'removetoolbar' ) ) {
					$sitelinkview
					.removetoolbar( {
						$container: $( '<span/>' ).appendTo(
							$sitelinkview.data( 'sitelinkview' ).$siteIdContainer
						)
					} )
					.on( 'removetoolbarremove.removetoolbar', function( event ) {
						if ( event.target !== $sitelinkview.get( 0 ) ) {
							return;
						}
						sitelinklistview.$listview.data( 'listview' ).removeItem( $sitelinkview );
					} );
				}

				var removetoolbar = $sitelinkview.data( 'removetoolbar' ),
					isDisabled = removetoolbar.option( 'disabled' ),
					isValid = sitelinkview.isValid(),
					isEmpty = sitelinkview.isEmpty();

				if ( ( !isValid || isEmpty ) && !isDisabled ) {
					removetoolbar.disable();
				} else if ( isValid && !isEmpty && isDisabled ) {
					removetoolbar.enable();
				}

				// Update inputautoexpand maximum width after adding "remove" toolbar:
				var $siteIdInput = sitelinkview.$siteId.find( 'input' ),
					inputautoexpand = $siteIdInput.length
						? $siteIdInput.data( 'inputautoexpand' )
						: null;
				if ( inputautoexpand ) {
					$siteIdInput.inputautoexpand( {
						maxWidth: $sitelinkview.width() - (
							sitelinkview.$siteIdContainer.outerWidth( true ) - $siteIdInput.width()
						)
					} );
				}

				sitelinkview.updatePageNameInputAutoExpand();
			} );
		},
		sitelinkgroupviewafterstopediting: function( event, toolbarcontroller ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' ),
				$sitelinklistview = sitelinkgroupview.$sitelinklistview,
				sitelinklistview = $sitelinklistview.data( 'sitelinklistview' ),
				sitelinklistviewListview = sitelinklistview.$listview.data( 'listview' );

			sitelinklistviewListview.items().each( function() {
				var $sitelinkview = $( this );
				toolbarcontroller.destroyToolbar( $sitelinkview.data( 'removetoolbar' ) );
			} );
		},
		sitelinkgroupviewdisable: function( event ) {
			var $sitelinkgroupview = $( event.target ),
				sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' ),
				$sitelinklistview = sitelinkgroupview.$sitelinklistview,
				sitelinklistview = $sitelinklistview.data( 'sitelinklistview' ),
				sitelinklistviewListview = sitelinklistview.$listview.data( 'listview' );

			sitelinklistviewListview.items().each( function() {
				var $sitelinkview = $( this ),
					removetoolbar = $sitelinkview.data( 'removetoolbar' );

				if ( !removetoolbar ) {
					return;
				}

				removetoolbar[sitelinkgroupview.option( 'disabled' ) ? 'disable' : 'enable']();
			} );
		}
	}
} );

}( jQuery ) );
