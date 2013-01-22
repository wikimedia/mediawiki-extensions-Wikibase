/**
* JavaScript that allows linking articles with Wikibase items or creating
* new wikibase items directly in the client wikis
*
* Author: Marius Hoch hoo@online.de
*/
( function( mw, $ ) {
	var $dialog, $spinner,
		targetLanguage,	targetArticle;

	function openLinkForm( event ) {
		$dialog = $( '<div>' )
			.append( $( '<p>' )
				.text( mw.message( 'linkItem-selectLink' ) )
			)
			.append( $( '<form>' )
				// dummy
				.attr( 'name', 'linkItemStep1' )
				.attr( 'id', 'linkItemFirstStepForm' )
				.html( 'Lang: <input type="text" name="linkItemLang" /> Page: <input type="text" name="linkItemPage" />')
			)
			.dialog( {
				title: mw.message( 'linkItem-title' ).escaped(),
				minWidth: '123px',
				minHeigh: '123px',
				buttons: [ {
					text: mw.message( 'linkItem-addLink' ).escaped(),
					id: 'wbclient-linkItem-goButton',
					click: secondStep
				} ]
			} );

		// add an edit tool for all properties in the data view:
		// new wb.ui.PropertyEditTool.EditableSiteLink( $( '#dummy' ) );

		event.preventDefault();
	}

	/**
	 * Let the user know that we're currently doing something by showing a spinning animation
	 *
	 */
	function showSpinner() {
		$spinner = $.createSpinner();
		$( '#wbclient-linkItem-goButton' )
			.hide()
			.after( $spinner );
	}

	/**
	 * Remove the spinner created with showSpinner and show the original button again
	 *
	 */
	function removeSpinner() {
		if ( !$spinner.length ) {
			return;
		}
		$spinner.remove();
		$( '#wbclient-linkItem-goButton' ).show();
	}
 
	// Called after the user gave us a language and a page name
	function secondStep() {
		var api = new wb.RepoApi(),
			targetLanguage = $( '#linkItemFirstStepForm' ).find( 'input[name=linkItemLang]' ).val(),
			targetArticle = $( '#linkItemFirstStepForm' ).find( 'input[name=linkItemPage]' ).val(),
			entity;

		showSpinner();

		// @TODO: Sort by Lang (it that's not done by default)
		//		Seems to be done by default... safe to rely on that?
		api.getEntitiesByPage( targetLanguage, targetArticle, ['info', 'sitelinks/urls'], mw.config.get( 'wgUserLanguage' ) )
			.done( function( data ) {
				var i;
				if ( !data.entities['-1'] ) {
					// Show a table with links to the user and ask for confirmation
					for( entity in data.entities ) {
						if ( data.entities[ entity ].sitelinks ) {
							entity = data.entities[ entity ];
							break;
						}
					}

					for( i in entity.sitelinks ) {
						// @TODO: Print fancy (see $templates['wb-sitelink'])
						console.log( entity.sitelinks[ i ] );
					}
					removeSpinner();
					$( '#wbclient-linkItem-goButton' )
						.off( 'click' )
						.text( mw.message( 'linkItem-confirm' ).escaped() )
						.click( function() {
							// The user confirmed that this is the right item... link us
							showSpinner();
							api.setSitelink( entity.id, entity.lastrevid, mw.config.get( 'wbCurrentSite' ).globalSiteId, mw.config.get( 'wgPageName' ) )
								.done( success )
								.fail( onError );
						} );
				} else {
					// Item doesn't yet exist... create it
					// @TODO: We might want to add the current title as label
					api.createEntity( {} )
						.done( function( data ) {
							// Now link this page with the item
							api.setSitelink( data.entity.id, data.entity.lastrevid, mw.config.get( 'wbCurrentSite' ).globalSiteId, mw.config.get( 'wgPageName' ) )
								.done( function( data ) {
									// ...and the one given by the user
									api.setSitelink( data.entity.id, data.entity.lastrevid, targetLanguage, targetArticle )
										.done()
										.fail( onError );
								} )
								.fail( onError );
						} )
						.fail( onError );
				}
			} )
			.fail( onError );
	}

	function success() {
			removeSpinner();
			// @TODO: Purge this page in the background... we shouldn't confuse the user with the newly added link(s) not being there
			$dialog.html(
				// @TODO: Link the (new?) item
				'<p>All done ;)</p>'
			);
	}

	function onError( ) {
		removeSpinner();
		alert( 'Not good' );
	}

	$( document ).ready( function() {
		$( '#wbc-linkToItem > a' )
			.click( openLinkForm );
	} );
} )( mediaWiki, jQuery );
