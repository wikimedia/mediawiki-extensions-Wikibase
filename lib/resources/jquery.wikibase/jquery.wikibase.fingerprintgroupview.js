/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * Encapsulates a fingerprintlistview widget.
 * @since 0.5
 * @extends jQuery.ui.TemplatedWidget
 *
 * @option {Object[]} value
 *         Object representing the widget's value.
 *         Structure: [
 *           { language: <{string]>, label: <{string|null}>, description: <{string|null}> } [, ...]
 *         ]
 *
 * @options {string} entityId
 *
 * @option {wikibase.RepoApi} api
 *
 * @option {wikibase.entityChangers.EntityChangersFactory} entityChangersFactory
 *
 * @option {string} [helpMessage]
 *                  Default: 'Edit label, description and aliases per language.'
 *
 * @event change
 *        - {jQuery.Event}
 *
 * @event afterstartediting
 *       - {jQuery.Event}
 *
 * @event stopediting
 *        - {jQuery.Event}
 *        - {boolean} Whether to drop the value.
 *        - {Function} Callback function.
 *
 * @event afterstopediting
 *        - {jQuery.Event}
 *        - {boolean} Whether to drop the value.
 *
 * @event toggleerror
 *        - {jQuery.Event}
 *        - {Error|null}
 */
$.widget( 'wikibase.fingerprintgroupview', PARENT, {
	options: {
		template: 'wikibase-fingerprintgroupview',
		templateParams: [
			function() {
				return mw.msg( 'wikibase-terms' );
			},
			'', // fingerprintlistview
			'' // edit section
		],
		templateShortCuts: {
			$h: 'h2'
		},
		value: [],
		entityId: null,
		api: null,
		entityChangersFactory: null,
		helpMessage: 'Edit label, description and aliases per language.'
	},

	/**
	 * @type {boolean}
	 */
	_isInEditMode: false,

	/**
	 * @type {jQuery}
	 */
	$fingerprintlistview: null,

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 */
	_create: function() {
		if(
			!$.isArray( this.options.value )
			|| !this.options.entityId
			|| !this.options.api
			|| !this.options.entityChangersFactory
		) {
			throw new Error( 'Required option(s) missing' );
		}

		PARENT.prototype._create.call( this );

		this.element.addClass( 'wikibase-fingerprintgroupview' );

		this.$fingerprintlistview = this.element.find( '.wikibase-fingerprintlistview' );

		if( !this.$fingerprintlistview.length ) {
			this.$fingerprintlistview = $( '<table/>' ).appendTo( this.element );
		}

		this._createFingerprintlistview();
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.destroy
	 */
	destroy: function() {
		// When destroying a widget not initialized properly, fingerprintlistview will not have been
		// created.
		if( this.$fingerprintlistview ) {
			var fingerprintlistview = this.$fingerprintlistview.data( 'fingerprintlistview' );

			if( fingerprintlistview ) {
				fingerprintlistview.destroy();
			}

			this.$fingerprintlistview.remove();
		}

		this.element.removeClass( 'wikibase-fingerprintgroupview' );
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * Creates and initializes the fingerprintlistview widget.
	 */
	_createFingerprintlistview: function() {
		var self = this,
			prefix = $.wikibase.fingerprintlistview.prototype.widgetEventPrefix;

		this.$fingerprintlistview
		.on( prefix + 'change.' + this.widgetName, function( event ) {
			event.stopPropagation();
			self._trigger( 'change' );
		} )
		.on( prefix + 'toggleerror.' + this.widgetName, function( event, error ) {
			event.stopPropagation();
			self.setError( error );
		} )
		.on(
			[
				prefix + 'create.' + this.widgetName,
				prefix + 'afterstartediting.' + this.widgetName,
				prefix + 'stopediting.' + this.widgetName,
				prefix + 'afterstopediting.' + this.widgetName,
				prefix + 'disable.' + this.widgetName
			].join( ' ' ),
			function( event ) {
				event.stopPropagation();
			}
		)
		.fingerprintlistview( {
			value: this.options.value,
			entityId: this.options.entityId,
			api: this.options.api,
			entityChangersFactory: this.options.entityChangersFactory
		} );
	},

	/**
	 * @return {boolean}
	 */
	isValid: function() {
		return this.$fingerprintlistview.data( 'fingerprintlistview' ).isValid();
	},

	/**
	 * @return {boolean}
	 */
	isInitialValue: function() {
		return this.$fingerprintlistview.data( 'fingerprintlistview' ).isInitialValue();
	},

	startEditing: function() {
		if( this._isInEditMode ) {
			return;
		}

		this._isInEditMode = true;
		this.element.addClass( 'wb-edit' );

		this.$fingerprintlistview.data( 'fingerprintlistview' ).startEditing();

		this._trigger( 'afterstartediting' );
	},

	/**
	 * @param {boolean} [dropValue]
	 */
	stopEditing: function( dropValue ) {
		var self = this;

		if( !this._isInEditMode || ( !this.isValid() || this.isInitialValue() ) && !dropValue ) {
			return;
		}

		dropValue = !!dropValue;

		this._trigger( 'stopediting', null, [dropValue] );

		this.disable();

		this.$fingerprintlistview.one(
			'fingerprintlistviewafterstopediting',
			function( dropValue ) {
				self._afterStopEditing( dropValue );
			}
		);

		this.$fingerprintlistview.data( 'fingerprintlistview' ).stopEditing( dropValue );
	},

	/**
	 * @param {boolean} dropValue
	 */
	_afterStopEditing: function( dropValue ) {
		if( !dropValue ) {
			this.options.value = this.value();
		}
		this._isInEditMode = false;
		this.enable();
		this.element.removeClass( 'wb-edit' );
		this._trigger( 'afterstopediting', null, [dropValue] );
	},

	cancelEditing: function() {
		this.stopEditing( true );
	},

	focus: function() {
		this.$fingerprintlistview.data( 'fingerprintlistview' ).focus();
	},

	/**
	 * Applies/Removes error state.
	 *
	 * @param {Error} [error]
	 */
	setError: function( error ) {
		if( error ) {
			this.element.addClass( 'wb-error' );
			this._trigger( 'toggleerror', null, [error] );
		} else {
			this.element.removeClass( 'wb-error' );
			this._trigger( 'toggleerror' );
		}
	},

	/**
	 * @param {Object[]} [value]
	 * @return {Object[]|*}
	 */
	value: function( value ) {
		if( value !== undefined ) {
			return this.option( 'value', value );
		}

		return this.$fingerprintlistview.data( 'fingerprintlistview' ).value();
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 */
	_setOption: function( key, value ) {
		if( key === 'value' ) {
			throw new Error( 'Impossible to set value after initialization' );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this.$fingerprintlistview.data( 'fingerprintlistview' ).option( key, value );
		}

		return response;
	}
} );

$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'fingerprintgroupview',
	selector: ':' + $.wikibase.fingerprintgroupview.prototype.namespace
		+ '-' + $.wikibase.fingerprintgroupview.prototype.widgetName,
	events: {
		fingerprintgroupviewcreate: function( event, toolbarcontroller ) {
			var $fingerprintgroupview = $( event.target ),
				fingerprintgroupview = $fingerprintgroupview.data( 'fingerprintgroupview' );

			$fingerprintgroupview.edittoolbar( {
				$container: $( '<div/>' )
					.appendTo( $fingerprintgroupview.find(
						'.wikibase-fingerprintgroupview-heading-container'
					) ),
				interactionWidget: fingerprintgroupview
			} );

			$fingerprintgroupview.on( 'keyup.edittoolbar', function( event ) {
				if( fingerprintgroupview.option( 'disabled' ) ) {
					return;
				}
				if( event.keyCode === $.ui.keyCode.ESCAPE ) {
					fingerprintgroupview.stopEditing( true );
				} else if( event.keyCode === $.ui.keyCode.ENTER ) {
					fingerprintgroupview.stopEditing( false );
				}
			} );
		},
		'fingerprintgroupviewchange fingerprintgroupviewafterstartediting': function( event ) {
			var $fingerprintgroupview = $( event.target ),
				fingerprintgroupview = $fingerprintgroupview.data( 'fingerprintgroupview' ),
				toolbar = $fingerprintgroupview.data( 'edittoolbar' ).toolbar,
				$btnSave = toolbar.editGroup.getButton( 'save' ),
				btnSave = $btnSave.data( 'toolbarbutton' ),
				enable = fingerprintgroupview.isValid() && !fingerprintgroupview.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();
		},
		fingerprintgroupviewdisable: function( event ) {
			var $fingerprintgroupview = $( event.target ),
				fingerprintgroupview = $fingerprintgroupview.data( 'fingerprintgroupview' ),
				toolbar = $fingerprintgroupview.data( 'edittoolbar' ).toolbar,
				$btnSave = toolbar.editGroup.getButton( 'save' ),
				btnSave = $btnSave.data( 'toolbarbutton' ),
				enable = fingerprintgroupview.isValid() && !fingerprintgroupview.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();
		},
		toolbareditgroupedit: function( event, toolbarcontroller ) {
			var $fingerprintgroupview = $( event.target ).closest( ':wikibase-edittoolbar' ),
				fingerprintgroupview = $fingerprintgroupview.data( 'fingerprintgroupview' );

			if( !fingerprintgroupview ) {
				return;
			}

			fingerprintgroupview.focus();
		}
	}
} );


}( mediaWiki, jQuery ) );
