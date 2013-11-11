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
	 * @type wb.linkPages
	 */
	linkPages: null,

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
		// This should be in the most human readable format as this is might be used as label
		pageTitle: (new mw.Title( mw.config.get( 'wgTitle' ), mw.config.get( 'wgNamespaceNumber' ) ) ).getPrefixedText(),
		globalSiteId: mw.config.get( 'wbCurrentSite' ).globalSiteId,
		namespaceNumber: mw.config.get( 'wgNamespaceNumber' ),
		repoArticlePath: mw.config.get( 'wbRepoUrl' ) + mw.config.get( 'wbRepoArticlePath' ),
		// Fallback to the site group of the current site in case .langLinkSiteGroup isn't yet in the cache
		langLinkSiteGroup: mw.config.get( 'wbCurrentSite' ).langLinkSiteGroup || wb.getSite( mw.config.get( 'wbCurrentSite' ).globalSiteId ).getGroup()
	},

	/**
	 * Check whether the user is logged in on both the client and the repo
	 * show the dialog if he is, error if not
	 *
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		var self = this,
			$dialogSpinner = $.createSpinner();

		this.element
		.hide()
		.after( $dialogSpinner );

		this.repoApi.get( {
			action: 'query',
			meta: 'userinfo'
		} )
		.done( function( data ) {
			$dialogSpinner.remove();

			if ( data.query.userinfo.anon !== undefined ) {
				// User isn't logged into the repo
				self._notLoggedin();
				return;
			}

			self._createDialog();
		} )
		.fail( function() {
			$dialogSpinner.remove();
			self.element.show();

			self.element.wbtooltip( {
				content: mw.msg( 'wikibase-error-unexpected' ),
				gravity: 'w'
			} );

			self.element.data( 'wbtooltip' ).show();
			self.element.one( 'click.' + self.widgetName, function() {
				// Remove the tooltip by the time the user clicks the link again.
				self.element.data( 'wbtooltip' ).destroy();
			} );
		} );
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
					text: mw.msg( 'wikibase-linkitem-linkpage' ),
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
	 * Gets an object with all linkable sites despite the current one (as we can't link pages on the same wiki)
	 *
	 * @return {object}
	 */
	_getLinkableSites: function() {
		var sites,
			linkableSites = {},
			site,
			currentSiteId;

		currentSiteId = this.options.globalSiteId;
		sites = wb.getSitesOfGroup( this.options.langLinkSiteGroup );

		for( site in sites ) {
			if ( sites[ site ].getId() !== currentSiteId ) {
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
						'for': 'wbclient-linkItem-site'
					} )
					.text( mw.message( 'wikibase-linkitem-input-site' ) )
			)
			.append(
				$( '<input />' )
					.attr( {
						name: 'wbclient-linkItem-site',
						id: 'wbclient-linkItem-site',
						'class': 'wbclient-linkItem-input'
					} )
					.siteselector( {
						resultSet: this._getLinkableSites()
					} )
					.on( 'siteselectoropen siteselectorclose siteselectorautocomplete blur', $.proxy( function() {
						var apiUrl;

						$( '#wbclient-linkItem-page' )
							.val( '' );

						try {
							apiUrl = $( '#wbclient-linkItem-site' ).siteselector( 'getSelectedSite' ).getApi();
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
										namespace: this.options.namespaceNumber
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
						'for': 'wbclient-linkItem-site'
					} )
					.text( mw.msg( 'wikibase-linkitem-input-page' ) )
			)
			.append(
				$( '<input />' )
					.attr( {
						name: 'wbclient-linkItem-page',
						id: 'wbclient-linkItem-page',
						disabled: 'disabled',
						'class' : 'wbclient-linkItem-input'
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
		if ( $( '#wbclient-linkItem-site' ).siteselector( 'getSelectedSite' ) ) {
			this.targetSite = $( '#wbclient-linkItem-site' ).siteselector( 'getSelectedSite' ).getId();
		} else {
			// This should never happen because the button shouldn't be enabled if the site isn't valid
			// ...keeping this for sanity and paranoia
			this.invalidSiteGiven();
			return;
		}
		this.targetArticle = $( '#wbclient-linkItem-page' ).val();

		this.linkPages = new wb.linkPages(
			this.options.globalSiteId,
			this.options.pageTitle,
			this.targetSite,
			this.targetArticle
		);

		// Show a spinning animation and do an API request
		this._showSpinner();

		this.linkPages.getNewlyLinkedPages()
		.done( $.proxy( this._onConfirmationDataLoad, this ) )
		// This will (as a side effect) also catch errors where the target page doesn't exist
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
							wb.getSite( entity.sitelinks[ i ].site ),
							entity.sitelinks[ i ]
						)
					);
			}
		}
		return $siteLinks;
	},

	/**
	 * Handles the data from getEntitiesByPage and either creates a new item or in case there already is an
	 * item it shows the user a confirmation form
	 *
	 * @param {object} data
	 */
	_onConfirmationDataLoad: function( entity ) {
		var i, entity, itemLink;

		if ( entity && entity.sitelinks ) {
			var siteLinkCount = 0;

			// Show a table with links to the user and ask for confirmation
			itemLink = this._linkRepoTitle( entity.title );

			// Count site links and abort in case the entity already is linked with a page on this wiki
			for ( i in entity.sitelinks ) {
				if ( entity.sitelinks[ i ].site ) {
					siteLinkCount += 1;
					if ( entity.sitelinks[ i ].site === this.options.globalSiteId ) {
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
				this.linkPages.doLink()
				.done( $.proxy( this._onSuccess, this ) )
				.fail( $.proxy( this._onError, this ) );
			} else {
				this._removeSpinner();

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
						// The user confirmed that this is the right item...
						$.proxy( function() {
							this._showSpinner();
							this.linkPages.doLink()
							.done( $.proxy( this._onSuccess, this ) )
							.fail( $.proxy( this._onError, this ) );
						}, this )
					);
			}
		} else {
			this.linkPages.doLink()
			.done( $.proxy( this._onSuccess, this ) )
			.fail( $.proxy( this._onError, this ) );
		}
	},


	/**
	 * Called after an entity has succesfully been linked or created. Replaces the dialog content with a useful
	 * message linking the (new) item.
	 */
	_onSuccess: function() {
		var mwApi = new mw.Api(),
			itemUri = this._linkRepoTitle( 'Special:ItemByTitle/' + this.options.globalSiteId + '/' + this.options.pageTitle );

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
					.html( mw.message( 'wikibase-linkitem-success-link', itemUri ).parse() )
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
			titles: this.options.pageTitle
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
		var error = ( errorInfo )
			? wb.RepoApiError.newFromApiResponse( errorCode, errorInfo )
			: errorCode;

		var $elem = $( '#wbclient-linkItem-page' );

		if ( $elem.length === 0 ) {
			$elem = $( '#wbclient-linkItem-siteLinks' );
		}

		$elem.wbtooltip( {
			content: error,
			permanent: true
		} );

		this._removeSpinner();
		$elem.data( 'wbtooltip' ).show();

		// Remove the tooltip if the user clicks onto the dialog trying to correct the input
		// Also remove the tooltip in case the dialog is getting closed
		this.$dialog.one( 'dialogclose click', function() {
			if ( $elem.data( 'wbtooltip' ) ) {
				$elem.data( 'wbtooltip' ).destroy();
			}
		} );
	},

	/**
	 * Let the user know that the site given is invalid
	 */
	_invalidSiteGiven: function() {
		var $linkItemSite = $( '#wbclient-linkItem-site' );

		$linkItemSite.wbtooltip( { content: mw.msg( 'wikibase-linkitem-invalidsite' ) } );

		$linkItemSite.data( 'wbtooltip' ).show();
		$linkItemSite.one( 'focus', function() {
			// Remove the tooltip by the time the user tries to correct the input
			$linkItemSite.data( 'wbtooltip' ).destroy();
		} );
	},

	/**
	 * Returns a link to the given title on the repo.
	 *
	 * @param {string} title
	 * @return {string}
	 */
	_linkRepoTitle: function( title ) {
		return this.options.repoArticlePath.replace( /\$1/g, mw.util.wikiUrlencode( title ) );
	}
} );

} )( wikibase, mediaWiki, jQuery );
