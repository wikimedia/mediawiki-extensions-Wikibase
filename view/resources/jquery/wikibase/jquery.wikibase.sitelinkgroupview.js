/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget,
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * @param {string} group
	 * @return {string[]}
	 */
	function getSiteIdsOfGroup( group ) {
		var siteIds = [];
		// eslint-disable-next-line no-jquery/no-each-util
		$.each( wb.sites.getSitesOfGroup( group ), function ( siteId, site ) {
			siteIds.push( siteId );
		} );
		return siteIds;
	}

	/**
	 * Manages a sitelinklistview widget specific to a particular site link group.
	 *
	 * @extends jQuery.ui.EditableTemplatedWidget
	 *
	 * @option {string} groupName
	 * @option {datamodel.SiteLinkSet} value
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
				function () {
					return 'sitelinks-' + this.options.groupName;
				},
				function () {
					// It's hard to dynamically load the right message. Fake it as best as possible.
					return this.options.groupName[ 0 ].toUpperCase()
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
		_create: function () {
			if ( !this.options.groupName || !this.options.getSiteLinkListView ) {
				throw new Error( 'Required parameter(s) missing' );
			}

			this.options.value = this._checkValue( this.options.value );
			this._siteIdsOfGroup = getSiteIdsOfGroup( this.options.groupName );

			PARENT.prototype._create.call( this );

			this.$sitelinklistview = this.element.find( '.wikibase-sitelinklistview' );

			if ( !this.$sitelinklistview.length ) {
				this.$sitelinklistview = $( '<table>' ).appendTo( this.element );
			}

			this.draw();
		},

		/**
		 * @see jQuery.ui.EditableTemplatedWidget.destroy
		 */
		destroy: function () {
			if ( this.$sitelinklistview ) {
				this.$sitelinklistview.data( 'sitelinklistview' ).destroy();
			}
			PARENT.prototype.destroy.call( this );
		},

		/**
		 * @see jQuery.ui.EditableTemplatedWidget.draw
		 */
		draw: function () {
			var deferred = $.Deferred();

			this.element.data( 'group', this.options.groupName );

			if ( !this._$notification ) {
				this.notification()
				.appendTo( this.$headingSection );
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
		_createSitelinklistview: function () {
			var sitelinklistview = this.options.getSiteLinkListView(
				this._getSiteLinksOfGroup(),
				this.$sitelinklistview,
				this._siteIdsOfGroup,
				this.$counter
			);
			var prefix = sitelinklistview.widgetEventPrefix;

			var self = this;
			this.$sitelinklistview
			.on( prefix + 'change.' + this.widgetName, function ( event ) {
				self._trigger( 'change' );
			} )
			.on( prefix + 'toggleerror.' + this.widgetName, function ( event, error ) {
				self.setError( error );
			} );
		},

		/**
		 * @return {datamodel.SiteLink[]}
		 */
		_getSiteLinksOfGroup: function () {
			var self = this,
				result = [];

			if ( !this.options.value ) {
				return result;
			}

			this.options.value.each( function ( siteId, siteLink ) {
				if ( self._siteIdsOfGroup.indexOf( siteId ) !== -1 ) {
					result.push( siteLink );
				}
			} );
			return result;
		},

		/**
		 * @param {datamodel.SiteLinkSet|null} value
		 * @throws {Error}
		 * @return {datamodel.SiteLinkSet}
		 */
		_checkValue: function ( value ) {
			if ( !value ) {
				value = new datamodel.SiteLinkSet( [] );
			} else if ( !( value instanceof datamodel.SiteLinkSet ) ) {
				throw new Error( 'value must be a SiteLinkSet or null' );
			}

			return value;
		},

		_startEditing: function () {
			return this.$sitelinklistview.data( 'sitelinklistview' ).startEditing();
		},

		_stopEditing: function ( dropValue ) {
			var self = this;
			return this.$sitelinklistview.data( 'sitelinklistview' ).stopEditing( dropValue )
			.done( function () {
				self.notification();
			} );
		},

		/**
		 * @see jQuery.ui.EditableTemplatedWidget.value
		 *
		 * @param {datamodel.SiteLink[]} [value]
		 * @return {datamodel.SiteLinkSet}
		 */
		value: function ( value ) {
			if ( value !== undefined ) {
				return this.option( 'value', value );
			}

			return new datamodel.SiteLinkSet( this.$sitelinklistview.data( 'sitelinklistview' ).value() );
		},

		/**
		 * @see jQuery.ui.TemplatedWidget.focus
		 */
		focus: function () {
			this.$sitelinklistview.data( 'sitelinklistview' ).focus();
		},

		_getSiteLinksArray: function () {
			var res = [];
			// FIXME: Replace with Set.toArray (requires DataModel JavaScript 3.0).
			this.options.value.each( function ( siteId, siteLink ) {
				res.push( siteLink );
			} );
			return res;
		},

		/**
		 * @see jQuery.ui.TemplatedWidget._setOption
		 */
		_setOption: function ( key, value ) {
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
		setError: function ( error ) {
			if ( !error ) {
				if ( this.$notification && this.$notification.hasClass( 'wb-error' ) ) {
					this.notification();
				}
			}

			PARENT.prototype.setError.call( this, error );
		},

		doErrorNotification: function ( error ) {
			this.notification( wb.buildErrorOutput( error ), 'wb-error' );
		}
	} );

}( wikibase ) );
