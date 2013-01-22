/**
* JavaScript that allows linking articles with Wikibase items or creating
* new wikibase items directly in the client wikis
*
* @since 0.4
*
* Author: Marius Hoch hoo@online.de
*/
( function( wb, mw, $ ) {
	var api = new wb.RepoApi(),
		$dialog, $spinner, targetSite, targetArticle;

	/**
	 * Create the dialog asking for a page the user wants to link with the current one
	 */
	function createDialog( event ) {
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

		event.preventDefault();
	}

	/**
	 * Get a form for selecting the site and the page to link in a user friendly manner
	 */
	function getSiteLinkForm() {
		return $( '<form>' )
			.attr( {
				name: 'linkItemStep1',
				id: 'linkItemFirstStepForm'
			} )
			.append(
				$( '<input />' )
					.attr( {
						name: 'linkItemSite',
						id: 'linkItemSite',
						'class': 'linkItemSite'
					} )
					.siteselector( {
						resultSet: wb.getSites()
					} )
					.on( 'siteselectorselect', function() {
						var apiUrl;
						try {
							apiUrl = $( '#linkItemSite' ).siteselector( 'getSelectedSite' ).getApi();
						} catch( e ) {
							// Invalid input (likely incomplete)
							return;
						}
						// If the language gets changed the yet selected page is no longer available so we clear the input element.
						// Furthermore we remove the old suggestor (if there's one) and create a new one working on the right wiki
						$( '#linkItemPage' )
							.val( '' )
							.suggester( 'destroy' )
							.suggester( {
								ajax: {
									url: apiUrl,
									params: {
										action: 'opensearch',
										namespace: 0
									}
								}
							} );
					} )
			)
			.append(
				$( '<input />' )
					.attr( {
						name: 'linkItemPage',
						id: 'linkItemPage',
						'class' : 'linkItemPage'
					} )
			);
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

	/**
	 * Create a table row for a site link
	 *
	 * @param {wikibase.Site} site
	 * @param {object} entitySitelinks
	 * @return {jQuery}
	 */
	function siteLinkRow( site, entitySitelinks ) {
		return $( '<tr>' )
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
							.attr( 'href', site.getLinkTo( entitySitelinks.title ) )
							.text( entitySitelinks.title )
					)
			);
	}

	/**
	* Called after the user gave us a language and a page name. Looks up any existing items then or
	* tries to link the currently viewed page with an existing item
	*
	*/
	function secondStep() {
		targetSite = $( '#linkItemSite' ).siteselector( 'getSelectedSite' ).getGlobalSiteId(),
		targetArticle = $( '#linkItemPage' ).val();

		// Show a spinning animation and do an API request
		showSpinner();

		api.getEntitiesByPage( targetSite, targetArticle, ['info', 'sitelinks'], mw.config.get( 'wgUserLanguage' ), 'sites', 'ascending' )
			.done( onItemLoad )
			.fail( onError );
	}

	/**
	 * Handles the data from getEntitiesByPage and either directly creates a new item or in case there already is an
	 * item it shows the user a confirmation form
	 *
	 * @param {object} data
	 */
	function onItemLoad( data ) {
		var i, $siteLinks, entity;

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
								.text( mw.msg( 'wikibase-sitelinks-sitename-columnheading' ) )
						)
						.append(
							$( '<th>' )
								.text( mw.msg( 'wikibase-sitelinks-siteid-columnheading' ) )
						)
						.append(
							$( '<th>' )
								.text( mw.msg( 'wikibase-sitelinks-link-columnheading' ) )
						)
				)
				.appendTo( $siteLinks.find( 'table' ) );

			for( i in entity.sitelinks ) {
				if ( entity.sitelinks[ i ].site ) {
					// Show a row for each page that is linked with the current entity
					$siteLinks
						.find( 'table' )
						.append(
							siteLinkRow(
								wb.getSiteByGlobalId( entity.sitelinks[ i ].site ),
								entity.sitelinks[ i ]
							)
						);
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
			// There is no item for the page the user wants to link... so create a new one

			// Add the current title as label
			var entityData = {
				labels: {}
			};
			entityData.labels[ mw.config.get( 'wgContentLanguage' ) ] = {
				language: mw.config.get( 'wgContentLanguage' ),
				value: mw.config( 'wgTitle' )
			}
			api.createEntity( entityData )
				.done( function( data ) {
					// Now link this page with the item
					api.setSitelink( data.entity.id, data.entity.lastrevid, mw.config.get( 'wbCurrentSite' ).globalSiteId, mw.config.get( 'wgPageName' ) )
						.done( function( data ) {
							// ...and the one given by the user
							api.setSitelink( data.entity.id, data.entity.lastrevid, targetSite, targetArticle )
								.done( success )
								.fail( onError );
						} )
						.fail( onError );
				} )
				.fail( onError );
		}
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
			.click( createDialog );
	} );
} )( wikibase, mediaWiki, jQuery );
