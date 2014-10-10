/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * Displays and allows editing label and description in a specific language.
 * @since 0.5
 * @extends jQuery.ui.TemplatedWidget
 *
 * @option {Object|null} value
 *         Object representing the widget's value.
 *         Structure: {
 *           language: <{string}>,
 *           label: <{string|null}>,
 *           description: <{string|null>,
 *           aliases: <{string[]}>
 *         }
 *
 * @option {string} [helpMessage]
 *         Default: mw.msg( 'wikibase-fingerprintview-input-help-message' )
 *
 * @option {string} entityId
 *
 * @option {wikibase.entityChangers.EntityChangersFactory} entityChangersFactory
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
$.widget( 'wikibase.fingerprintview', PARENT, {
	options: {
		template: 'wikibase-fingerprintview',
		templateParams: [
			function() {
				return this.options.value.language;
			},
			function() {
				var title = new mw.Title(
					mw.config.get( 'wgTitle' ),
					mw.config.get( 'wgNamespaceNumber' )
				);
				return title.getUrl( { setlang: this.options.value.language } );
			},
			function() {
				return wb.getLanguageNameByCode( this.options.value.language );
			},
			'', // label
			'', // description
			'' // aliases
		],
		templateShortCuts: {
			$language: '.wikibase-fingerprintview-language',
			$label: '.wikibase-fingerprintview-label',
			$description : '.wikibase-fingerprintview-description',
			$aliases : '.wikibase-fingerprintview-aliases'
		},
		value: null,
		helpMessage: mw.msg( 'wikibase-fingerprintview-input-help-message' ),
		entityId: null,
		entityChangersFactory: null
	},

	/**
	 * @type {jQuery}
	 */
	$labelview: null,

	/**
	 * @type {jQuery}
	 */
	$descriptionview: null,

	/**
	 * @type {jQuery}
	 */
	$aliasesview: null,

	/**
	 * @type {boolean}
	 */
	_isInEditMode: false,

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 */
	_create: function() {
		if( !this.options.entityId || !this.options.api || !this.options.entityChangersFactory ) {
			throw new Error( 'Required option(s) missing' );
		}

		this.options.value = this._checkValue( this.options.value );

		PARENT.prototype._create.call( this );

		this._createWidgets();

		var self = this;
		this.element
		// TODO: Move that code to a sensible place (see jQuery.wikibase.entityview):
		.on( 'fingerprintviewafterstartediting.' + this.widgetName, function( event ) {
			$( wb ).trigger( 'startItemPageEditMode', [
				self.element,
				{
					exclusive: false,
					wbCopyrightWarningGravity: 'sw'
				}
			] );
		} )
		.on( 'fingerprintviewafterstopediting.' + this.widgetName, function( event, dropValue ) {
			$( wb ).trigger( 'stopItemPageEditMode', [
				self.element,
				{ save: dropValue !== true }
			] );
		} );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.destroy
	 */
	destroy: function() {
		var self = this;

		function degrade() {
			if( self.$labelview ) {
				self.$labelview.data( 'labelview' ).destroy();
			}
			if( self.$descriptionview ) {
				self.$descriptionview.data( 'descriptionview' ).destroy();
			}
			if( self.$aliasesview ) {
				self.$aliasesview.data( 'aliasesview' ).destroy();
			}

			PARENT.prototype.destroy.call( self );
		}

		if( this._isInEditMode ) {
			this.element.one( this.widgetEventPrefix + 'afterstopediting', function( event ) {
				degrade();
			} );

			this.cancelEditing();
		} else {
			degrade();
		}
	},

	/**
	 * Creates labelview and descriptionview widget.
	 */
	_createWidgets: function() {
		var self = this;

		$.each( ['label', 'description', 'aliases'], function( i, subjectName ) {
			var widgetName = subjectName + 'view';

			self['$' + widgetName] = self['$' + subjectName].children( '.wikibase-' + widgetName );

			if( !self['$' + widgetName].length ) {
				self['$' + widgetName] = $( '<div/>' ).appendTo( self['$' + subjectName] );
			}

			// Fully encapsulate child widgets by suppressing their events:
			self['$' + widgetName]
			.on( widgetName + 'change', function( event ) {
				event.stopPropagation();
				self._trigger( 'change' );
			} )
			.on( widgetName + 'toggleerror.' + self.widgetName, function( event, error ) {
				event.stopPropagation();
				self.setError( error );
			} )
			.on(
				[
					widgetName + 'create.' + self.widgetName,
					widgetName + 'afterstartediting.' + self.widgetName,
					widgetName + 'stopediting.' + self.widgetName,
					widgetName + 'afterstopediting.' + self.widgetName,
					widgetName + 'disable.' + self.widgetName
				].join( ' ' ),
				function( event ) {
					event.stopPropagation();
				}
			);

			var options = {
				value: self.options.value,
				helpMessage: mw.msg(
					'wikibase-' + subjectName + '-input-help-message',
					wb.getLanguageNameByCode( self.options.value.language )
				)
			};

			if( widgetName === 'aliasesview' ) {
				options.aliasesChanger = self.options.entityChangersFactory.getAliasesChanger();
			} else if ( widgetName === 'descriptionview' ) {
				options.descriptionsChanger = self.options.entityChangersFactory.getDescriptionsChanger();
			} else if ( widgetName === 'labelview' ) {
				options.labelsChanger = self.options.entityChangersFactory.getLabelsChanger();
				options.entityId = self.options.entityId;
			}

			self['$' + widgetName][widgetName]( options );
		} );
	},

	/**
	 * @return {boolean}
	 */
	isValid: function() {
		return this.$labelview.data( 'labelview' ).isValid()
			&& this.$descriptionview.data( 'descriptionview' ).isValid()
			&& this.$aliasesview.data( 'aliasesview' ).isValid();
	},

	/**
	 * @return {boolean}
	 */
	isInitialValue: function() {
		return this.$labelview.data( 'labelview' ).isInitialValue()
			&& this.$descriptionview.data( 'descriptionview' ).isInitialValue()
			&& this.$aliasesview.data( 'aliasesview' ).isInitialValue();
	},

	/**
	 * Puts the widget into edit mode.
	 */
	startEditing: function() {
		if( this._isInEditMode ) {
			return;
		}

		this._isInEditMode = true;
		this.element.addClass( 'wb-edit' );

		this.$labelview.data( 'labelview' ).startEditing();
		this.$descriptionview.data( 'descriptionview' ).startEditing();
		this.$aliasesview.data( 'aliasesview' ).startEditing();

		this._trigger( 'afterstartediting' );
	},

	/**
	 * Stops the widget's edit mode.
	 *
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

		var labelview = this.$labelview.data( 'labelview' ),
			descriptionview = this.$descriptionview.data( 'descriptionview' ),
			aliasesview = this.$aliasesview.data( 'aliasesview' );

		// TODO: This widget should not need to queue the requests of its encapsulated widgets.
		// However, the back-end produces edit conflicts when issuing multiple requests at once.
		// Remove queueing as soon as the back-end is fixed; see bug #72020.
		var $queue = $( {} );

		/**
		 * @param {jQuery} $queue
		 * @param {jQuery.ui.TemplatedWidget} widget
		 * @param {boolean} dropValue
		 */
		function addStopEditToQueue( $queue, widget, dropValue ) {
			var eventName = widget.widgetEventPrefix + 'afterstopediting';
			$queue.queue( 'stopediting', function( next ) {
				widget.element
				.one( eventName + '.fingerprintview', function( event ) {
					setTimeout( next, 0 );
				} );
				widget.stopEditing( dropValue );
			} );
		}

		addStopEditToQueue( $queue, labelview, dropValue || labelview.isInitialValue() );
		addStopEditToQueue(
			$queue,
			descriptionview,
			dropValue || descriptionview.isInitialValue()
		);
		addStopEditToQueue( $queue, aliasesview, dropValue || aliasesview.isInitialValue() );

		$queue.queue( 'stopediting', function() {
			self._afterStopEditing( dropValue );
		} );

		$queue.dequeue( 'stopediting' );
	},

	/**
	 * @param {boolean} [dropValue]
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

	/**
	 * Cancels editing.
	 */
	cancelEditing: function() {
		this.stopEditing( true );
	},

	/**
	 * Sets/Gets the widget's value.
	 *
	 * @param {Object} [value]
	 * @return {Object|*}
	 */
	value: function( value ) {
		if( value !== undefined ) {
			return this.option( 'value', value );
		}

		return {
			language: this.options.value.language,
			label: this.$labelview.data( 'labelview' ).value().label,
			description: this.$descriptionview.data( 'descriptionview' ).value().description,
			aliases: this.$aliasesview.data( 'aliasesview' ).value().aliases
		};
	},

	/**
	 * @see jQuery.ui.TemplatedWidget._setOption
	 *
	 * @throws {Error} when trying to set value with a new language.
	 */
	_setOption: function( key, value ) {
		if( key === 'value' ) {
			value = this._checkValue( value );

			if( value.language !== this.options.value.language ) {
				throw new Error( 'Cannot alter language' );
			}

			this.$labelview.data( 'labelview' ).option( 'value', {
				language: value.language,
				label: value.label
			} );

			this.$descriptionview.data( 'descriptionview' ).option( 'value', {
				language: value.language,
				description: value.description
			} );

			this.$aliasesview.data( 'aliasesview' ).option( 'value', {
				language: value.language,
				aliases: value.aliases
			} );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this.$labelview.data( 'labelview' ).option( key, value );
			this.$descriptionview.data( 'descriptionview' ).option( key, value );
			this.$aliasesview.data( 'aliasesview' ).option( key, value );
		}

		return response;
	},

	/**
	 * @param {*} value
	 * @return {Object}
	 *
	 * @throws {Error} if value is not defined properly.
	 */
	_checkValue: function( value ) {
		if( !$.isPlainObject( value ) ) {
			throw new Error( 'Value needs to be an object' );
		} else if( !value.language ) {
			throw new Error( 'Value needs language to be specified' );
		}

		if( !value.label ) {
			value.label = null;
		}

		if( !value.description ) {
			value.description = null;
		}

		if( !value.aliases ) {
			value.aliases = [];
		}

		return value;
	},

	/**
	 * Sets keyboard focus on the first input element.
	 */
	focus: function() {
		this.$labelview.data( 'labelview' ).focus();
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
	}

} );

}( mediaWiki, wikibase, jQuery ) );
