( function () {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

	/**
	 * Abstract base view for displaying a Wikibase `Entity`.
	 *
	 * @class jQuery.wikibase.entityview
	 * @extends jQuery.ui.TemplatedWidget
	 * @abstract
	 * @license GPL-2.0-or-later
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 * @param {wikibase.datamodel.Entity} options.value
	 * @param {Function} options.buildEntityTermsView
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
			buildEntityTermsView: null,
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
		 * @property {jQuery.wikibase.entitytermsview}
		 * @readonly
		 */
		_entityTerms: null,

		/**
		 * @inheritdoc
		 *
		 * @throws {Error} when called.
		 */
		_create: function () {
			throw new Error( 'Abstract entityview cannot be created directly' );
		},

		/**
		 * Main initialization actually calling parent's _create().
		 *
		 * @see jQuery.ui.TemplatedWidget._create
		 * @protected
		 */
		_createEntityview: function () {
			PARENT.prototype._create.call( this );

			this.element.data( $.wikibase.entityview.prototype.widgetName, this );
		},

		/**
		 * @inheritdoc
		 * @protected
		 *
		 * @throws {Error} if a required options is missing.
		 */
		_init: function () {
			if ( !this.options.value || !this.options.buildEntityTermsView ) {
				throw new Error( 'Required option(s) missing' );
			}

			this._initEntityTerms();

			PARENT.prototype._init.call( this );

			this._attachEventHandlers();
		},

		/**
		 * @protected
		 */
		_initEntityTerms: function () {
			var $entityTerms = $( '.wikibase-entitytermsview', this.element );

			if ( !$entityTerms.length ) {
				$entityTerms = $( '<div>' ).prependTo( this.$main );
			}

			this._entityTerms = this.options.buildEntityTermsView(
				this.options.value.getFingerprint(),
				$entityTerms
			);
		},

		/**
		 * @protected
		 */
		_attachEventHandlers: function () {
			this._on( {
				entitytermsviewafterstartediting: function ( event ) {
					event.stopPropagation();
					this._trigger( 'afterstartediting' );
				},

				entitytermsviewafterstopediting: function ( event, dropValue ) {
					event.stopPropagation();
					this._trigger( 'afterstopediting', null, [ dropValue ] );
				}
			} );
		},

		/**
		 * @inheritdoc
		 *
		 * @throws {Error} when trying to set an option to an improper value.
		 */
		_setOption: function ( key, value ) {
			var response = PARENT.prototype._setOption.apply( this, arguments );

			if ( key === 'disabled' ) {
				this._setState( value ? 'disable' : 'enable' );
			}

			return response;
		},

		/**
		 * @protected
		 *
		 * @param {string} state "disable" or "enable"
		 */
		_setState: function ( state ) {
			this._entityTerms[ state ]();
		}

	} );

	/**
	 * List of entityview types. Every entityview type should add itself to the list in order to be
	 * matched by $( ':wikibase-entityview' ) pseudo selector.
	 *
	 * @property {string[]}
	 * @static
	 */
	$.wikibase.entityview.TYPES = [];

	$.expr.pseudos[ $.wikibase.entityview.prototype.widgetFullName ]
		= $.expr.createPseudo( function ( fullName ) {
			return function ( elem ) {
				for ( var i = 0; i < $.wikibase.entityview.TYPES.length; i++ ) {
					if ( $.data( elem, $.wikibase.entityview.TYPES[ i ] ) ) {
						return true;
					}
				}
				return false;
			};
		} );

}() );
