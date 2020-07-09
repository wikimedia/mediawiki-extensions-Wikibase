/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget;

	/**
	 * Displays and allows editing label and description in a specific language.
	 *
	 * @extends jQuery.ui.TemplatedWidget
	 *
	 * @option {Object|null} value
	 *         Object representing the widget's value.
	 *         Structure: {
	 *           language: <{string}>,
	 *           label: <{wikibase.datamodel.Term}>,
	 *           description: <{wikibase.datamodel.Term}>,
	 *           aliases: <{wikibase.datamodel.MultiTerm}>
	 *         }
	 *
	 * @event change
	 *        - {jQuery.Event}
	 *        - {string} Language code the change was made in.
	 *
	 * @event afterstartediting
	 *       - {jQuery.Event}
	 *
	 * @event afterstopediting
	 *        - {jQuery.Event}
	 *        - {boolean} Whether to drop the value.
	 *
	 * @event toggleerror
	 *        - {jQuery.Event}
	 *        - {Error|null}
	 */
	$.widget( 'wikibase.entitytermsforlanguageview', PARENT, {
		options: {
			template: 'wikibase-entitytermsforlanguageview',
			templateParams: [
				'tr',
				'td',
				function () {
					return this.options.value.language;
				},
				function () {
					return wb.getLanguageNameByCode( this.options.value.language );
				},
				'', // label
				'', // description
				'', // aliases
				'', // toolbar placeholder
				'th' // row table header
			],
			templateShortCuts: {
				$language: '.wikibase-entitytermsforlanguageview-language',
				$label: '.wikibase-entitytermsforlanguageview-label',
				$description: '.wikibase-entitytermsforlanguageview-description',
				$aliases: '.wikibase-entitytermsforlanguageview-aliases'
			},
			value: null
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
		 * @see jQuery.ui.TemplatedWidget._create
		 */
		_create: function () {
			this.options.value = this._checkValue( this.options.value );

			PARENT.prototype._create.call( this );

			this._createWidgets();
		},

		/**
		 * @see jQuery.ui.TemplatedWidget.destroy
		 */
		destroy: function () {
			var self = this;

			function degrade() {
				if ( self.$labelview ) {
					self.$labelview.data( 'labelview' ).destroy();
				}
				if ( self.$descriptionview ) {
					self.$descriptionview.data( 'descriptionview' ).destroy();
				}
				if ( self.$aliasesview ) {
					self.$aliasesview.data( 'aliasesview' ).destroy();
				}

				PARENT.prototype.destroy.call( self );
			}

			if ( this.isInEditMode() ) {
				this.element.one( this.widgetEventPrefix + 'afterstopediting', function ( event ) {
					degrade();
				} );

				this.stopEditing( true );
			} else {
				degrade();
			}
		},

		/**
		 * Creates labelview, descriptionview and aliasesview widget
		 */
		_createWidgets: function () {
			var self = this;

			[ 'label', 'description', 'aliases' ].forEach( function ( subjectName ) {
				var widgetName = subjectName + 'view';

				self[ '$' + widgetName ] = self[ '$' + subjectName ].children( '.wikibase-' + widgetName );

				if ( !self[ '$' + widgetName ].length ) {
					self[ '$' + widgetName ] = $( '<div>' ).appendTo( self[ '$' + subjectName ] );
				}

				// Fully encapsulate child widgets by suppressing their events:
				self[ '$' + widgetName ]
				.on( widgetName + 'change', function ( event ) {
					event.stopPropagation();
					// The only event handler for this is in entitytermsforlanguagelistview.
					self._trigger( 'change', null, [ self.options.value.language ] );
				} )
				.on( widgetName + 'toggleerror.' + self.widgetName, function ( event, error ) {
					event.stopPropagation();
					self.setError( error );
				} )
				.on(
					[
						widgetName + 'create.' + self.widgetName,
						widgetName + 'afterstartediting.' + self.widgetName,
						widgetName + 'afterstopediting.' + self.widgetName,
						widgetName + 'disable.' + self.widgetName
					].join( ' ' ),
					function ( event ) {
						event.stopPropagation();
					}
				);

				var options = {
					value: self.options.value[ subjectName ],
					helpMessage: mw.msg(
						// The following messages can be used here:
						// * wikibase-label-input-help-message
						// * wikibase-description-input-help-message
						// * wikibase-aliases-input-help-message
						'wikibase-' + subjectName + '-input-help-message',
						wb.getLanguageNameByCode( self.options.value.language )
					)
				};

				self[ '$' + widgetName ][ widgetName ]( options );
			} );
		},

		/**
		 * Puts the widget into edit mode.
		 */
		_startEditing: function () {
			this.$labelview.data( 'labelview' ).startEditing();
			this.$descriptionview.data( 'descriptionview' ).startEditing();
			this.$aliasesview.data( 'aliasesview' ).startEditing();
			return $.Deferred().resolve().promise();
		},

		/**
		 * @param {boolean} [dropValue]
		 */
		_stopEditing: function ( dropValue ) {
			this.$labelview.data( 'labelview' ).stopEditing( dropValue );
			this.$descriptionview.data( 'descriptionview' ).stopEditing( dropValue );
			this.$aliasesview.data( 'aliasesview' ).stopEditing( dropValue );
			return $.Deferred().resolve().promise();
		},

		/**
		 * Sets/Gets the widget's value.
		 *
		 * @param {Object} [value]
		 * @return {Object|*}
		 */
		value: function ( value ) {
			if ( value !== undefined ) {
				return this.option( 'value', value );
			}

			return {
				language: this.options.value.language,
				label: this.$labelview.data( 'labelview' ).value(),
				description: this.$descriptionview.data( 'descriptionview' ).value(),
				aliases: this.$aliasesview.data( 'aliasesview' ).value()
			};
		},

		/**
		 * @see jQuery.ui.TemplatedWidget._setOption
		 *
		 * @throws {Error} when trying to set value with a new language.
		 */
		_setOption: function ( key, value ) {
			if ( key === 'value' ) {
				value = this._checkValue( value );

				if ( value.language !== this.options.value.language ) {
					throw new Error( 'Cannot alter language' );
				}

				this.$labelview.data( 'labelview' ).option( 'value', value.label );
				this.$descriptionview.data( 'descriptionview' ).option( 'value', value.description );
				this.$aliasesview.data( 'aliasesview' ).option( 'value', value.aliases );
			}

			var response = PARENT.prototype._setOption.apply( this, arguments );

			if ( key === 'disabled' ) {
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
		_checkValue: function ( value ) {
			if ( !$.isPlainObject( value ) ) {
				throw new Error( 'Value needs to be an object' );
			} else if ( !value.language ) {
				throw new Error( 'Value needs language to be specified' );
			}

			if ( !value.label ) {
				throw new Error( 'label needs to be a wb.datamodel.Term instance' );
			}

			if ( !value.description ) {
				throw new Error( 'description needs to be a wb.datamodel.Term instance' );
			}

			if ( !value.aliases ) {
				throw new Error( 'aliases need to be a wb.datamodel.MultiTerm instance' );
			}

			return value;
		},

		/**
		 * @see jQuery.ui.TemplatedWidget.focus
		 */
		focus: function () {
			this.$labelview.data( 'labelview' ).focus();
		},

		removeError: function () {
			PARENT.prototype.removeError.call( this );

			this.$labelview.data( 'labelview' ).removeError();
			this.$descriptionview.data( 'descriptionview' ).removeError();
			this.$aliasesview.data( 'aliasesview' ).removeError();
		}

	} );

}( wikibase ) );
