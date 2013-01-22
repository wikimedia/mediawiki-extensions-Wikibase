/**
* JavaScript that allows linking articles with Wikibase items or creating
* new wikibase items directly in the client wikis
*
* @since 0.4
*
* Author: Marius Hoch hoo@online.de
*/
( function( wb, mw, $ ) {
	var repoApi = new wb.RepoApi(),
		$dialog, $spinner, targetSite, targetArticle;

	/**
	 * Create the dialog asking for a page the user wants to link with the current one
	 */
	function createDialog( event ) {
		$dialog = $( '<div>' )
			.dialog( {
				title: mw.message( 'wikibase-linkItem-title' ).escaped(),
				width: 700,
				height: 400,
				resizable: false,
				buttons: [ {
					text: mw.message( 'wikibase-linkItem-linkPage' ).escaped(),
					id: 'wbclient-linkItem-goButton',
					click: secondStep
				} ]
			} )
			.append(
				$( '<p>' )
					.text( mw.message( 'wikibase-linkItem-selectLink' ).escaped() )
			)
			.append( getSiteLinkForm() );

		event.preventDefault();
	}

	/**
	 * Get a form for selecting the site and the page to link in a user friendly manner (with autocompletion)
	 */
	function getSiteLinkForm() {
		return $( '<form>' )
			.attr( {
				name: 'wikibase-linkItem-form',
				id: 'wikibase-linkItemFirstStepForm'
			} )
			.append(
				$( '<label>' )
					.attr( {
						'for': 'linkItemSite'
					} )
					.text( mw.message( 'wikibase-linkItem-siteInput' ) )
			)
			.append(
				$( '<input />' )
					.attr( {
						name: 'linkItemSite',
						id: 'linkItemSite',
						'class': 'wikibase-client-linkItemInput'
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
				$( '<label>' )
					.attr( {
						'for': 'linkItemSite'
					} )
					.text( mw.msg( 'wikibase-linkItem-pageInput' ) )
			)
			.append(
				$( '<input />' )
					.attr( {
						name: 'linkItemPage',
						id: 'linkItemPage',
						'class' : 'wikibase-client-linkItemInput'
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

		repoApi.getEntitiesByPage( targetSite, targetArticle, ['info', 'sitelinks'], mw.config.get( 'wgUserLanguage' ), 'sites', 'ascending' )
			.done( onItemLoad )
			.fail( onError );
	}

	/**
	 * Shows a table with all sitelinks linked to an item
	 *
	 * @param {object} entity
	 * @return {jQuery}
	 */
	function siteLinkTable( entity )  {
		var i,
			$siteLinks =
				$( '<div>' )
				.attr( 'id', 'wbclient-linkItem-siteLinks' )
				.append(
					$( '<table>' )
				);

		// Table head
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

		// Table body
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
		return $siteLinks;
	}

	/**
	 * Handles the data from getEntitiesByPage and either directly creates a new item or in case there already is an
	 * item it shows the user a confirmation form
	 *
	 * @param {object} data
	 */
	function onItemLoad( data ) {
		var i, $siteLinks, entity, entityTitle;

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
						.text( mw.msg( 'wikibase-linkItem-confirmLinkWithItem' ) )
				).append(
					$( '<br />' )
				)
				.append(
					siteLinkTable( entity )
				);

			removeSpinner();
			$( '#wbclient-linkItem-goButton' )
				.off( 'click' )
				.text( mw.message( 'wikibase-linkItem-confirmButton' ).escaped() )
				.click( function() {
					// The user confirmed that this is the right item... link us
					showSpinner();
					repoApi.setSitelink( entity.id, entity.lastrevid, mw.config.get( 'wbCurrentSite' ).globalSiteId, mw.config.get( 'wgPageName' ) )
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
				value: mw.config.get( 'wgTitle' )
			};
			repoApi.createEntity( entityData )
				.done( function( data ) {
					// Now link this page with the item
					repoApi.setSitelink( data.entity.id, data.entity.lastrevid, mw.config.get( 'wbCurrentSite' ).globalSiteId, mw.config.get( 'wgPageName' ) )
						.done( function( data ) {
							// ...and the one given by the user
							repoApi.setSitelink( data.entity.id, data.entity.lastrevid, targetSite, targetArticle )
								// @TODO: Make success aware of the entity title somehow (so that it can be linked)
								.done( success )
								.fail( onError );
						} )
						.fail( onError );
				} )
				.fail( onError );
		}
	}

	function success() {
		var mwApi = new mw.Api();

		// @TODO: Remove button (or replace with "close")
		removeSpinner();
		$dialog.empty();

		// Purge this page in the background... we shouldn't confuse the user with the newly added link(s) not being there
		mwApi.post( {
			action: 'purge',
			titles: mw.config.get( 'wgPageName' )
		} );

		$dialog.html(
			// @TODO: Link the (new) item and show a nice and encouraging message
			'<p><b>Done</b> (this one is to do)</p>'
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
