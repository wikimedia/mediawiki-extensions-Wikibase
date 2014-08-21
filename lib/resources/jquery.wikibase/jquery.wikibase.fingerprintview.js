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
 *         Structure: { language: <{string}>, label: <{string|null}>, description: <{string|null> }
 *
 * @option {string} [helpMessage]
 *         Default: mw.msg( 'wikibase-fingerprintview-input-help-message' )
 *
 * @options {string} entityId
 *
 * @option {wikibase.RepoApi} api
 *
 * @event change
 *        - {jQuery.Event}
 *
 * @event afterstartediting
 *       - [jQuery.Event}
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
				return wb.getLanguageNameByCode( this.options.value.language );
			},
			'', // label
			'', // description
			'', // label toolbar
			'', // description toolbar
			'', // additional label CSS classes
			'', // additional description CSS classes
			function() {
				var title = new mw.Title(
					mw.config.get( 'wgTitle' ),
					mw.config.get( 'wgNamespaceNumber' )
				);

				return title.getUrl( { setlang: this.options.value.language } );
			}
		],
		templateShortCuts: {
			$language: '.wikibase-fingerprintview-language',
			$label: '.wikibase-fingerprintview-label',
			$description : '.wikibase-fingerprintview-description'
		},
		value: null,
		helpMessage: mw.msg( 'wikibase-fingerprintview-input-help-message' ),
		entityId: null,
		api: null
	},

	/**
	 * @type {boolean}
	 */
	_isInEditMode: false,

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 */
	_create: function() {
		this.options.value = this._checkValue( this.options.value );

		PARENT.prototype._create.call( this );

		this._createWidgets();
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.destroy
	 */
	destroy: function() {
		var self = this;

		function degrade() {
			if( self.$label ) {
				self.$label.data( 'labelview' ).destroy();
			}
			if( self.$description ) {
				self.$description.data( 'descriptionview' ).destroy();
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

		$.each( ['label', 'description'], function( i, subjectName ) {
			var widgetName = subjectName + 'view',
				$subject = self['$' + subjectName];

			// Fully encapsulate labelview and descriptionview by suppressing their events:
			$subject
			.on( widgetName + 'change', function( event ) {
				event.stopPropagation();
				self._trigger( 'change' );
			} )
			.on( widgetName + 'toggleerror.' + self.widgetName, function( event, error ) {
				event.stopPropagation();
				self.setError( error );
			} )
			.on(
				widgetName + 'create.' + self.widgetName + ' '
				+ widgetName + 'afterstartediting.' + self.widgetName + ' '
				+ widgetName + 'stopediting.' + self.widgetName + ' '
				+ widgetName + 'afterstopediting.' + self.widgetName,
				function( event ) {
					event.stopPropagation();
				}
			);

			$subject[widgetName]( {
				value: self.options.value,
				helpMessage: mw.msg(
					'wikibase-' + subjectName + '-input-help-message',
					wb.getLanguageNameByCode( self.options.value.language )
				),
				entityId: self.options.entityId,
				api: self.options.api
			} );
		} );
	},

	/**
	 * @return {boolean}
	 */
	isValid: function() {
		return this.$label.data( 'labelview' ).isValid()
			&& this.$description.data( 'descriptionview' ).isValid();
	},

	/**
	 * @return {boolean}
	 */
	isInitialValue: function() {
		return this.$label.data( 'labelview' ).isInitialValue()
			&& this.$description.data( 'descriptionview' ).isInitialValue();
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

		this.$label.data( 'labelview' ).startEditing();
		this.$description.data( 'descriptionview' ).startEditing();

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

		var labelview = this.$label.data( 'labelview' ),
			descriptionview = this.$description.data( 'descriptionview' ),
			stoppedEditing = false;

		function detectAfterStopEditing() {
			if( !stoppedEditing ) {
				stoppedEditing = true;
				return;
			}
			self._afterStopEditing( dropValue );
		}

		this.$label.one( 'labelviewafterstopediting', detectAfterStopEditing );
		this.$description.one( 'descriptionviewafterstopediting', detectAfterStopEditing );

		labelview.stopEditing( dropValue || labelview.isInitialValue() );
		descriptionview.stopEditing( dropValue || descriptionview.isInitialValue() );
	},

	/**
	 * @param {boolean} [dropValue]
	 */
	_afterStopEditing: function( dropValue ) {
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
	 * @return {Object|undefined}
	 */
	value: function( value ) {
		if( value !== undefined ) {
			return this.option( 'value', value );
		}

		return {
			language: this.options.value.language,
			label: this.$label.data( 'labelview' ).value().label,
			description: this.$description.data( 'descriptionview' ).value().description
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

			this.$label.data( 'labelview' ).option( 'value', {
				language: value.language,
				label: value.label
			} );

			this.$description.data( 'descriptionview' ).option( 'value', {
				language: value.language,
				description: value.description
			} );
		}
		return PARENT.prototype._setOption.call( this, key, value );
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

		return value;
	},

	/**
	 * Sets keyboard focus on the first input element.
	 */
	focus: function() {
		this.$label.data( 'labelview' ).focus();
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

$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'fingerprintview',
	selector: ':' + $.wikibase.fingerprintview.prototype.namespace
		+ '-' + $.wikibase.fingerprintview.prototype.widgetName,
	events: {
		fingerprintviewcreate: function( event, toolbarcontroller ) {
			var $fingerprintview = $( event.target ),
				fingerprintview = $fingerprintview.data( 'fingerprintview' );

			$fingerprintview.edittoolbar( {
				$container: $( '<td rowspan="2" />' )
					.appendTo( $fingerprintview.children( 'tr' ).first() ),
				interactionWidgetName: $.wikibase.fingerprintview.prototype.widgetName,
				enableRemove: false
			} );

			$fingerprintview.on( 'keyup', function( event ) {
				if( fingerprintview.option( 'disabled' ) ) {
					return;
				}
				if( event.keyCode === $.ui.keyCode.ESCAPE ) {
					fingerprintview.stopEditing( true );
				} else if( event.keyCode === $.ui.keyCode.ENTER ) {
					fingerprintview.stopEditing( false );
				}
			} );
		},
		'fingerprintviewchange fingerprintviewafterstartediting': function( event ) {
			var $fingerprintview = $( event.target ),
				fingerprintview = $fingerprintview.data( 'fingerprintview' ),
				toolbar = $fingerprintview.data( 'edittoolbar' ).toolbar,
				$btnSave = toolbar.editGroup.getButton( 'save' ),
				btnSave = $btnSave.data( 'toolbarbutton' ),
				enable = fingerprintview.isValid() && !fingerprintview.isInitialValue();

			btnSave[enable ? 'enable' : 'disable']();
		},
		toolbareditgroupedit: function( event, toolbarcontroller ) {
			var $fingerprintview = $( event.target ).closest( ':wikibase-edittoolbar' ),
				fingerprintview = $fingerprintview.data( 'labelview' );

			if( !fingerprintview ) {
				return;
			}

			fingerprintview.focus();
		}
	}
} );

}( mediaWiki, wikibase, jQuery ) );
