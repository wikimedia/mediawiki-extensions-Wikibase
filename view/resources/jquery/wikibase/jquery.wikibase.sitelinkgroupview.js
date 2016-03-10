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
 * @option {Object} value
 *         Object representing the widget's value.
 *         Structure: { group: <{string}>, siteLinks: <{wikibase.datamodel.SiteLink[]}> }
 *
 * @option {wikibase.entityChangers.SiteLinksChanger} siteLinksChanger
 *
 * @option {wikibase.entityIdFormatter.EntityIdPlainFormatter} entityIdPlainFormatter
 *
 * @option {jQuery.util.EventSingletonManager} [eventSingletonManager]
 *         Should be set when the widget instance is part of a jQuery.wikibase.sitelinkgrouplistview.
 *         Default: null (will be constructed automatically)
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
				return 'sitelinks-' + this.options.value.group;
			},
			function() {
				// It's hard to dynamically load the right message. Fake it as best as possible.
				return this.options.value.group[0].toUpperCase()
					+ this.options.value.group.slice( 1 );
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
		entityIdPlainFormatter: null,
		siteLinksChanger: null,
		eventSingletonManager: null,
		helpMessage: mw.msg( 'wikibase-sitelinkgroupview-input-help-message' )
	},

	/**
	 * @type {jQuery}
	 */
	$sitelinklistview: null,

	/**
	 * @type {jQuery.util.EventSingletonManager}
	 */
	_eventSingletonManager: null,

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 */
	_create: function() {
		if ( !this.options.siteLinksChanger || !this.options.entityIdPlainFormatter ) {
			throw new Error( 'Required parameter(s) missing' );
		}

		this.options.value = this._checkValue( this.options.value );

		PARENT.prototype._create.call( this );

		this.$sitelinklistview = this.element.find( '.wikibase-sitelinklistview' );

		if ( !this.$sitelinklistview.length ) {
			this.$sitelinklistview = $( '<table/>' ).appendTo( this.element );
		}

		this._eventSingletonManager
			= this.options.eventSingletonManager || new $.util.EventSingletonManager();

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

		this.element.data( 'group', this.options.value.group );

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
		var self = this,
			prefix = $.wikibase.sitelinklistview.prototype.widgetEventPrefix;

		this.$sitelinklistview
		.on( prefix + 'change.' + this.widgetName, function( event ) {
			self._trigger( 'change' );
		} )
		.on( prefix + 'toggleerror.' + this.widgetName, function( event, error ) {
			self.setError( error );
		} )
		.sitelinklistview( {
			value: this._getSiteLinksOfGroup(),
			allowedSiteIds: this.options.value
				? getSiteIdsOfGroup( this.options.value.group )
				: [],
			entityIdPlainFormatter: this.options.entityIdPlainFormatter,
			siteLinksChanger: this.options.siteLinksChanger,
			eventSingleton: this._eventSingleton,
			$counter: this.$counter,
			encapsulate: true
		} );
	},

	/**
	 * @return {wikibase.datamodel.SiteLink[]}
	 */
	_getSiteLinksOfGroup: function() {
		var self = this;

		if ( !this.options.value ) {
			return [];
		}

		return $.grep( this.options.value.siteLinks, function( siteLink ) {
			return $.inArray(
				siteLink.getSiteId(),
				getSiteIdsOfGroup( self.options.value.group )
			) !== -1;
		} );
	},

	/**
	 * @param {*} value
	 * @return {Object}
	 *
	 * @throws {Error} if value is not defined properly.
	 */
	_checkValue: function( value ) {
		if ( !$.isPlainObject( value ) ) {
			throw new Error( 'Value needs to be an object' );
		} else if ( !value.group ) {
			throw new Error( 'Value needs group id to be specified' );
		}

		if ( !value.siteLinks ) {
			value.siteLinks = [];
		}

		return value;
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.startEditing
	 */
	startEditing: function() {
		var self = this,
			deferred = $.Deferred();

		this.$sitelinklistview.one( 'sitelinklistviewafterstartediting', function() {
			PARENT.prototype.startEditing.call( self )
				.done( deferred.resolve )
				.fail( deferred.reject );
		} );

		this.$sitelinklistview.data( 'sitelinklistview' ).startEditing();

		return deferred.promise();
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.stopEditing
	 */
	stopEditing: function( dropValue ) {
		var self = this,
			deferred = $.Deferred();

		if ( !this.isInEditMode() || ( !this.isValid() || this.isInitialValue() ) && !dropValue ) {
			return deferred.resolve().promise();
		}

		this._trigger( 'stopediting', null, [dropValue] );

		this.disable();

		this.$sitelinklistview
		.one(
			'sitelinklistviewafterstopediting.sitelinkgroupviewstopediting',
			function( event, dropValue ) {
				self._afterStopEditing( dropValue );
				self.$sitelinklistview.off( '.sitelinkgroupviewstopediting' );
				self.notification();
				deferred.resolve();
			}
		)
		.one( 'sitelinklistviewtoggleerror.sitelinkgroupviewstopediting', function( event, error ) {
			self.enable();
			self.$sitelinklistview.off( '.sitelinkgroupviewstopediting' );
			deferred.reject( error );
		} );

		this.$sitelinklistview.data( 'sitelinklistview' ).stopEditing( dropValue );

		return deferred.promise();
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.value
	 *
	 * @param {Object} [value]
	 * @return {Object|*}
	 */
	value: function( value ) {
		if ( value !== undefined ) {
			return this.option( 'value', value );
		}

		return this.options.value;
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.isEmpty
	 */
	isEmpty: function() {
		return !this.value().siteLinks.length;
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.isValid
	 * @return {boolean}
	 */
	isValid: function() {
		return this.$sitelinklistview.data( 'sitelinklistview' ).isValid();
	},

	/**
	 * @see jQuery.ui.EditableTemplatedWidget.isInitialValue
	 * @return {boolean}
	 */
	isInitialValue: function() {
		return this.$sitelinklistview.data( 'sitelinklistview' ).isInitialValue();
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.focus
	 */
	focus: function() {
		this.$sitelinklistview.data( 'sitelinklistview' ).focus();
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 */
	_setOption: function( key, value ) {
		if ( key === 'value' ) {
			value = this._checkValue( value );
		}

		var response = PARENT.prototype._setOption.call( this, key, value );

		if ( key === 'value' ) {
			this.$sitelinklistview.data( 'sitelinklistview' )
			.option( 'allowedSiteIds', getSiteIdsOfGroup( this.options.value.group ) )
			.value( this.options.value.siteLinks );

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
		if ( error ) {
			var self = this;

			var $error = wb.buildErrorOutput( error, {
				progress: function() {
					self.$headingSection.data( 'sticknode' ).refresh();
				}
			} );

			this.element.addClass( 'wb-error' );
			this.notification( $error, 'wb-error' );
		} else {
			if ( this.$notification && this.$notification.hasClass( 'wb-error' ) ) {
				this.notification();
			}
		}

		PARENT.prototype.setError.call( this, error );
	}
} );

}( jQuery, mediaWiki, wikibase ) );
