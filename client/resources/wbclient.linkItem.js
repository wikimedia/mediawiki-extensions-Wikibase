/**
* JavaScript that allows linking articles with Wikibase items or creating
* new wikibase items directly in the client wikis
*
* Author: Marius Hoch hoo@online.de
*/
( function( wb, mw, $ ) {
	var $dialog, $spinner;

	function openLinkForm( event ) {
		$dialog = $( '<div>' )
			.dialog( {
				title: mw.message( 'linkItem-title' ).escaped(),
				width: 700,
				height: 400,
				resizable: false,
				buttons: [ {
					text: mw.message( 'linkItem-addLink' ).escaped(),
					id: 'wbclient-linkItem-goButton',
					click: secondStep
				} ]
			} )
			.append(
				$( '<p>' )
					.text( mw.message( 'linkItem-selectLink' ) )
			)
			.append( getSiteLinkForm() );
/*				$( '<form>' )
					// dummy
					.attr( 'name', 'linkItemStep1' )
					.attr( 'id', 'linkItemFirstStepForm' )
					.html( 'Lang: <input type="text" name="linkItemLang" /> Page: <input type="text" name="linkItemPage" />')
			);
*/
		// add an edit tool for all properties in the data view:
		// new wb.ui.PropertyEditTool.EditableSiteLink( $( '#dummy' ) );

		event.preventDefault();
	}

	function getSiteLinkForm() {
		var $form = $( '<form>' )
			.attr({
				'name' : 'linkItemStep1',
				'id' : 'linkItemFirstStepForm'
			});

		$( '<input />' )
			.attr({
				// @todo: these really are sites, not langs (e.g. simple English)
				'name' : 'linkItemLang',
				'class' : 'linkItemLang'
			})
			.siteselector({ resultSet: getSites() })
			.appendTo( $( $form ) );

		$( '<input />' )
			.attr({
				'name' : 'linkItemPage',
				'class' : 'linkItemPage'
			}).appendTo( $( $form ) );

		return $form;
	}

	function getSites() {
		var $sites = [];
		for ( var siteId in wb.getSites() ) {
			$sites.push( wb.getSite( siteId ) );
		}
		return $sites;
	}

	/**
	 * Let the user know that we're currently doing something by
	 * replacing the go on button with a spinning animation
	 */
	function showSpinner() {
		$spinner = $.createSpinner();
		$( '#wbclient-linkItem-goButton' )
			.hide()
			.after( $spinner );
	}

	/**
	 * Remove the spinner created with showSpinner and show the original button again
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
			targetArticle = $( '#linkItemFirstStepForm' ).find( 'input[name=linkItemPage]' ).val();

		showSpinner();

		// @TODO: Sort by Lang (it that's not done by default)
		//		Seems to be done by default... safe to rely on that?
		api.getEntitiesByPage( targetLanguage, targetArticle, ['info', 'sitelinks'], mw.config.get( 'wgUserLanguage' ) )
			.done( function( data ) {
				var i, $siteLinks, site, entity;
				if ( !data.entities['-1'] ) {
					// Show a table with links to the user and ask for confirmation
					for( entity in data.entities ) {
						if ( data.entities[ entity ].sitelinks ) {
							entity = data.entities[ entity ];
							break;
						}
					}

					$dialog
						.empty()
						.append(
							$( '<div>' )
								// @TODO: i18n
								.text( 'Please confirm that the item shown below is the one you want to link' )
						).append(
							$( '<br />' )
						);

					$siteLinks = $( '<div>' )
							.attr( 'id', 'wbclient-linkItem-siteLinks' )
							.append(
								$( '<table>' )
							);

					// table head
					// @TODO: i18n
					$( '<thead>' )
						.append(
							$( '<tr>' )
								.append(
									$( '<th>' )
										.text( 'Code' )
								)
								.append(
									$( '<th>' )
										.text( 'Language' )
								)
								.append(
									$( '<th>' )
											.text( 'Page link' )
								)
						)
						.appendTo( $siteLinks.find( 'table' ) );

					for( i in entity.sitelinks ) {
						if ( entity.sitelinks[ i ].site ) {
							// Show a row for each page that is linked with the current entity
							site = wb.getSiteByGlobalId( entity.sitelinks[ i ].site );
							$( '<tr>' )
								.append(
									$( '<td>' )
										.text( site.getLanguageCode() )
								)
								.append(
									$( '<td>' )
										.text( site.getName() )
										.css( 'direction', site.getLanguage().dir )
								)
								.append(
									$( '<td>' )
										.append(
											$( '<a>' )
												.attr( 'href', site.getLinkTo( entity.sitelinks[ i ].title ) )
												.text( entity.sitelinks[ i ].title )
										)
								)
								.appendTo( $siteLinks.find( 'table' ) );
						}
					}
					$dialog.append( $siteLinks );
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
										.done( success )
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
			$dialog.empty();
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
} )( wikibase, mediaWiki, jQuery );
