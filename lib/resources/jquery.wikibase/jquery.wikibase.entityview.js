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
		templateShortCuts: {},
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
	$toc: null,

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
	$fingerprints: null,

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

		PARENT.prototype._create.call( this );

		this.element.data( $.wikibase.entityview.prototype.widgetName, this );

		this.$toc = $( '.toc', this.element );

		this._initLabel();
		this._initDescription();
		this._initAliases();
		this._initFingerprints();

		this._attachEventHandlers();
	},

	/**
	 * @protected
	 */
	_initLabel: function() {
		this.$label = $( '.wb-firstHeading .wikibase-labelview', this.element ).first();
		if( !this.$label.length ) {
			this.$label = $( '<div/>' );
			mw.wbTemplate( 'wikibase-firstHeading',
				this.options.value.getId(),
				this.$label
			).appendTo( this.element );
		}

		// FIXME: entity object should not contain fallback strings
		var label = this.options.value.getFingerprint().getLabelFor( this.options.languages[0] )
			|| new wb.datamodel.Term( this.options.languages[0], '' );

		this.$label.labelview( {
			value: label,
			helpMessage: mw.msg(
				'wikibase-description-input-help-message',
				wb.getLanguageNameByCode( this.options.languages[0] )
			),
			entityId: this.options.value.getId(),
			labelsChanger: this.options.entityChangersFactory.getLabelsChanger(),
			showEntityId: true
		} );
	},

	/**
	 * @protected
	 */
	_initDescription: function() {
		this.$description = $( '.wikibase-descriptionview', this.element ).first();
		if( !this.$description.length ) {
			this.$description = $( '<div/>' ).appendTo( this.element );
		}

		// FIXME: entity object should not contain fallback strings
		var description = this.options.value.getFingerprint().getDescriptionFor(
			this.options.languages[0]
		) || new wb.datamodel.Term( this.options.languages[0], '' );

		this.$description.descriptionview( {
			value: description,
			helpMessage: mw.msg(
				'wikibase-description-input-help-message',
				wb.getLanguageNameByCode( this.options.languages[0] )
			),
			descriptionsChanger: this.options.entityChangersFactory.getDescriptionsChanger()
		} );
	},

	/**
	 * @protected
	 */
	_initAliases: function() {
		this.$aliases = $( '.wikibase-aliasesview', this.element ).first();
		if( !this.$aliases.length ) {
			this.$aliases = $( '<div/>' ).appendTo( this.element );
		}

		var aliases = this.options.value.getFingerprint().getAliasesFor( this.options.languages[0] )
			|| new wb.datamodel.MultiTerm( this.options.languages[0], [] );

		this.$aliases.aliasesview( {
			value: aliases,
			aliasesChanger: this.options.entityChangersFactory.getAliasesChanger()
		} );
	},

	/**
	 * @protected
	 */
	_initFingerprints: function() {
		var self = this;

		if( this.options.languages.length === 1 ) {
			return;
		}

		this.$fingerprints = $( '.wikibase-fingerprintgroupview', this.element );

		if( !this.$fingerprints.length ) {
			var $precedingNode = this.$toc;

			if( !$precedingNode.length ) {
				$precedingNode = $( '.wikibase-aliasesview' );
			} else {
				this._addTocItem(
					'#wb-terms',
					mw.msg( 'wikibase-terms' ),
					this.$toc.find( 'li' ).first()
				);
			}

			this.$fingerprints = $( '<div/>' ).insertAfter( $precedingNode );
		} else {
			// Scrape languages from static HTML:
			// FIXME: Currently, this simply overrules the languages options.
			self.options.languages = [];
			this.$fingerprints.find( '.wikibase-fingerprintview' ).each( function() {
				$.each( $( this ).attr( 'class' ).split( ' ' ), function() {
					if( this.indexOf( 'wikibase-fingerprintview-' ) === 0 ) {
						self.options.languages.push(
							this.split( 'wikibase-fingerprintview-' )[1]
						);
						return false;
					}
				} );
			} );
		}

		var fingerprint = this.options.value.getFingerprint(),
			value = [];

		for( var i = 1; i < this.options.languages.length; i++ ) {
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

		this.$fingerprints.fingerprintgroupview( {
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
			'fingerprintgroupviewafterstartediting.' + this.widgetName
		].join( ' ' ),
		function( event ) {
			self._trigger( 'afterstartediting' );
		} );

		this.element
		.on( [
			'labelviewafterstopediting.' + this.widgetName,
			'descriptionviewafterstopediting.' + this.widgetName,
			'aliasesviewafterstopediting.' + this.widgetName,
			'fingerprintgroupviewafterstopediting.' + this.widgetName
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
		if( this.$fingerprints ) {
			this.$fingerprints.data( 'fingerprintgroupview' )[state]();
		}
	},

	/**
	 * Adds an item to the table of contents.
	 * @protected
	 *
	 * @param {string} href
	 * @param {string} text
	 * @param {jQuery} [$insertBefore] Omit to have the item inserted at the end
	 */
	_addTocItem: function( href, text, $insertBefore ) {
		if( !this.$toc.length ) {
			return;
		}

		var $li = $( '<li>' )
			.addClass( 'toclevel-1' )
			.append( $( '<a>' ).attr( 'href', href ).text( text ) );

		if( $insertBefore ) {
			$li.insertBefore( $insertBefore );
		} else {
			this.$toc.append( $li );
		}

		this.$toc.find( 'li' ).each( function( i, li ) {
			$( li )
			.removeClass( 'tocsection-' + i )
			.addClass( 'tocsection-' + ( i + 1 ) );
		} );
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
