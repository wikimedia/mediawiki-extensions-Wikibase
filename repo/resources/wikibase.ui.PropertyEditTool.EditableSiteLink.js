/**
 * JavaScript for managing editable representation of site links.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 * @author Daniel Werner
 */
( function( mw, wb, $, undefined ) {
'use strict';
var $PARENT = wb.ui.PropertyEditTool.EditableValue;

/**
 * Serves the input interface for a site link, extends EditableValue.
 * @constructor
 * @see wikibase.ui.PropertyEditTool.EditableValue
 * @since 0.1
 */
wb.ui.PropertyEditTool.EditableSiteLink = wb.utilities.inherit( $PARENT, {
	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.API_VALUE_KEY
	 */
	API_VALUE_KEY: 'sitelinks',

	/**
	 * The part of the editable site link representing the site the selected page belongs to
	 * @var wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface
	 */
	siteIdInterface: null,

	/**
	 * The part of the editable site link representing the link to the site
	 * @var wikibase.ui.PropertyEditTool.EditableValue.SitePageInterface
	 */
	pageNameInterface: null,

	/**
	 * current results received from the api
	 * @var Array
	 */
	_currentResults: null,

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._initInterfaces
	 */
	_initInterfaces: function() {
		$PARENT.prototype._initInterfaces.call( this );
		// make interfaces available for public since they contain interesting data:
		this.siteIdInterface = this._interfaces.siteId;
		this.pageNameInterface = this._interfaces.pageName;
		/* TODO: Setting the language attributes on initialisation will not be required as soon as
		the attributes are already attached in PHP for the non-JS version */
		if ( this.siteIdInterface.getSelectedSite() !== null ) {
			this.pageNameInterface.updateLanguageAttributes(
				this.siteIdInterface.getSelectedSite().getLanguage()
			);
		}
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._buildInterfaces
	 *
	 * @param jQuery subject
	 * @return wikibase.ui.PropertyEditTool.EditableValue.Interface[]
	 */
	_buildInterfaces: function( subject ) {
		var interfaces = [];
		var tableCells = subject.children( 'td' );
		var ev = wb.ui.PropertyEditTool.EditableValue;

		// interface for choosing the source site:
		interfaces.siteId = new ev.SiteIdInterface();
		interfaces.siteId.inputPlaceholder = mw.msg( 'wikibase-sitelink-site-edit-placeholder' );
		interfaces.siteId.ignoredSiteLinks = this.ignoredSiteLinks;
		interfaces.siteId.init( tableCells[0] );

		interfaces.siteId.setActive( this.isPending() ); // site ID will remain once set!

		// interface for choosing a page (from the source site):
		interfaces.pageName = new ev.SitePageInterface(
			tableCells.last(), interfaces.siteId.getSelectedSite()
		);
		interfaces.pageName.inputPlaceholder = mw.msg( 'wikibase-sitelink-page-edit-placeholder' );
		interfaces.pageName.ajaxParams = {
			action: 'opensearch',
			namespace: 0,
			suggest: ''
		};

		interfaces.push( interfaces.siteId );
		interfaces.push( interfaces.pageName );
		return interfaces;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._interfaceHandler_onInputRegistered
	 *
	 * @param relatedInterface wikibase.ui.PropertyEditTool.EditableValue.Interface
	 */
	_interfaceHandler_onInputRegistered: function( relatedInterface ) {
		$PARENT.prototype._interfaceHandler_onInputRegistered.call( this, relatedInterface );

		var idInterface = this._interfaces.siteId;
		var pageInterface = this._interfaces.pageName;

		// set up necessary communication between both interfaces:
		var site = idInterface.getSelectedSite();
		if( site !== pageInterface.getSite() && site !== null ) {
			// FIXME: this has to be done on idInterface.onInputRegistered only but that
			//        is not really possible with the current 'event' system since this function is
			//        registered there.
			pageInterface.setSite( site );

			var siteId = idInterface.getSelectedSiteId();

			// change class names:
			this._subject.removeClassByRegex( /^wb-sitelinks-.+/ );
			this._subject.addClass( 'wb-sitelinks-' + siteId );

			idInterface._getValueContainer().removeClassByRegex( /^wb-sitelinks-site-.+/ );
			idInterface._getValueContainer().addClass( 'wb-sitelinks-site wb-sitelinks-site-' + siteId );

			pageInterface._getValueContainer().removeClassByRegex( /^wb-sitelinks-link-.+/ );
			pageInterface._getValueContainer().addClass( 'wb-sitelinks-link wb-sitelinks-link-' + siteId );
			// directly updating the page interface's language attributes when a site is selected
			pageInterface.updateLanguageAttributes( site.getLanguage() );
		}

		// only enable site page selector if there is a valid site id selected
		pageInterface.setDisabled( ! idInterface.isValid() );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._getValueFromApiResponse
	 *
	 * @param array response
	 * @return string|null
	 */
	_getValueFromApiResponse: function( response ) {
		var siteId = this._interfaces.siteId.getSelectedSite().getId();
		var normalizedTitle = response[ this.API_VALUE_KEY ][ this._interfaces.siteId.getSelectedSite().getGlobalSiteId() ].title;
		return [ siteId, normalizedTitle ];
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._getToolbarParent
	 *
	 * @return jQuery node the toolbar should be appended to
	 */
	_getToolbarParent: function() {
		// append toolbar to new td
		this.__toolbarParent = this.__toolbarParent || $( '<td/>' ).appendTo( this._subject );
		return this.__toolbarParent;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.startEditing
	 *
	 * @return bool will return false if edit mode is active already.
	 */
	startEditing: function() {
		// set ignored site links again since they could have changed
		this._interfaces.siteId.ignoredSiteLinks = this.ignoredSiteLinks;
		return $PARENT.prototype.startEditing.call( this );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.stopEditing
	 *
	 * @param bool save whether to save the current value
	 * @return jQuery.Promise
	 */
	stopEditing: function( save ) {
		var promise = $PARENT.prototype.stopEditing.call( this, save );
		/*
		 * prevent siteId input element from appearing again when triggering edit on a just created
		 * site link without having reloaded the page; however, it is required to check whether the
		 * corresponding interface (still) exists since it might have been destroyed when - instead
		 * of being saved - the pending value got removed again by cancelling
		 */
		promise.done( $.proxy( function() {
			if ( this._interfaces !== null ) {
				this._interfaces.siteId.setActive( this.isPending() );
			}
		}, this ) );
		return promise;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.getInputHelpMessage
	 *
	 * @return string tooltip help message
	 */
	getInputHelpMessage: function() {
		return mw.msg( 'wikibase-sitelinks-input-help-message' );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.getApiCallParams
	 *
	 * @param number apiAction
	 * @return Object containing the API call specific parameters
	 */
	getApiCallParams: function( apiAction ) {
		var params = $PARENT.prototype.getApiCallParams.call( this, apiAction );
		params = $.extend( params, {
			action: 'wbsetsitelink',
			baserevid: mw.config.get( 'wgCurRevisionId' ),
			linksite: this.siteIdInterface.getSelectedSite().getGlobalSiteId(),
			linktitle: ( apiAction === this.API_ACTION.REMOVE || apiAction === this.API_ACTION.SAVE_TO_REMOVE ) ? '' : this.getValue()[1]
		} );
		delete( params.link ); // ? danwe: why is there an 'item' AND a 'link' param here?
		delete( params.item ); // ? danwe: why is there an 'item' AND a 'link' param here?
		delete( params.language );

		return params;
	},

	/////////////////
	// CONFIGURABLE:
	/////////////////

	/**
	 * determines whether to keep an empty form when leaving edit mode
	 * @see wikibase.ui.PropertyEditTool.EditableValue
	 * @var bool
	 */
	preserveEmptyForm: false,

	/**
	 * Allows to specify an array with sites which should not be allowed to choose
	 * @var wikibase.Site[]
	 */
	ignoredSiteLinks: null
} );

} )( mediaWiki, wikibase, jQuery );
