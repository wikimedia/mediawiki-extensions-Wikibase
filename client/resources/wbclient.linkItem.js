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
		$dialog, $spinner, $goButton, targetSite, targetArticle;

	/**
	 * Check whether the user is logged in on both the client and the repo
	 * show the dialog if he is, error if not
	 *
	 */
	function checkLoggedin( event ) {
		event.preventDefault();

		if ( mw.user.isAnon() ) {
			// User isn't logged in
			notLoggedin();
			return;
		}

		var $dialogSpinner = $.createSpinner(),
			$linkItemLink = $( '#wbc-linkToItem-link' );
		$linkItemLink
			.hide()
			.after( $dialogSpinner );

		var repoApi = new wb.RepoApi();
		repoApi.get( {
			action: 'query',
			meta: 'userinfo'
		} )
		.done( function( data ) {
			$dialogSpinner.remove();

			if ( data.query.userinfo.anon !== undefined ) {
				// User isn't logged into the repo
				notLoggedin();
				return;
			}

			createDialog();
		} )
		.fail( function() {
			$dialogSpinner.remove();
			$linkItemLink.show();

			var tooltip = new wb.ui.Tooltip( $linkItemLink, {}, mw.msg( 'wikibase-error-unexpected' ), { gravity: 'w' } );

			tooltip.show();
			$linkItemLink.one( 'click', function() {
				// Remove the tooltip by the time the user tries it again
				tooltip.destroy();
			} );
		} );
	}

	/**
	 * Show an error to the user in case he isn't logged in on both the client and the repo
	 *
	 */
	function notLoggedin() {
		$( '#wbc-linkToItem-link' ).show();

		var userLogin = linkRepoTitle( 'Special:UserLogin' );
		$( '<div>' )
			.dialog( {
				title: mw.msg( 'wikibase-linkitem-not-loggedin-title' ),
				width: 400,
				height: 200,
				resizable: true
			} )
			.append(
				$( '<p>' )
					.addClass( 'wbclient-linkItem-not-loggedin-message' )
					.html( mw.message( 'wikibase-linkitem-not-loggedin', userLogin ).parse() )
			);
	}

	/**
	 * Create the dialog asking for a page the user wants to link with the current one
	 */
	function createDialog() {
		$dialog = $( '<div>' )
			.attr( 'id', 'wbclient-linkItem-dialog' )
			.dialog( {
				title: mw.message( 'wikibase-linkitem-title' ).escaped(),
				width: 700,
				height: 400,
				resizable: false,
				buttons: [ {
					text: mw.message( 'wikibase-linkitem-linkpage' ).escaped(),
					id: 'wbclient-linkItem-goButton',
					disabled: 'disabled',
					click: secondStep
				} ],
				close: onDialogClose
			} )
			.append(
				$( '<p>' )
					.text( mw.message( 'wikibase-linkitem-selectlink' ).escaped() )
			)
			.append( getSiteLinkForm() );

		$goButton = $( '#wbclient-linkItem-goButton' );
	}

	/**
	 * Called by the time the dialog get's closed. Removes the values of all persistent variables
	 * and makes the link reapper
	 *
	 */
	function onDialogClose() {
		$dialog.remove();
		if ( $spinner && $spinner.length ) {
			$spinner.remove();
		}
		$goButton = null;
		targetSite = null;
		targetArticle = null;
		$( '#wbc-linkToItem-link' )
			.show();
	}

	/**
	 * Gets an object with all sites despite the current one (as we can't link pages on the same wiki)
	 *
	 * @return {object}
	 */
	function getLinkableSites() {
		var sites = wb.getSites(),
			linkableSites = {},
			site;
		for( site in sites ) {
			if ( sites[ site ].getGlobalSiteId() !== mw.config.get( 'wbCurrentSite' ).globalSiteId ) {
				linkableSites[ site ] = sites[ site ];
			}
		}
		return linkableSites;
	}

	/**
	 * Get a form for selecting the site and the page to link in a user friendly manner (with autocompletion)
	 */
	function getSiteLinkForm() {
		return $( '<form>' )
			.attr( {
				name: 'wikibase-linkItem-form'
			} )
			.append(
				$( '<label>' )
					.attr( {
						'for': 'wbclient-linkItem-Site'
					} )
					.text( mw.message( 'wikibase-linkitem-input-site' ) )
			)
			.append(
				$( '<input />' )
					.attr( {
						name: 'wbclient-linkItem-Site',
						id: 'wbclient-linkItem-Site',
						'class': 'wbclient-linkItem-Input'
					} )
					.siteselector( {
						resultSet: getLinkableSites()
					} )
					.on( 'siteselectorselect', function() {
						var apiUrl;
						try {
							apiUrl = $( '#wbclient-linkItem-Site' ).siteselector( 'getSelectedSite' ).getApi();
						} catch( e ) {
							// Invalid input (likely incomplete)
							invalidSiteGiven();
							return;
						}
						// If the language gets changed the yet selected page is no longer available so we clear the input element.
						// Furthermore we remove the old suggestor (if there's one) and create a new one working on the right wiki
						$( '#wbclient-linkItem-page' )
							.removeAttr( 'disabled' )
							.val( '' )
							.suggester( 'destroy' )
							.suggester( {
								ajax: {
									url: apiUrl,
									params: {
										action: 'opensearch',
										namespace: mw.config.get( 'wgNamespaceNumber' )
									}
								}
							} );
					} )
			)
			.append(
				$( '<br />' )
			)
			.append(
				$( '<label>' )
					.attr( {
						'for': 'wbclient-linkItem-Site'
					} )
					.text( mw.msg( 'wikibase-linkitem-input-page' ) )
			)
			.append(
				$( '<input />' )
					.attr( {
						name: 'wbclient-linkItem-page',
						id: 'wbclient-linkItem-page',
						disabled: 'disabled',
						'class' : 'wbclient-linkItem-Input'
					} )
					.one( 'focus', function() {
						// Enable the button by the time the user uses this field
						$goButton.button( 'enable' );
					} )
			);
	}

	/**
	 * Let the user know that we're currently doing something by
	 * replacing the go on button with a spinning animation
	 */
	function showSpinner() {
		$spinner = $.createSpinner();
		$goButton
			.hide()
			.after( $spinner );
	}

	/**
	 * Remove the spinner created with showSpinner and show the original button again
	 */
	function removeSpinner() {
		if ( !$spinner || !$spinner.length ) {
			return;
		}
		$spinner.remove();
		$goButton.show();
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
					.addClass( 'wbclient-linkItem-colum-site' )
					.text( site.getName() )
					.css( 'direction', site.getLanguage().dir )
			)
			.append(
				$( '<td>' )
					.addClass( 'wbclient-linkItem-colum-page' )
					.append(
						site.getLinkTo( entitySitelinks.title )
					)
					.css( 'direction', site.getLanguage().dir )
			);
	}

	/**
	* Called after the user gave us a language and a page name. Looks up any existing items then or
	* tries to link the currently viewed page with an existing item
	*/
	function secondStep() {
		if ( $( '#wbclient-linkItem-Site' ).siteselector( 'getSelectedSite' ) ) {
			targetSite = $( '#wbclient-linkItem-Site' ).siteselector( 'getSelectedSite' ).getGlobalSiteId();
		} else {
			invalidSiteGiven();
			return;
		}
		targetArticle = $( '#wbclient-linkItem-page' ).val();

		// Show a spinning animation and do an API request
		showSpinner();

		repoApi.getEntitiesByPage( targetSite, targetArticle, ['info', 'sitelinks'], mw.config.get( 'wgUserLanguage' ), 'sitelinks', 'ascending' )
			.done( onEntityLoad )
			.fail( onError );
	}

	/**
	 * Returns a table with all sitelinks linked to an entity
	 *
	 * @param {object} entity
	 * @return {jQuery}
	 */
	function siteLinkTable( entity )  {
		var i, $siteLinks;

		$siteLinks = $( '<div>' )
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
							.text( mw.msg( 'wikibase-sitelinks-link-columnheading' ) )
					)
			)
			.appendTo( $siteLinks.find( 'table' ) );

		// Table body
		for ( i in entity.sitelinks ) {
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
	 * Get the entity for the current page in case there is one
	 *
	 * @param {jQuery.promise}
	 */
	function getEntityForCurrentPage() {
		return repoApi.getEntitiesByPage(
			mw.config.get( 'wbCurrentSite' ).globalSiteId,
			mw.config.get( 'wgPageName' ),
			['info', 'sitelinks'],
			mw.config.get( 'wgUserLanguage' ),
			'sitelinks',
			'ascending'
		);
	}

	/**
	 * Handles the data from getEntitiesByPage and either creates a new item or in case there already is an
	 * item it shows the user a confirmation form
	 *
	 * @param {object} data
	 */
	function onEntityLoad( data ) {
		var i, entity, itemLink;

		if ( !data.entities['-1'] ) {
			removeSpinner();

			var siteLinkCount = 0;
			// Show a table with links to the user and ask for confirmation
			for ( i in data.entities ) {
				if ( data.entities[ i ].sitelinks ) {
					entity = data.entities[ i ];
					break;
				}
			}
			itemLink = linkRepoTitle( entity.title );

			// Count site links and abort in case the entity already is linked with a page on this wiki
			for ( i in entity.sitelinks ) {
				if ( entity.sitelinks[ i ].site ) {
					siteLinkCount += 1;
				}
				if ( entity.sitelinks[ i ].site === mw.config.get( 'wbCurrentSite' ).globalSiteId ) {
					// Abort as the entity already is linked with a page on this wiki
					onError(
						mw.message( 'wikibase-linkitem-alreadylinked', itemLink, entity.sitelinks[ i ].title ).parse()
					);
					return;
				}
			}

			if ( siteLinkCount === 1 ) {
				// The item we want to link with only has a single langlink so we don't have to ask for confirmation
				linkWithEntity( entity );
			} else {

				$dialog
					.empty()
					.append(
						$( '<div>' )
							.html( mw.message( 'wikibase-linkitem-confirmitem-text', itemLink ).parse() )
					).append(
						$( '<br />' )
					)
					.append(
						siteLinkTable( entity )
					);

				$goButton
					.off( 'click' )
					.button( 'option', 'label', mw.msg( 'wikibase-linkitem-confirmitem-button' ) )
					.click( function() {
						// The user confirmed that this is the right item...
						linkWithEntity( entity );
					} );
			}
		} else {
			// There is no item for the page the user wants to link
			// Maybe there's one for the current page though (without other links then)

			getEntityForCurrentPage()
				.fail( onError )
				.done( function( data ) {
					if ( data.entities['-1'] ) {
						// There's no entity yet, create one

						// JSON data for the new entity
						var entityData = {
							labels: {},
							sitelinks: {}
						};
						// Label (page title)
						entityData.labels[ mw.config.get( 'wgContentLanguage' ) ] = {
							language: mw.config.get( 'wgContentLanguage' ),
							value: mw.config.get( 'wgTitle' )
						};
						// Link this page
						entityData.sitelinks[ mw.config.get( 'wbCurrentSite' ).globalSiteId ] = {
							site: mw.config.get( 'wbCurrentSite' ).globalSiteId,
							title: mw.config.get( 'wgPageName' )
						};
						// ...and the one given by the user
						entityData.sitelinks[ targetSite ] = {
							site: targetSite,
							title: targetArticle
						};
						repoApi.createEntity( entityData )
							.done( successfullyCreated )
							.fail( onError );
					} else {
						// There already is an entity with the current page linked
						// but it's empty cause this dialog isn't shown on pages with langlinks
						var i, entity;

						for ( i in data.entities ) {
							if ( data.entities[ i ].title ) {
								entity = data.entities[ i ];
								break;
							}
						}

						repoApi.setSitelink(
							entity.id,
							entity.lastrevid,
							targetSite,
							targetArticle
						)
						.done( successfullyLinked )
						.fail( onError );
					}
				} );
		}
	}

	/**
	 * Links the current page with the given entity. If the current page yet is linked with an item we have to unlink it first.
	 * This is only going to happen if the current page is linked with an item which only has the current item linked to prevent conflicts
	 *
	 * @param {object} entity
	 */
	function linkWithEntity( entity ) {
		showSpinner();
		getEntityForCurrentPage()
			.fail( onError )
			.done( function( data ) {

				/**
				 * Link the item with the one the user told us
				 */
				function doLink() {
					repoApi.setSitelink(
						entity.id,
						entity.lastrevid,
						mw.config.get( 'wbCurrentSite' ).globalSiteId,
						mw.config.get( 'wgPageName' )
					)
					.done( successfullyLinked )
					.fail( onError );
				}

				if ( data.entities['-1'] ) {
					// Everything is ok
					doLink();
				} else {
					// We have to unlink it first
					var siteLinkCount = 0,
						i, selfEntity;
					for ( i in data.entities ) {
						if ( data.entities[ i ].title ) {
							selfEntity = data.entities[ i ];
							break;
						}
					}
					for ( i in selfEntity.sitelinks ) {
						if ( selfEntity.sitelinks[ i ].site ) {
							siteLinkCount += 1;
						}
					}
					if ( siteLinkCount === 1 ) {
						// The current page has an own item with no other links... unlink us
						repoApi.removeSitelink( selfEntity.id, selfEntity.lastrevid, mw.config.get( 'wbCurrentSite' ).globalSiteId )
							.done( doLink )
							.fail( onError );
					} else {
						// The current page already is linked with an item which is linked with other pages... this probably some kind of edit conflict.
						// Show an error and let the user purge the page
						var tooltip = new wb.ui.Tooltip(
							$goButton,
							{},
							mw.msg( 'wikibase-linkitem-failure' ),
							{ gravity: 'nw' }
						);

						removeSpinner();
						tooltip.show();

						// Replace the button with one asking to close the dialog and reload the current page
						$goButton
							.off( 'click' )
							.click( function() {
								showSpinner();
								window.location.href = mw.config.get( 'wgServer' ) + mw.config.get('wgScript' ) + '?title=' + encodeURIComponent( mw.config.get( 'wgPageName' ) ) + '&action=purge';
							} )
							.button( 'option', 'label', mw.msg( 'wikibase-linkitem-close' ) );
					}
				}
			} );
	}

	/**
	 * Called after an entity has succesfully been created.
	 */
	function successfullyCreated() {
		onSuccess( 'create' );
	}

	/**
	 * Called after an entity has succesfully been linked.
	 */
	function successfullyLinked() {
		onSuccess( 'link' );
	}

	/**
	 * Called after an entity has succesfully been linked or created. Replaces the dialog content with a useful
	 * message linking the (new) item.
	 *
	 * @param {string} type ( create or link )
	 */
	function onSuccess( type ) {
		var mwApi = new mw.Api(),
			itemUri = linkRepoTitle( 'Special:ItemByTitle/' + mw.config.get( 'wbCurrentSite' ).globalSiteId + '/' + mw.config.get( 'wgPageName' ) );

		$dialog
			.empty()
			.append(
				$( '<p>' )
					.addClass( 'wbclient-linkItem-success-message' )
					// Messages: wikibase-linkitem-success-create wikibase-linkitem-success-link
					.html( mw.message( 'wikibase-linkitem-success-' + type, itemUri ).parse() )
			)
			.append(
				$( '<p>' )
					.text( mw.msg( 'wikibase-replicationnote' ) )
			);
		removeSpinner();

		// Replace the button with one asking to close the dialog and reload the current page
		$goButton
			.off( 'click' )
			.click( function() {
				showSpinner();
				window.location.reload( true );
			} )
			.button( 'option', 'label', mw.msg( 'wikibase-linkitem-close' ) );

		// Purge this page in the background... we shouldn't confuse the user with the newly added link(s) not being there
		mwApi.post( {
			action: 'purge',
			titles: mw.config.get( 'wgPageName' )
		} );
	}

	/**
	 * Called in case an error occurs and displays an error message.
	 * 
	 * Can either show a given errorCode (as html) or use data from an
	 * API failure (pass two parameters in this case).
	 *
	 * @param {string} errorCode
	 * @param {object} errorInfo
	 */
	function onError( errorCode, errorInfo ) {
		var $elem, tooltip, error;
		if ( $( '#wbclient-linkItem-page' ).length ) {
			$elem = $( '#wbclient-linkItem-page' );
		} else {
			$elem = $( '#wbclient-linkItem-siteLinks' );
		}

		if ( errorInfo ) {
			error = wb.RepoApiError.newFromApiResponse( errorCode, errorInfo );
		} else {
			error = errorCode;
		}

		tooltip = new wb.ui.Tooltip( $elem, {}, error, { gravity: 'nw' } );

		removeSpinner();
		tooltip.show();

		$elem.one( ['click', 'focus'], function() {
			// Remove the tooltip by the time the user tries to correct the input
			tooltip.destroy();
		} );
	}

	/**
	 * Let the user know that the site given is invalid
	 *
	 */
	function invalidSiteGiven() {
		var $linkItemSite = $( '#wbclient-linkItem-Site' ),
			tooltip = new wb.ui.Tooltip( $linkItemSite, {}, mw.msg( 'wikibase-linkitem-invalidsite' ) );

		tooltip.show();
		$linkItemSite.one( 'focus', function() {
			// Remove the tooltip by the time the user tries to correct the input
			tooltip.destroy();
		} );
	}

	/**
	 * Returns a link to the given title on the repo.
	 *
	 * @param {string} title
	 * @return {string}
	 */
	function linkRepoTitle( title ) {
		return mw.config.get( 'wbRepoUrl' ) + mw.config.get( 'wbRepoArticlePath' ).replace( /\$1/, encodeURIComponent( title ) );
	}

	/**
	 * Displays the link which shows the dialog after checking whether the user is logged ins
	 *
	 */
	$( document ).ready( function() {
		$( '#wbc-linkToItem' )
			.text( '' )
			.append(
				$( '<a>' )
					.attr( {
						href: '#',
						id: 'wbc-linkToItem-link'
					} )
					.text( mw.msg( 'wikibase-linkitem-addlinks' ) )
					.click( checkLoggedin )
			);
	} );
} )( wikibase, mediaWiki, jQuery );
