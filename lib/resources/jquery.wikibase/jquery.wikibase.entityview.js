( function( wb, $, mw ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * Abstract base view for displaying Wikibase `Entity`s.
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
 * @param {string[]|string} languages
 *        Language codes of the languages to display label-/description-/aliasesview in. Other
 *        components of the entityview will use the first language code for rendering. May just be a
 *        single language code.
 * @param {wikibase.entityChangers.EntityChangersFactory} options.entityChangersFactory
 *        Required to be able to store changes applied to the entity.
 * @param {wikibase.store.EntityStore} options.entityStore
 *        Required by sub-components of the `entityview` to enable those to dynamically query for
 *        `Entity` objects.
 * @param {wikibase.ValueViewBuilder} options.valueViewBuilder
 *        Required by the `snakview` interfacing a `snakview` "value" `Variation` to
 *        `jQuery.valueview`.
 * @param {dataTypes.DataTypeStore} options.dataTypeStore
 *        Required by the `snakview` for retrieving and evaluating a proper `dataTypes.DataType`
 *        object when interacting on a "value" `Variation`.
 *
 * @throws {Error} when called.
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
	 * @property {Object}
	 * @protected
	 */
	options: {
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
		value: null,
		languages: null,
		entityStore: null,
		valueViewBuilder: null,
		dataTypeStore: null
	},

	/**
	 * @property {jQuery}
	 * @protected
	 */
	$label: null,

	/**
	 * @property {jQuery}
	 * @protected
	 */
	$description: null,

	/**
	 * @property {jQuery}
	 * @protected
	 */
	$aliases: null,

	/**
	 * @property {jQuery|null}
	 * @protected
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
	 *
	 * @throws {Error} if a required options is missing.
	 */
	_initEntityview: function() {
		if(
			!this.options.value
			|| !this.options.languages
			|| !this.options.entityStore
			|| !this.options.valueViewBuilder
			|| !this.options.entityChangersFactory
		) {
			throw new Error( 'Required option(s) missing' );
		}

		this.option( 'languages', this.options.languages );

		this.element.data( $.wikibase.entityview.prototype.widgetName, this );

		this._initEntityTerms();

		PARENT.prototype._create.call( this );

		this._attachEventHandlers();
	},

	/**
	 * @protected
	 */
	_initEntityTerms: function() {
		var i;

		this.$entityTerms = $( '.wikibase-entitytermsview', this.element );

		if( !this.$entityTerms.length ) {
			this.$entityTerms = $( '<div/>' ).prepend( this.$main );
		} else {
			var $entitytermsforlanguageview = this.$entityTerms
				.find( '.wikibase-entitytermsforlanguageview' );

			// Scrape languages from static HTML:
			var scrapedLanguages = [];
			if( $entitytermsforlanguageview.length > 0 ) {
				$entitytermsforlanguageview.each( function() {
					$.each( $( this ).attr( 'class' ).split( ' ' ), function() {
						if( this.indexOf( 'wikibase-entitytermsforlanguageview-' ) === 0 ) {
							scrapedLanguages.push(
								this.split( 'wikibase-entitytermsforlanguageview-' )[1]
							);
							return false;
						}
					} );
				} );
			}

			var mismatch = scrapedLanguages.length !== this.options.languages.length;

			if( !mismatch ) {
				for( i = 0; i < scrapedLanguages.length; i++ ) {
					if( scrapedLanguages[i] !== this.options.languages[i] ) {
						mismatch = true;
						break;
					}
				}
			}

			if( mismatch ) {
				// TODO: While this triggers rebuilding the whole DOM structure, the user interface
				// language is always rendered statically and would not need to be re-rendered.
				// However, that requires additional logic in respective widgets.
				$entitytermsforlanguageview.remove();
			}
		}

		var fingerprint = this.options.value.getFingerprint(),
			value = [];

		for( i = 0; i < this.options.languages.length; i++ ) {
			value.push( {
				language: this.options.languages[i],
				label: fingerprint.getLabelFor( this.options.languages[i] )
					|| new wb.datamodel.Term( this.options.languages[i], '' ),
				description: fingerprint.getDescriptionFor( this.options.languages[i] )
					|| new wb.datamodel.Term( this.options.languages[i], '' ),
				aliases: fingerprint.getAliasesFor( this.options.languages[i] )
					|| new wb.datamodel.MultiTerm( this.options.languages[i], [] )
			} );
		}

		this.$entityTerms.entitytermsview( {
			value: value,
			entityId: this.options.value.getId(),
			entityChangersFactory: this.options.entityChangersFactory,
			helpMessage: mw.msg( 'wikibase-fingerprintgroupview-input-help-message' )
		} );
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
		if( key === 'languages' ) {
			if( typeof this.options.languages === 'string' ) {
				this.options.languages = [this.options.languages];
			} else if( !$.isArray( this.options.languages ) ) {
				throw new Error( 'languages need to be supplied as string or array' );
			}
		}

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
		this.$label.data( 'labelview' )[state]();
		this.$description.data( 'descriptionview' )[state]();
		this.$aliases.data( 'aliasesview' )[state]();
		if( this.$entityTerms ) {
			this.$entityTerms.data( 'entitytermsview' )[state]();
		}
	},

	/**
	 * @inheritdoc
	 */
	focus: function() {
		this.$label.data( 'labelview' ).focus();
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

}( wikibase, jQuery, mediaWiki ) );
