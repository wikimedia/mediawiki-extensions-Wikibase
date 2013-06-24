/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
'use strict';
/* jshint camelcase: false */

var PARENT = wb.ui.PropertyEditTool.EditableValue;

/**
 * Serves the input interface for a site link, extends EditableValue.
 *
 * @example http://pastebin.com/EbxsCtrV
 *
 * @constructor
 * @extends wb.ui.PropertyEditTool.EditableValue
 * @since 0.1
 */
var SELF = wb.ui.PropertyEditTool.EditableSiteLink = wb.utilities.inherit( PARENT, {
	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.API_VALUE_KEY
	 */
	API_VALUE_KEY: 'sitelinks',

	/**
	 * The part of the editable site link representing the site the selected page belongs to
	 * @type wb.ui.PropertyEditTool.EditableValue.SiteIdInterface
	 */
	siteIdInterface: null,

	/**
	 * The part of the editable site link representing the link to the site
	 * @type wb.ui.PropertyEditTool.EditableValue.SitePageInterface
	 */
	sitePageInterface: null,

	/**
	 * current results received from the api
	 * @type Array
	 */
	_currentResults: null,

	/**
	 * @see wb.ui.PropertyEditTool.EditableValue._options
	 */
	_options: $.extend( {}, PARENT.prototype._options, {
		inputHelpMessageKey: 'wikibase-sitelinks-input-help-message'
	} ),

	/**
	 * @see wb.ui.PropertyEditTool.EditableValue._init
	 */
	_init: function( subject, options, interfaces, toolbar ) {
		if( interfaces.length < 2
			|| !( interfaces[0] instanceof wb.ui.PropertyEditTool.EditableValue.SiteIdInterface )
			|| !( interfaces[1] instanceof wb.ui.PropertyEditTool.EditableValue.SitePageInterface )
		) {
			throw new Error( 'Proper interfaces needed for EditableSiteLink are not provided' );
		}

		// TODO: rather provide getters for these
		this.siteIdInterface = interfaces.siteId = interfaces[0]; // this._interfaces.siteId
		this.sitePageInterface = interfaces.pageName = interfaces[1]; // this._interfaces.pageName

		PARENT.prototype._init.apply( this, arguments );
	},

	/**
	 * @see wb.ui.PropertyEditTool.EditableValue._bindInterfaces
	 */
	_bindInterfaces: function( interfaces ) {
		PARENT.prototype._bindInterfaces.call( this, interfaces );

		// TODO: move this into _init() perhaps
		/* TODO: Setting the language attributes on initialisation will not be required as soon as
		the attributes are already attached in PHP for the non-JS version */
		if ( this.siteIdInterface.getSelectedSite() !== null ) {
			this.sitePageInterface.setLanguageAttributes(
				this.siteIdInterface.getSelectedSite().getLanguage()
			);
		}
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._interfaceHandler_onInputRegistered
	 *
	 * @param relatedInterface wikibase.ui.PropertyEditTool.EditableValue.Interface
	 */
	_interfaceHandler_onInputRegistered: function( relatedInterface ) {
		PARENT.prototype._interfaceHandler_onInputRegistered.call( this, relatedInterface );

		var idInterface = this._interfaces.siteId,
			pageInterface = this._interfaces.pageName;

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
			pageInterface.setLanguageAttributes( site.getLanguage() );
		}

		// only enable site page selector if there is a valid site id selected
		pageInterface[ idInterface.isValid() ? 'enable' : 'disable' ]();
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._getValueFromApiResponse
	 */
	_getValueFromApiResponse: function( response ) {
		var siteId = this._interfaces.siteId.getSelectedSite().getId(),
			normalizedTitle = response[ this.API_VALUE_KEY ][ this._interfaces.siteId.getSelectedSite().getGlobalSiteId() ].title;

		return [ siteId, normalizedTitle ];
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._setRevisionIdFromApiResponse
	 */
	_setRevisionIdFromApiResponse: function( response ) {
		wb.getRevisionStore().setSitelinksRevision( response.lastrevid, this._interfaces.siteId.getSelectedSite().getGlobalSiteId() );
		return true;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue._getToolbarParent
	 *
	 * @return jQuery node the toolbar should be appended to
	 */
	_getToolbarParent: function() {
		return this._subject.children( 'td' ).last();
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.startEditing
	 *
	 * @return bool will return false if edit mode is active already.
	 */
	startEditing: function() {
		// set ignored site links again since they could have changed
		this._interfaces.siteId.setOption( 'ignoredSiteLinks', this.ignoredSiteLinks );
		return PARENT.prototype.startEditing.call( this );
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.stopEditing
	 *
	 * @param bool save whether to save the current value
	 * @return jQuery.Promise
	 */
	stopEditing: function( save ) {
		var promise = PARENT.prototype.stopEditing.call( this, save );
		// Prevent siteId input element from appearing again when triggering edit on a just created
		// site link without having reloaded the page. However, it is required to check whether the
		// corresponding interface (still) exists since it might have been destroyed when - instead
		// of being saved - the pending value got removed again by cancelling. In addition, make
		// toolbar display the "remove" button after having saved a vaue successfully.
		promise.done( $.proxy( function() {
			if ( this._interfaces !== null ) {
				this._interfaces.siteId.setActive( this.isPending() );
				this._toolbar.editGroup._options.displayRemoveButton = true;
			}
		}, this ) );
		return promise;
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.getValueLanguageContext
	 *
	 * @return null Site-Links are the same in all languages, they are not multi lingual
	 */
	getValueLanguageContext: function() {
		return null; // a site-link is not related to any specific language!
	},

	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.performApiAction
	 */
	performApiAction: function( apiAction ) {
		var promise = PARENT.prototype.performApiAction.call( this, apiAction );

		// no point in doing additional operations when removing anyway
		if ( apiAction !== this.API_ACTION.REMOVE ) {
			var self = this;

			// (bug 40399) for site-links we want to get the normalized link from the API result to make sure we have
			// the right links without knowing about the site type.
			promise.done( function( response ) {
				var page = self._interfaces.pageName.getValue(),
					site = wb.getSite( self._interfaces.siteId.getValue() );

				if( page !== '' && site !== null ) {
					var url = response.entity.sitelinks[ site.getGlobalSiteId() ].url,
						oldFn = site.getUrlTo;

					self._interfaces.siteId.setOption( 'siteId', site.getId() );

					// overwrite the getUrlTo function of this site object to always return the valid url returned by the
					// API without caring about the site type. This acts as a filter on top of the original function.
					// TODO/FIXME: this is rather hacky, a real cache could be introduced to wb.Site.getUrlTo
					site.getUrlTo = function( pageTitle ) {
						if( $.trim( pageTitle ) === page ) {
							return url;
						}
						return oldFn( pageTitle );
					};
				}
			} );
		}

		return promise;
	},

	/**
	 * Calling the corresponding method in the wikibase.RepoApi
	 *
	 * @param number apiAction see this.API_ACTION enum for all available actions
	 * @return {jQuery.Promise}
	 */
	queryApi: function( apiAction ) {
		return this._api.setSitelink(
			mw.config.get( 'wbEntityId' ),
			wb.getRevisionStore().getSitelinksRevision( this.siteIdInterface.getSelectedSite().getGlobalSiteId() ),
			this.siteIdInterface.getSelectedSite().getGlobalSiteId(),
			( apiAction === this.API_ACTION.REMOVE || apiAction === this.API_ACTION.SAVE_TO_REMOVE ) ? '' : this.getValue()[1]
		);
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
	 * Allows to specify an array with sites which should not be allowed to be chosen
	 * @var wikibase.Site[]
	 */
	ignoredSiteLinks: null
} );

/**
 * @see wb.ui.PropertyEditTool.EditableValue.newFromDom
 */
SELF.newFromDom = function( subject, options, toolbar, /** internal */ Constructor ) {
	Constructor = Constructor || SELF;

	var $subject = $( subject ),
		ev = wb.ui.PropertyEditTool.EditableValue,
		newEV = new Constructor(),
		iSiteId = new ev.SiteIdInterface(),
		iSitePage = new ev.SitePageInterface(),
		$tableCells = $subject.children( 'td' ),
		// extract site id
		cssClasses = $subject.attr( 'class' ) ? $subject.attr( 'class' ).split( ' ' ) : '',
		prefix = 'wb-sitelinks-',
		siteId = null;

	for ( var i in cssClasses ) {
		if ( cssClasses[i].indexOf( prefix ) !== -1 ) {
			siteId = cssClasses[i].substr( prefix.length );
			break;
		}
	}

	// interface for choosing the source site:
	iSiteId.init( $tableCells[0], {
		siteId: siteId,
		// propagate information about which site links are set already to the site ID interface:
		ignoredSiteLinks: newEV.ignoredSiteLinks,
		inputPlaceholder: mw.msg( 'wikibase-sitelink-site-edit-placeholder' )
	} );

	// once stored, the site ID should not be editable!
	// TODO/FIXME: not sure this should be done here
	iSiteId.setActive( $subject.hasClass( 'wb-pending-value' ) );

	// interface for choosing a page (from the source site)
	// pass the second to last cell as subject since the last cell will be used by the toolbar
	iSitePage.init(
		$tableCells[ $tableCells.length - 2 ],
		{ inputPlaceholder: mw.msg( 'wikibase-sitelink-page-edit-placeholder' ) },
		iSiteId.getSelectedSite()
	);
	iSitePage.ajaxParams = {
		action: 'opensearch',
		namespace: 0,
		suggest: ''
	};

	newEV.init( $subject, options, [ iSiteId, iSitePage ], toolbar );
	return newEV;
};

}( mediaWiki, wikibase, jQuery ) );
