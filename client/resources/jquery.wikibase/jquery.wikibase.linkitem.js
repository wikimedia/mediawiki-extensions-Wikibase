/**
* JavaScript that allows linking articles with Wikibase items or creating
* new wikibase items directly in the client wikis
*
* @since 0.4
*
* @author Marius Hoch < hoo@online.de >
*/
( function( wb, mw, $ ) {
'use strict';

$.widget( 'wikibase.linkitem', {
	/**
	 * @type wb.RepoApi
	 */
	repoApi: new wb.RepoApi(),

	/**
	 * @type jQuery
	 */
	$dialog: null,

	/**
	 * Spinner (set if there's something ongoing)
	 * @type jQuery
	 */
	$spinner: null,

	/**
	 * Button to go on (next step)
	 * @type jQuery
	 */
	$goButton: null,

	/**
	 * Global ID of the site to link with
	 * @type {string}
	 */
	targetSite: null,

	/**
	 * Name of the page title to link with
	 * @type {string}
	 */
	targetArticle: null,

	/**
	 * (Additional) default options
	 * @see jQuery.Widget.options
	 */
	options: {
		// ...
	},

	/**
	 * Check whether the user is logged in on both the client and the repo
	 * show the dialog if he is, error if not
	 *
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		var $dialogSpinner = $.createSpinner(),
			$linkItemLink = this.element;

		$linkItemLink
			.hide()
			.after( $dialogSpinner );

		this.repoApi.get( {
			action: 'query',
			meta: 'userinfo'
		} )
		.done(
			$.proxy( function( data ) {
				$dialogSpinner.remove();

				if ( data.query.userinfo.anon !== undefined ) {
					// User isn't logged into the repo
					this._notLoggedin();
					return;
				}

				this._createDialog();
			}, this )
		)
		.fail(
			$.proxy( function() {
				$dialogSpinner.remove();
				$linkItemLink.show();

				var tooltip = new wb.ui.Tooltip( $linkItemLink, {}, mw.msg( 'wikibase-error-unexpected' ), { gravity: 'w' } );

				tooltip.show();
				$linkItemLink.one( 'click', function() {
					// Remove the tooltip by the time the user tries it again
					tooltip.destroy();
				} );
			}, this )
		);
	},

	/**
	 * Show an error to the user in case he isn't logged in on both the client and the repo
	 */
	_notLoggedin: function() {
		this.element.show();

		var userLogin = this._linkRepoTitle( 'Special:UserLogin' );
		$( '<div>' )
			.dialog( {
				title: mw.msg( 'wikibase-linkitem-not-loggedin-title' ),
				width: 400,
				height: 200,
				resizable: true,
				close: $.proxy( this._onDialogClose, this )
			} )
			.append(
				$( '<p>' )
					.addClass( 'wbclient-linkItem-not-loggedin-message' )
					.html( mw.message( 'wikibase-linkitem-not-loggedin', userLogin ).parse() )
			);
	},

	/**
	 * Create the dialog asking for a page the user wants to link with the current one
	 */
	_createDialog: function() {
		this.$dialog = $( '<div>' )
			.attr( 'id', 'wbclient-linkItem-dialog' )
			.dialog( {
				title: mw.message( 'wikibase-linkitem-title' ).escaped(),
				width: 500,
				resizable: false,
				buttons: [ {
					text: mw.message( 'wikibase-linkitem-linkpage' ).escaped(),
					id: 'wbclient-linkItem-goButton',
					disabled: 'disabled',
					click: $.proxy( this._onSecondStep, this )
				} ],
				modal: true
			} )
			// Use .on instead of passing this to dialog() as close: as we want to be able to remove it later
			.on( 'dialogclose', $.proxy( this._onDialogClose, this ) )
			.append(
				$( '<p>' )
					.text( mw.message( 'wikibase-linkitem-selectlink' ).escaped() )
			)
			.append( this._getSiteLinkForm() );

		this.$goButton = $( '#wbclient-linkItem-goButton' );
	},

	/**
	 * Called by the time the dialog get's closed.
	 */
	_onDialogClose: function() {
		if ( this.$dialog && this.$dialog.length ) {
			this.$dialog.remove();
		}
		if ( this.$spinner && this.$spinner.length ) {
			this.$spinner.remove();
		}

		this.destroy();
		this.element.show();
	},

	/**
	 * Gets an object with all sites despite the current one (as we can't link pages on the same wiki)
	 *
	 * @return {object}
	 */
	_getLinkableSites: function() {
		var sites = wb.getSites(),
			linkableSites = {},
			site;
		for( site in sites ) {
			if ( sites[ site ].getGlobalSiteId() !== mw.config.get( 'wbCurrentSite' ).globalSiteId ) {
				linkableSites[ site ] = sites[ site ];
			}
		}
		return linkableSites;
	},

	/**
	 * Get a form for selecting the site and the page to link in a user friendly manner (with autocompletion)
	 *
	 * @return {jQuery}
	 */
	_getSiteLinkForm: function() {
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
						resultSet: this._getLinkableSites()
					} )
					.on( 'siteselectoropen siteselectorclose siteselectorautocomplete blur', $.proxy( function() {
						var apiUrl;

						$( '#wbclient-linkItem-page' )
							.val( '' );

						try {
							apiUrl = $( '#wbclient-linkItem-Site' ).siteselector( 'getSelectedSite' ).getApi();
						} catch( e ) {
							// Invalid input (likely incomplete). Disable the page input an re-disable to button
							$( '#wbclient-linkItem-page' )
								.attr( 'disabled', 'disabled' );
							this.$goButton.button( 'disable' );
							return;
						}
						// If the language gets changed the yet selected page is no longer available so we clear the input element.
						// Furthermore we remove the old suggestor (if there's one) and create a new one working on the right wiki
						$( '#wbclient-linkItem-page' )
							.removeAttr( 'disabled' )
							.suggester( {
								ajax: {
									url: apiUrl,
									params: {
										action: 'opensearch',
										namespace: mw.config.get( 'wgNamespaceNumber' )
									}
								}
							} );
					}, this ) )
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
					.on(
						'focus',
						$.proxy( function () {
							// Enable the button by the time the user uses this field
							this.$goButton.button( 'enable' );
						}, this )
					)
			);
	},

	/**
	 * Let the user know that we're currently doing something by
	 * replacing the go on button with a spinning animation
	 */
	_showSpinner: function() {
		this.$spinner = $.createSpinner();
		this.$goButton
			.hide()
			.after( this.$spinner );
	},

	/**
	 * Remove the spinner created with showSpinner and show the original button again
	 */
	_removeSpinner: function() {
		if ( !this.$spinner || !this.$spinner.length ) {
			return;
		}
		this.$spinner.remove();
		this.$goButton.show();
	},

	/**
	 * Create a table row for a site link
	 *
	 * @param {wikibase.Site} site
	 * @param {object} entitySitelinks
	 * @return {jQuery}
	 */
	_siteLinkRow: function( site, entitySitelinks ) {
		return $( '<tr>' )
			.append(
				$( '<td>' )
					.addClass( 'wbclient-linkItem-column-site' )
					.text( site.getName() )
					.css( 'direction', site.getLanguage().dir )
			)
			.append(
				$( '<td>' )
					.addClass( 'wbclient-linkItem-column-page' )
					.append(
						site.getLinkTo( entitySitelinks.title )
					)
					.css( 'direction', site.getLanguage().dir )
			);
	},

	/**
	* Called after the user gave us a language and a page name. Looks up any existing items then or
	* tries to link the currently viewed page with an existing item
	*/
	_onSecondStep: function() {
		if ( $( '#wbclient-linkItem-Site' ).siteselector( 'getSelectedSite' ) ) {
			this.targetSite = $( '#wbclient-linkItem-Site' ).siteselector( 'getSelectedSite' ).getGlobalSiteId();
		} else {
			// This should never happen because the button shouldn't be enabled if the site isn't valid
			// ...keeping this for sanity and paranoia
			this.invalidSiteGiven();
			return;
		}
		this.targetArticle = $( '#wbclient-linkItem-page' ).val();

		// Show a spinning animation and do an API request
		this._showSpinner();

		this.repoApi.getEntitiesByPage( this.targetSite, this.targetArticle, ['info', 'sitelinks'], mw.config.get( 'wgUserLanguage' ), 'sitelinks', 'ascending' )
			.done( $.proxy( this._onEntityLoad, this ) )
			.fail( $.proxy( this._onError, this ) );
	},

	/**
	 * Returns a table with all sitelinks linked to an entity
	 *
	 * @param {object} entity
	 * @return {jQuery}
	 */
	_siteLinkTable: function( entity )  {
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
						this._siteLinkRow(
							wb.getSiteByGlobalId( entity.sitelinks[ i ].site ),
							entity.sitelinks[ i ]
						)
					);
			}
		}
		return $siteLinks;
	},

	/**
	 * Get the entity for the current page in case there is one
	 *
	 * @param {jQuery.promise}
	 */
	getEntityForCurrentPage: function() {
		return this.repoApi.getEntitiesByPage(
			mw.config.get( 'wbCurrentSite' ).globalSiteId,
			mw.config.get( 'wgPageName' ),
			['info', 'sitelinks'],
			mw.config.get( 'wgUserLanguage' ),
			'sitelinks',
			'ascending'
		);
	},

	/**
	 * Handles the data from getEntitiesByPage and either creates a new item or in case there already is an
	 * item it shows the user a confirmation form
	 *
	 * @param {object} data
	 */
	_onEntityLoad: function( data ) {
		var i, entity, itemLink;

		if ( !data.entities['-1'] ) {
			this._removeSpinner();

			var siteLinkCount = 0;
			// Show a table with links to the user and ask for confirmation
			for ( i in data.entities ) {
				if ( data.entities[ i ].sitelinks ) {
					entity = data.entities[ i ];
					break;
				}
			}
			itemLink = this._linkRepoTitle( entity.title );

			// Count site links and abort in case the entity already is linked with a page on this wiki
			for ( i in entity.sitelinks ) {
				if ( entity.sitelinks[ i ].site ) {
					siteLinkCount += 1;
					if ( entity.sitelinks[ i ].site === mw.config.get( 'wbCurrentSite' ).globalSiteId ) {
						// Abort as the entity already is linked with a page on this wiki
						this._onError(
							mw.message( 'wikibase-linkitem-alreadylinked', itemLink, entity.sitelinks[ i ].title ).parse()
						);
						return;
					}
				}
			}

			if ( siteLinkCount === 1 ) {
				// The item we want to link with only has a single langlink so we don't have to ask for confirmation
				this._linkWithEntity( entity );
			} else {

				this.$dialog
					.empty()
					.append(
						$( '<div>' )
							.html( mw.message( 'wikibase-linkitem-confirmitem-text', itemLink, siteLinkCount ).parse() )
					).append(
						$( '<br />' )
					)
					.append(
						this._siteLinkTable( entity )
					);

				this.$goButton
					.off( 'click' )
					.button( 'option', 'label', mw.msg( 'wikibase-linkitem-confirmitem-button' ) )
					.click(
						$.proxy(
							function () {
								// The user confirmed that this is the right item...
								this._linkWithEntity( entity );
							},
							this
						)
					);
			}
		} else {
			// There is no item for the page the user wants to link
			// Maybe there's one for the current page though (without other links then)

			this.getEntityForCurrentPage()
				.fail( $.proxy( this._onError, this ) )
				.done( $.proxy( function( data ) {
					if ( data.entities['-1'] ) {
						// There's no item yet, create one

						// JSON data for the new entity
						var entityData = {
							labels: {},
							sitelinks: {}
						}, title;

						title = new mw.Title(
							mw.config.get( 'wgTitle' ), mw.config.get( 'wgNamespaceNumber' )
						);

						// Label (page title)
						entityData.labels[ mw.config.get( 'wgContentLanguage' ) ] = {
							language: mw.config.get( 'wgContentLanguage' ),
							value: title.getPrefixedText()
						};
						// Link this page
						entityData.sitelinks[ mw.config.get( 'wbCurrentSite' ).globalSiteId ] = {
							site: mw.config.get( 'wbCurrentSite' ).globalSiteId,
							title: mw.config.get( 'wgPageName' )
						};
						// ...and the one given by the user
						entityData.sitelinks[ this.targetSite ] = {
							site: this.targetSite,
							title: this.targetArticle
						};
						this.repoApi.createEntity( 'item', entityData )
							.done( $.proxy( this._successfullyCreated, this ) )
							.fail( $.proxy( this._onError, this ) );
					} else {
						// There already is an entity with the current page linked
						// but it's empty (=only has the current page linked) cause this dialog isn't shown on pages with langlinks
						var i, entity;

						for ( i in data.entities ) {
							if ( data.entities[ i ].title ) {
								entity = data.entities[ i ];
								break;
							}
						}

						this.repoApi.setSitelink(
							entity.id,
							entity.lastrevid,
							this.targetSite,
							this.targetArticle
						)
						.done( $.proxy( this._successfullyLinked, this ) )
						.fail( $.proxy( this._onError, this ) );
					}
				}, this ) );
		}
	},

	/**
	 * Links the current page with the given entity. If the current page yet is linked with an item we have to unlink it first.
	 * This is only going to happen if the current page is linked with an item which only has the current item linked to prevent conflicts
	 *
	 * @param {object} entity
	 */
	_linkWithEntity: function( entity ) {
		this._showSpinner();
		this.getEntityForCurrentPage()
			.fail( $.proxy( this._onError, this ) )
			.done( $.proxy( function( data ) {

				/**
				 * Link the item with the current page
				 */
				var doLink = $.proxy( function() {
					this.repoApi.setSitelink(
						entity.id,
						entity.lastrevid,
						mw.config.get( 'wbCurrentSite' ).globalSiteId,
						mw.config.get( 'wgPageName' )
					)
					.done( $.proxy( this._successfullyLinked, this ) )
					.fail( $.proxy( this._onError, this ) );
				}, this );

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
						this.repoApi.removeSitelink( selfEntity.id, selfEntity.lastrevid, mw.config.get( 'wbCurrentSite' ).globalSiteId )
							.done( $.proxy( doLink, this ) )
							.fail( $.proxy( this._onError, this ) );
					} else {
						// The current page already is linked with an item which is linked with other pages... this probably some kind of edit conflict.
						// Show an error and let the user purge the page
						var tooltip = new wb.ui.Tooltip(
							this.$goButton,
							{},
							mw.msg( 'wikibase-linkitem-failure' ),
							{ gravity: 'nw' }
						);

						this._removeSpinner();
						tooltip.show();

						// Replace the button with one asking to close the dialog and reload the current page
						this.$goButton
							.off( 'click' )
							.click(
								$.proxy( function() {
									this._showSpinner();
									window.location.href = mw.config.get( 'wgServer' ) + mw.config.get('wgScript' ) + '?title=' + encodeURIComponent( mw.config.get( 'wgPageName' ) ) + '&action=purge';
								}, this )
							)
							.button( 'option', 'label', mw.msg( 'wikibase-linkitem-close' ) );
					}
				}
			}, this ) );
	},

	/**
	 * Called after an entity has succesfully been created.
	 */
	_successfullyCreated: function() {
		this._onSuccess( 'create' );
	},

	/**
	 * Called after an entity has succesfully been linked.
	 */
	_successfullyLinked: function() {
		this._onSuccess( 'link' );
	},

	/**
	 * Called after an entity has succesfully been linked or created. Replaces the dialog content with a useful
	 * message linking the (new) item.
	 *
	 * @param {string} type ( create or link )
	 */
	_onSuccess: function( type ) {
		var mwApi = new mw.Api(),
			itemUri = this._linkRepoTitle( 'Special:ItemByTitle/' + mw.config.get( 'wbCurrentSite' ).globalSiteId + '/' + mw.config.get( 'wgPageName' ) );

		this.$dialog
			.empty()
			// Don't reshow the "Add links" link but reload the page on dialog close
			.off( 'dialogclose' )
			.on( 'dialogclose', function() {
				window.location.reload( true );
			} )
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
		this._removeSpinner();

		// Replace the button with one asking to close the dialog and reload the current page
		this.$goButton
			.off( 'click' )
			.click(
				$.proxy( function() {
					this._showSpinner();
					window.location.reload( true );
				}, this )
			)
			.button( 'option', 'label', mw.msg( 'wikibase-linkitem-close' ) );

		// Purge this page in the background... we shouldn't confuse the user with the newly added link(s) not being there
		mwApi.post( {
			action: 'purge',
			titles: mw.config.get( 'wgPageName' )
		} );
	},

	/**
	 * Called in case an error occurs and displays an error message.
	 *
	 * Can either show a given errorCode (as html) or use data from an
	 * API failure (pass two parameters in this case).
	 *
	 * @param {string} errorCode
	 * @param {object} errorInfo
	 */
	_onError: function( errorCode, errorInfo ) {
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

		this._removeSpinner();
		tooltip.show();

		// Remove the tooltip if the user clicks onto the dialog trying to correct the input
		// Also remove the tooltip in case the dialog is getting closed
		this.$dialog.on( 'dialogclose click', function() {
			tooltip.destroy();
		} );
	},

	/**
	 * Let the user know that the site given is invalid
	 *
	 */
	_invalidSiteGiven: function() {
		var $linkItemSite = $( '#wbclient-linkItem-Site' ),
			tooltip = new wb.ui.Tooltip( $linkItemSite, {}, mw.msg( 'wikibase-linkitem-invalidsite' ) );

		tooltip.show();
		$linkItemSite.one( 'focus', function() {
			// Remove the tooltip by the time the user tries to correct the input
			tooltip.destroy();
		} );
	},

	/**
	 * Returns a link to the given title on the repo.
	 *
	 * @param {string} title
	 * @return {string}
	 */
	_linkRepoTitle: function( title ) {
		return mw.config.get( 'wbRepoUrl' ) + mw.config.get( 'wbRepoArticlePath' ).replace( /\$1/g, mw.util.wikiUrlencode( title ) );
	}
} );

} )( wikibase, mediaWiki, jQuery );
