/**
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
( function( wb, mw, $ ) {
'use strict';

/**
 * This widget allows linking articles with Wikibase items or creating new wikibase items directly
 * in client wikis.
 * The widget can take a couple of arguments to make it work on pages and sites other than the
 * current one. All these options default to global state / the current page's attributes.
 * @since 0.4
 *
 * @option mwApiForRep {mediaWiki.Api} A mw.Api instance configured to use the repo's API.
 *
 * @option pageTitle {string} Title of the page to link.
 *
 * @option globalSiteId {string} Id of the site the given page is on.
 *
 * @option namespaceNumber {number} Number of the namespace the given title is in.
 *         This is used to determine the linkable pages on the target wiki.
 *
 * @option repoArticlePath {string} Article path (like wgArticlePath) for the repo.
 *
 * @option langLinkSiteGroup {string} Group of sites we allow the user to link the given page with.
 *
 * @event dialogclose: Triggered when the interaction dialog is closed.
 *        (1) {jQuery.Event}
 *
 * @event success: Triggered when pages have been linked successfully.
 *        (1) {jQuery.Event}
 */
$.widget( 'wikibase.linkitem', {
	/**
	 * @type wikibase.PageConnector
	 */
	_pageConnector: null,

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
	 * Options
	 * @see jQuery.Widget.options
	 */
	options: {
		mwApiForRepo: null,
		pageTitle: null,
		globalSiteId: null,
		namespaceNumber: null,
		repoArticlePath: null,
		langLinkSiteGroup: null
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

		this.options.mwApiForRepo.get( {
			action: 'query',
			meta: 'userinfo'
		} )
		.done( function( data ) {
			$dialogSpinner.remove();

			if ( data.query.userinfo.anon !== undefined ) {
				// User isn't logged into the repo
				self._notLoggedIn();
				return;
			}

			self._createDialog();
			$( '#wbclient-linkItem-site' ).focus();
		} )
		.fail( function( errorCode, errorInfo ) {
			$dialogSpinner.remove();
			self.element.show();

			self.element.wbtooltip( {
				content: mw.msg( 'wikibase-error-unexpected',
					( errorInfo.error && errorInfo.error.info ) || errorInfo.exception ),
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
	_notLoggedIn: function() {
		var self = this;

		var userLogin = this._linkRepoTitle( 'Special:UserLogin' );
		$( '<div>' )
		.dialog( {
			title: mw.msg( 'wikibase-linkitem-not-loggedin-title' ),
			width: 400,
			height: 200,
			resizable: true
		} )
		.on( 'dialogclose', function() {
			self._trigger( 'dialogclose' );
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
					click: $.proxy( this._secondStep, this )
				} ],
				modal: true
			} )
			// Use .on instead of passing this to dialog() as close as we want to be able to remove
			// it later:
			.on( 'dialogclose', $.proxy( function() {
				this.element.show();
				this._trigger( 'dialogclose' );
			}, this ) )
			.append( $( '<p>' ) .text( mw.message( 'wikibase-linkitem-selectlink' ).escaped() ) )
			.append( this._createSiteLinkForm() );

		this.$goButton = $( '#wbclient-linkItem-goButton' );
	},

	/**
	 * @see jQuery.Widget.destroy
	 */
	destroy: function() {
		if ( this.$dialog && this.$dialog.length ) {
			this.$dialog.remove();
		}
		if ( this.$spinner && this.$spinner.length ) {
			this.$spinner.remove();
		}
		if ( this.$goButton && this.$goButton.length ) {
			this.$goButton.remove();
		}

		$.Widget.prototype.destroy.call( this );

		// FIXME: The destroy() method should be final. Re-showing the element should be done
		// outside the scope of destroy().
		this.element.show();
	},

	/**
	 * Creates a form for selecting the site and the page to link in a user-friendly manner (with
	 * auto-completion).
	 *
	 * @return {jQuery}
	 */
	_createSiteLinkForm: function() {
		return $( '<form>' )
		.attr( 'name', 'wikibase-linkItem-form' )
		.append( this._createSiteInput() )
		.append( $( '<br />' ) )
		.append( this._createPageInput() );
	},

	/**
	 * Creates a labeled input box for selecting client sites.
	 *
	 * @return {jQuery}
	 */
	_createSiteInput: function() {
		return $( '<label>' )
		.attr( 'for', 'wbclient-linkItem-site' )
		.text( mw.msg( 'wikibase-linkitem-input-site' ) )
		.add(
			$( '<input />' )
			.attr( {
				name: 'wbclient-linkItem-site',
				id: 'wbclient-linkItem-site',
				'class': 'wbclient-linkItem-input'
			} )
			.siteselector( {
				source: this._getLinkableSites()
			} )
			.on(
				'siteselectoropen siteselectorclose siteselectorautocomplete blur',
				$.proxy( this._onSiteSelectorChangeHandler, this )
			)
		);
	},

	/**
	 * Gets an object with all linkable sites despite the current one (as pages on the same wiki
	 * cannot be linked).
	 *
	 * @return {object}
	 */
	_getLinkableSites: function() {
		var sites,
			linkableSites = [],
			site,
			currentSiteId;

		currentSiteId = this.options.globalSiteId;
		sites = wb.sites.getSitesOfGroup( this.options.langLinkSiteGroup );

		for( site in sites ) {
			if ( sites[ site ].getId() !== currentSiteId ) {
				linkableSites.push( sites[ site ] );
			}
		}

		return linkableSites;
	},

	/**
	 * Handles changes to the siteselector
	 */
	_onSiteSelectorChangeHandler: function() {
		var apiUrl,
			$page = $( '#wbclient-linkItem-page' );

		$page.val( '' );

		try {
			apiUrl = $( '#wbclient-linkItem-site' ).siteselector( 'getSelectedSite' ).getApi();
		} catch( e ) {
			// Invalid input (likely incomplete). Disable the page input an re-disable to button
			$page.attr( 'disabled', 'disabled' );
			this.$goButton.button( 'disable' );
			return;
		}

		// If the language gets changed the yet selected page is no longer available so we clear the
		// input element. Furthermore, we remove the old suggestor (if there's one) and create a new
		// one working on the right wiki.
		$page
		.removeAttr( 'disabled' )
		.suggester( {
			source: function( term ) {
				var deferred = $.Deferred();

				$.ajax( {
					url: apiUrl,
					dataType: 'jsonp',
					data: {
						search: term,
						action: 'opensearch'
					},
					timeout: 8000
				} )
				.done( function( response ) {
					deferred.resolve( response[1], response[0] );
				} )
				.fail( function( jqXHR, textStatus ) {
					deferred.reject( textStatus );
				} );

				return deferred.promise();
			}
		} );
	},

	/**
	 * Creates a labeled input box for selecting pages on a client site.
	 *
	 * @return {jQuery}
	 */
	_createPageInput: function() {
		return $( '<label>' )
		.attr( 'for', 'wbclient-linkItem-page' )
		.text( mw.msg( 'wikibase-linkitem-input-page' ) )
		.add(
			$( '<input />' )
			.attr( {
				name: 'wbclient-linkItem-page',
				id: 'wbclient-linkItem-page',
				disabled: 'disabled',
				'class' : 'wbclient-linkItem-input'
			} )
			.on( 'focus', $.proxy( function () {
				// Enable the button by the time the user uses this field
				this.$goButton.button( 'enable' );
			}, this ) )
		);
	},

	/**
	 * Called after the user specified site and a page name. Looks up any existing items or tries to
	 * link the currently viewed page with an existing item.
	 */
	_secondStep: function() {
		this.targetSite = $( '#wbclient-linkItem-site' ).siteselector( 'getSelectedSite' ).getId();
		this.targetArticle = $( '#wbclient-linkItem-page' ).val();

		this._pageConnector = new wb.PageConnector(
			new wb.RepoApi( this.options.mwApiForRepo ),
			this.options.globalSiteId,
			this.options.pageTitle,
			this.targetSite,
			this.targetArticle
		);

		// Show a spinning animation and do an API request
		this._showSpinner();

		this._pageConnector.getNewlyLinkedPages()
		.done( $.proxy( this._onConfirmationDataLoad, this ) )
		// This will (as a side effect) also catch errors where the target page doesn't exist:
		.fail( $.proxy( this._onError, this ) );
	},

	/**
	 * Replaces the $goButton button with a loading spinner.
	 */
	_showSpinner: function() {
		this.$spinner = $.createSpinner();
		this.$goButton
			.hide()
			.after( this.$spinner );
	},

	/**
	 * Removes the spinner created with _showSpinner and shows the original button again.
	 */
	_removeSpinner: function() {
		if ( !this.$spinner || !this.$spinner.length ) {
			return;
		}
		this.$spinner.remove();
		this.$goButton.show();
	},

	/**
	 * Handles the data from getNewlyLinkedPages and either creates a new item or shows the user a
	 * confirmation form in case an item exists already.
	 *
	 * @param {object} entity
	 */
	_onConfirmationDataLoad: function( entity ) {
		var i, itemLink;

		if ( entity && entity.sitelinks ) {
			var siteLinkCount = 0;

			// Show a table with links to the user and ask for confirmation
			itemLink = this._linkRepoTitle( entity.title );

			// Count site links and abort in case the entity already is linked with a page on this
			// wiki:
			for ( i in entity.sitelinks ) {
				if ( entity.sitelinks[ i ].site ) {
					siteLinkCount += 1;
					if ( entity.sitelinks[ i ].site === this.options.globalSiteId ) {
						// Abort as the entity already is linked with a page on this wiki
						this._onError( mw.message(
							'wikibase-linkitem-alreadylinked',
							itemLink,
							entity.sitelinks[ i ].title
						).parse() );
						return;
					}
				}
			}

			if ( siteLinkCount === 1 ) {
				// The item we want to link with only has a single sitelink so we don't have to ask
				// for confirmation:
				this._pageConnector.linkPages()
				.done( $.proxy( this._onSuccess, this ) )
				.fail( $.proxy( this._onError, this ) );
			} else {
				// Let the user verify this is indeed the entity to link with and link it after.
				this._removeSpinner();
				this._userConfirmEntity( entity, siteLinkCount, itemLink );
			}
		} else {
			this._pageConnector.linkPages()
			.done( $.proxy( this._onSuccess, this ) )
			.fail( $.proxy( this._onError, this ) );
		}
	},

	/**
	 * Let the user verify this is indeed the entity to link with and link it after.
	 *
	 * @param {object} entity
	 * @param {number} siteLinkCount Number of sitelinks attached to the entity
	 * @param {string} itemLink Link to the entity on the repo
	 */
	_userConfirmEntity: function( entity, siteLinkCount, itemLink ) {
		var self = this,
			confirmationMsg = mw.message(
				'wikibase-linkitem-confirmitem-text',
				itemLink,
				siteLinkCount
			).parse();

		this.$dialog
			.empty()
			.append( $( '<div>' ).html( confirmationMsg ) )
			.append( $( '<br />' ) )
			.append( this._createSiteLinkTable( entity ) );

		this.$goButton
			.off( 'click' )
			.button( 'option', 'label', mw.msg( 'wikibase-linkitem-confirmitem-button' ) )
			.click( function() {
				// The user confirmed that this is the right item...
				self._showSpinner();
				self._pageConnector.linkPages()
				.done( $.proxy( self._onSuccess, self ) )
				.fail( $.proxy( self._onError, self ) );
			} );
	},

	/**
	 * Creates a table with all sitelinks linked to an entity.
	 *
	 * @param {Object} entity
	 *
	 * @return {jQuery}
	 */
	_createSiteLinkTable: function( entity )  {
		var i, $siteLinks;

		$siteLinks = $( '<div>' )
			.attr( 'id', 'wbclient-linkItem-siteLinks' )
			.append( $( '<table>' ) );

		// Table head
		$( '<thead>' )
		.append(
			$( '<tr>' )
			.append( $( '<th>' ).text( mw.msg( 'wikibase-sitelinks-sitename-columnheading' ) ) )
			.append( $( '<th>' ).text( mw.msg( 'wikibase-sitelinks-link-columnheading' ) ) )
		)
		.appendTo( $siteLinks.find( 'table' ) );

		// Table body
		for ( i in entity.sitelinks ) {
			if ( entity.sitelinks[ i ].site ) {
				// Show a row for each page that is linked with the current entity
				$siteLinks
				.find( 'table' )
				.append(
					this._createSiteLinkRow(
						wb.sites.getSite( entity.sitelinks[ i ].site ),
						entity.sitelinks[ i ]
					)
				);
			}
		}
		return $siteLinks;
	},

	/**
	 * Creates a table row for a site link.
	 *
	 * @param {wb.Site} site
	 * @param {object} entitySitelinks
	 *
	 * @return {jQuery}
	 */
	_createSiteLinkRow: function( site, entitySitelinks ) {
		return $( '<tr>' )
			.append(
				$( '<td>' )
				.addClass( 'wbclient-linkItem-column-site' )
				.text( site.getName() )
				.css( 'direction', site.getLanguageDirection() )
			)
			.append(
				$( '<td>' )
				.addClass( 'wbclient-linkItem-column-page' )
				.append( site.getLinkTo( entitySitelinks.title ) )
				.css( 'direction', site.getLanguageDirection() )
			);
	},

	/**
	 * Called after an entity has successfully been linked or created. Replaces the dialog content
	 * with a useful message linking the (new) item.
	 */
	_onSuccess: function() {
		var mwApi = new mw.Api(),
			itemUri = this._linkRepoTitle(
				'Special:ItemByTitle/' + this.options.globalSiteId + '/' + this.options.pageTitle
			);

		this.$dialog
			.empty()
			.append(
				$( '<p>' )
				.addClass( 'wbclient-linkItem-success-message' )
				.html( mw.message( 'wikibase-linkitem-success-link', itemUri ).parse() )
			)
			.append( $( '<p>' ).text( mw.msg( 'wikibase-replicationnote' ) ) );

		this._removeSpinner();

		// Replace the button with one asking to close the dialog and reload the current page
		this.$goButton
			.off( 'click' )
			.click( $.proxy( function() {
				this._showSpinner();
				window.location.reload( true );
			}, this ) )
			.button( 'option', 'label', mw.msg( 'wikibase-linkitem-close' ) );

		// Purge this page in the background... we shouldn't confuse the user with the newly added
		// link(s) not being there:
		mwApi.post( {
			action: 'purge',
			titles: this.options.pageTitle
		} );

		this._trigger( 'success' );
	},

	/**
	 * Called in case an error occurs and displays an error message.
	 *
	 * Can either show a given errorCode (as html) or use data from an
	 * API failure (pass two parameters in this case).
	 *
	 * @param {string} errorCode
	 * @param {Object} [errorInfo]
	 */
	_onError: function( errorCode, errorInfo ) {
		var error = ( errorInfo )
			? wb.RepoApiError.newFromApiResponse( errorInfo )
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
	 * Returns a link to the given title on the repo.
	 *
	 * @param {string} title
	 *
	 * @return {string}
	 */
	_linkRepoTitle: function( title ) {
		return this.options.repoArticlePath.replace( /\$1/g, mw.util.wikiUrlencode( title ) );
	}
} );

} )( wikibase, mediaWiki, jQuery );
