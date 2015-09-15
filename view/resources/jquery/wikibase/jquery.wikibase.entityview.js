( function( $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * Abstract base view for displaying a Wikibase `Entity`.
 * @class jQuery.wikibase.entityview
 * @extends jQuery.ui.TemplatedWidget
 * @abstract
 * @since 0.3
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} options
 * @param {wikibase.datamodel.Entity} options.value
 * @param {Function} options.entityTermsViewBuilder
 */
/**
 * @event afterstartediting
 * Triggered after the widget has switched to edit mode.
 * @param {jQuery.Event} event
 */
/**
 * @event afterstopediting
 * Triggered after the widget has left edit mode.
 * @param {jQuery.Event} event
 * @param {boolean} dropValue Whether the pending value has been dropped (editing has been
 *        cancelled).
 */
$.widget( 'wikibase.entityview', PARENT, {
	/**
	 * @inheritdoc
	 * @protected
	 */
	options: {
		entityTermsViewBuilder: null,
		template: 'wikibase-entityview',
		templateParams: [
			'', // entity type
			'', // entity id
			'', // language code
			'', // language direction
			'', // main content
			'' // sidebar
		],
		templateShortCuts: {
			$main: '.wikibase-entityview-main',
			$side: '.wikibase-entityview-side'
		},
		value: null
	},

	/**
	 * @property {jQuery|null}
	 * @readonly
	 */
	$entityTerms: null,

	/**
	 * @inheritdoc
	 *
	 * @throws {Error} when called.
	 */
	_create: function() {
		throw new Error( 'Abstract entityview cannot be created directly' );
	},

	/**
	 * Main initialization actually calling parent's _create().
	 * @see jQuery.ui.TemplatedWidget._create
	 * @protected
	 */
	_createEntityview: function() {
		PARENT.prototype._create.call( this );
	},

	/**
	 * @inheritdoc
	 * @protected
	 *
	 * @throws {Error} if a required options is missing.
	 */
	_init: function() {
		if( !this.options.value || !this.options.entityTermsViewBuilder ) {
			throw new Error( 'Required option(s) missing' );
		}

		this.element.data( $.wikibase.entityview.prototype.widgetName, this );

		this._initEntityTerms();

		PARENT.prototype._init.call( this );

		this._attachEventHandlers();
	},

	/**
	 * @protected
	 */
	_initEntityTerms: function() {
		this.$entityTerms = $( '.wikibase-entitytermsview', this.element );

		if( !this.$entityTerms.length ) {
			this.$entityTerms = $( '<div/>' ).prependTo( this.$main );
		}

		this.options.entityTermsViewBuilder( this.options.value.getFingerprint(), this.$entityTerms );
	},

	/**
	 * @protected
	 */
	_attachEventHandlers: function() {
		var self = this;

		this.element
		.on( [
			'labelviewafterstartediting.' + this.widgetName,
			'descriptionviewafterstartediting.' + this.widgetName,
			'aliasesviewafterstartediting.' + this.widgetName,
			'entitytermsviewafterstartediting.' + this.widgetName
		].join( ' ' ),
		function( event ) {
			self._trigger( 'afterstartediting' );
		} );

		this.element
		.on( [
			'labelviewafterstopediting.' + this.widgetName,
			'descriptionviewafterstopediting.' + this.widgetName,
			'aliasesviewafterstopediting.' + this.widgetName,
			'entitytermsviewafterstopediting.' + this.widgetName
		].join( ' ' ),
		function( event, dropValue ) {
			self._trigger( 'afterstopediting', null, [dropValue] );
		} );
	},

	/**
	 * @inheritdoc
	 *
	 * @throws {Error} when trying to set an option to an improper value.
	 */
	_setOption: function( key, value ) {
		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this._setState( value ? 'disable' : 'enable' );
		}

		return response;
	},

	/**
	 * @protected
	 *
	 * @param {string} state "disable" or "enable"
	 */
	_setState: function( state ) {
		if( this.$entityTerms ) {
			this.$entityTerms.data( 'entitytermsview' )[state]();
		}
	}

} );

/**
 * List of entityview types. Every entityview type should add itself to the list in order to be
 * matched by $( ':wikibase-entityview' ) pseudo selector.
 * @property {string[]}
 * @static
 */
$.wikibase.entityview.TYPES = [];

$.expr[':'][$.wikibase.entityview.prototype.widgetFullName]
	= $.expr.createPseudo( function( fullName ) {
		return function( elem ) {
			for( var i = 0; i < $.wikibase.entityview.TYPES.length; i++ ) {
				if( !!$.data( elem, $.wikibase.entityview.TYPES[i] ) ) {
					return true;
				}
			}
			return false;
		};
	}
);

}( jQuery ) );
