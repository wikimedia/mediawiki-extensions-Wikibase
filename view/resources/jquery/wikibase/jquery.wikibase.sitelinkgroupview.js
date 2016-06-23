/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, mw, wb ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget;

/**
 * @param {string} group
 * @return {string[]}
 */
function getSiteIdsOfGroup( group ) {
	var siteIds = [];
	$.each( wb.sites.getSitesOfGroup( group ), function( siteId, site ) {
		siteIds.push( siteId );
	} );
	return siteIds;
}

/**
 * Manages a sitelinklistview widget specific to a particular site link group.
 * @since 0.5
 * @extends jQuery.ui.EditableTemplatedWidget
 *
 * @option {string} groupName
 * @option {wikibase.datamodel.SiteLink[]} value A list of SiteLinks
 * @option {Function} getSiteLinkListView
 *
 * @option {string} [helpMessage]
 *                  Default: 'Add a site link by specifying a site and a page of that site, edit or
 *                  remove existing site links.'
 */
$.widget( 'wikibase.sitelinkgroupview', PARENT, {
	/**
	 * @see jQuery.ui.TemplatedWidget.options
	 */
	options: {
		template: 'wikibase-sitelinkgroupview',
		templateParams: [
			function() {
				return 'sitelinks-' + this.options.groupName;
			},
			function() {
				// It's hard to dynamically load the right message. Fake it as best as possible.
				return this.options.groupName[0].toUpperCase()
					+ this.options.groupName.slice( 1 );
			},
			'', // counter
			'', // sitelinklistview
			'', // group
			'', // toolbar
			'' // additional class names
		],
		templateShortCuts: {
			$headingSection: '.wikibase-sitelinkgroupview-heading-section',
			headingContainer: '.wikibase-sitelinkgroupview-heading-container',
			$h: 'h3',
			$counter: '.wikibase-sitelinkgroupview-counter'
		},
		value: null,
		getSiteLinkListView: null,
		groupName: null,
		helpMessage: mw.msg( 'wikibase-sitelinkgroupview-input-help-message' )
	},

	/**
	 * @type {jQuery}
	 */
	$sitelinklistview: null,

	/**
	 * @type {string[]}
	 */
	_siteIdsOfGroup: null,

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 */
	_create: function() {
		if ( !this.options.groupName || !this.options.getSiteLinkListView ) {
			throw new Error( 'Required parameter(s) missing' );
		}

		this.options.value = this._checkValue( this.options.value );
		this._siteIdsOfGroup = getSiteIdsOfGroup( this.options.groupName );

		PARENT.prototype._create.call( this );

		this.$sitelinklistview = this.element.find( '.wikibase-sitelinklistview' );

		if ( !this.$sitelinklistview.length ) {
			this.$sitelinklistview = $( '<table/>' ).appendTo( this.element );
		}

		this.draw();
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.destroy
	 */
	destroy: function() {
		if ( this.$sitelinklistview ) {
			this.$sitelinklistview.data( 'sitelinklistview' ).destroy();
		}
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.draw
	 */
	draw: function() {
		var self = this,
			deferred = $.Deferred();

		this.element.data( 'group', this.options.groupName );

		if ( !this.$headingSection.data( 'sticknode' ) ) {
			this.$headingSection.sticknode( {
				$container: this.element
			} );
		}

		if ( !this._$notification ) {
			this.notification()
			.appendTo( this.$headingSection )
			.on( 'closeableupdate.' + this.widgetName, function() {
				var sticknode = self.element.data( 'sticknode' );
				if ( sticknode ) {
					sticknode.refresh();
				}
			} );
		}

		if ( !this.$sitelinklistview.data( 'sitelinklistview' ) ) {
			this._createSitelinklistview();
			deferred.resolve();
		} else {
			this.$sitelinklistview.data( 'sitelinklistview' ).draw()
				.done( deferred.resolve )
				.fail( deferred.reject );
		}

		return deferred.promise();
	},

	/**
	 * Creates and initializes the sitelinklistview widget.
	 */
	_createSitelinklistview: function() {
		var sitelinklistview = this.options.getSiteLinkListView(
			this._getSiteLinksOfGroup(),
			this.$sitelinklistview,
			this._siteIdsOfGroup,
			this.$counter
		);
		var prefix = sitelinklistview.widgetEventPrefix;

		var self = this;
		this.$sitelinklistview
		.on( prefix + 'change.' + this.widgetName, function( event ) {
			self._trigger( 'change' );
		} )
		.on( prefix + 'toggleerror.' + this.widgetName, function( event, error ) {
			self.setError( error );
		} );
	},

	/**
	 * @return {wikibase.datamodel.SiteLink[]}
	 */
	_getSiteLinksOfGroup: function() {
		var self = this,
			result = [];

		if ( !this.options.value ) {
			return result;
		}

		this.options.value.each( function( siteId, siteLink ) {
			if ( $.inArray( siteId, self._siteIdsOfGroup ) !== -1 ) {
				result.push( siteLink );
			}
		} );
		return result;
	},

	/**
	 * @param {*} value
	 * @return {Object}
	 */
	_checkValue: function( value ) {
		if ( !value ) {
			value = new wb.datamodel.SiteLinkSet( [] );
		} else if ( !( value instanceof wb.datamodel.SiteLinkSet ) ) {
			throw new Error();
		}

		return value;
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.startEditing
	 */
	startEditing: function() {
		var self = this;
		return this.$sitelinklistview.data( 'sitelinklistview' ).startEditing().done( function() {
			return PARENT.prototype.startEditing.call( self );
		} );
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget._afterStopEditing
	 */
	_afterStopEditing: function( dropValue ) {
		var self = this;
		return this.$sitelinklistview.data( 'sitelinklistview' ).stopEditing( dropValue )
		.done( function() {
			self.notification();
			return PARENT.prototype._afterStopEditing.call( self );
		} );
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.value
	 *
	 * @param {wikibase.datamodel.SiteLink[]} [value]
	 * @return {wikibase.datamodel.SiteLink[]}
	 */
	value: function( value ) {
		if ( value !== undefined ) {
			return this.option( 'value', value );
		}

		return new wb.datamodel.SiteLinkSet( this.$sitelinklistview.data( 'sitelinklistview' ).value() );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.focus
	 */
	focus: function() {
		this.$sitelinklistview.data( 'sitelinklistview' ).focus();
	},

	_getSiteLinksArray: function() {
		var res = [];
		this.options.value.each( function( siteId, siteLink ) {
			res.push( siteLink );
		} );
		return res;
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 */
	_setOption: function( key, value ) {
		if ( key === 'value' ) {
			value = this._checkValue( value );
		} else if ( key === 'groupName' && value !== this.options.groupName ) {
			this._siteIdsOfGroup = getSiteIdsOfGroup( value );
			this.$sitelinklistview.data( 'sitelinklistview' )
				.option( 'allowedSiteIds', this._siteIdsOfGroup );

			this.draw();
		}

		var response = PARENT.prototype._setOption.call( this, key, value );

		if ( key === 'value' ) {
			this.$sitelinklistview.data( 'sitelinklistview' )
			.value( this._getSiteLinksArray() );

			this.draw();
		} else if ( key === 'disabled' ) {
			this.$sitelinklistview.data( 'sitelinklistview' ).option( key, value );
		}

		return response;
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.setError
	 */
	setError: function( error ) {
		if ( !error ) {
			if ( this.$notification && this.$notification.hasClass( 'wb-error' ) ) {
				this.notification();
			}
		}

		PARENT.prototype.setError.call( this, error );
	},

	doErrorNotification: function( error ) {
		var self = this;

		var $error = wb.buildErrorOutput( error, {
			progress: function() {
				self.$headingSection.data( 'sticknode' ).refresh();
			}
		} );

		this.notification( $error, 'wb-error' );
	}
} );

}( jQuery, mediaWiki, wikibase ) );
