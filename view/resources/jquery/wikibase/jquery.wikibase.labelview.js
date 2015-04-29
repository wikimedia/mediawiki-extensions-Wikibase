( function( $, mw, wb ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget;

/**
 * Displays and allows editing of a `wikibase.datamodel.Term` acting as an `Entity`'s label.
 * @since 0.5
 * @class jQuery.wikibase.labelview
 * @extends jQuery.ui.EditableTemplatedWidget
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Object} options
 * @param {wikibase.datamodel.Term} options.value
 * @param {string} [options.helpMessage=mw.msg( 'wikibase-label-input-help-message' )]
 * @param {wikibase.entityChangers.LabelsChanger} options.labelsChanger
 * @param {string} options.entityId
 * @param {boolean} [options.showEntityId=false]
 */
$.widget( 'wikibase.labelview', PARENT, {
	/**
	 * @inheritdoc
	 * @protected
	 */
	options: {
		template: 'wikibase-labelview',
		templateParams: [
			'', // additional class
			'', // text
			'', // entity id
			'' // toolbar
		],
		templateShortCuts: {
			$text: '.wikibase-labelview-text',
			$entityId: '.wikibase-labelview-entityid'
		},
		value: null,
		inputNodeName: 'TEXTAREA',
		helpMessage: mw.msg( 'wikibase-label-input-help-message' ),
		entityId: null,
		showEntityId: false
	},

	/**
	 * @inheritdoc
	 * @protected
	 *
	 * @throws {Error} if a required option is not specified properly.
	 */
	_create: function() {
		if(
			!( this.options.value instanceof wb.datamodel.Term )
			|| !this.options.entityId
			|| !this.options.labelsChanger
			|| this.options.inputNodeName !== 'INPUT' && this.options.inputNodeName !== 'TEXTAREA'
		) {
			throw new Error( 'Required option not specified properly' );
		}

		var self = this;

		this.element
		.on(
			'labelviewafterstartediting.' + this.widgetName
			+ ' eachchange.' + this.widgetName,
		function( event ) {
			if( self.value().getText() === '' ) {
				// Since the widget shall not be in view mode when there is no value, triggering
				// the event without a proper value is only done when creating the widget. Disabling
				// other edit buttons shall be avoided.
				// TODO: Move logic to a sensible place.
				self.element.addClass( 'wb-empty' );
				return;
			}

			self.element.removeClass( 'wb-empty' );
		} );

		PARENT.prototype._create.call( this );

		if( this.$text.text() === '' ) {
			this.draw();
		}
	},

	/**
	 * @inheritdoc
	 */
	destroy: function() {
		if( this.isInEditMode() ) {
			var self = this;

			this.element.one( this.widgetEventPrefix + 'afterstopediting', function( event ) {
				PARENT.prototype.destroy.call( self );
			} );

			this.cancelEditing();
		} else {
			PARENT.prototype.destroy.call( this );
		}
	},

	/**
	 * @inheritdoc
	 */
	draw: function() {
		var self = this,
			deferred = $.Deferred(),
			languageCode = this.options.value.getLanguageCode(),
			labelText = this.options.value.getText();

		if( labelText === '' ) {
			labelText = null;
		}

		if( this.options.showEntityId && !( this.isInEditMode() && labelText ) ) {
			this.$entityId.text( mw.msg( 'parentheses', this.options.entityId ) );
		} else {
			this.$entityId.empty();
		}

		this.element[labelText ? 'removeClass' : 'addClass']( 'wb-empty' );

		if( !this.isInEditMode() && !labelText ) {
			this.$text.text( mw.msg( 'wikibase-label-empty' ) );
			// Apply lang and dir of UI language
			// instead language of that row
			var userLanguage = mw.config.get( 'wgUserLanguage' );
			this.element
			.attr( 'lang', userLanguage )
			.attr( 'dir', $.util.getDirectionality( userLanguage ) );
			return deferred.resolve().promise();
		}

		this.element
		.attr( 'lang', languageCode )
		.attr( 'dir', $.util.getDirectionality( languageCode ) );

		if( !this.isInEditMode() ) {
			this.$text.text( labelText );
			return deferred.resolve().promise();
		}

		var $input = $( document.createElement( this.options.inputNodeName ) );

		$input
		.addClass( this.widgetFullName + '-input' )
		// TODO: Inject correct placeholder via options
		.attr( 'placeholder', mw.msg(
				'wikibase-label-edit-placeholder-language-aware',
				wb.getLanguageNameByCode( languageCode )
			)
		)
		.attr( 'lang', languageCode )
		.attr( 'dir', $.util.getDirectionality( languageCode ) )
		.on( 'keydown.' + this.widgetName, function( event ) {
			if( event.keyCode === $.ui.keyCode.ENTER ) {
				event.preventDefault();
			}
		} )
		.on( 'eachchange.' + this.widgetName, function( event ) {
			self._trigger( 'change' );
		} );

		if( labelText ) {
			$input.val( labelText );
		}

		if( $.fn.inputautoexpand ) {
			$input.inputautoexpand( {
				expandHeight: true,
				suppressNewLine: true
			} );
		}

		this.$text.empty().append( $input );

		return deferred.resolve().promise();
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_save: function() {
		var deferred = $.Deferred();

		this.options.labelsChanger.setLabel( this.value() )
		.done( function( label ) {
			deferred.resolve();
		} )
		.fail( deferred.reject );

		return deferred.promise();
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_afterStopEditing: function( dropValue ) {
		if( dropValue && this.options.value.getText() === '' ) {
			this.$text.children( '.' + this.widgetFullName + '-input' ).val( '' );
		}
		return PARENT.prototype._afterStopEditing.call( this, dropValue );
	},

	/**
	 * @inheritdoc
	 */
	isValid: function() {
		return true;
	},

	/**
	 * @inheritdoc
	 */
	isInitialValue: function() {
		return this.value().equals( this.options.value );
	},

	/**
	 * @inheritdoc
	 * @protected
	 *
	 * @throws {Error} when trying to set the widget's value to something other than a
	 *         `wikibase.datamodel.Term` instance.
	 */
	_setOption: function( key, value ) {
		if( key === 'value' && !( value instanceof wb.datamodel.Term ) ) {
			throw new Error( 'Value needs to be a wb.datamodel.Term instance' );
		}

		var response = PARENT.prototype._setOption.call( this, key, value );

		if( key === 'disabled' && this.isInEditMode() ) {
			this.$text.children( '.' + this.widgetFullName + '-input' ).prop( 'disabled', value );
		}

		return response;
	},

	/**
	 * @inheritdoc
	 *
	 * @param {wikibase.datamodel.Term} [value]
	 * @return {wikibase.datamodel.Term|undefined}
	 */
	value: function( value ) {
		if( value !== undefined ) {
			this.option( 'value', value );
			return;
		}

		if( !this.isInEditMode() ) {
			return this.option( 'value' );
		}

		return new wb.datamodel.Term(
			this.options.value.getLanguageCode(),
			$.trim( this.$text.children( '.' + this.widgetFullName + '-input' ).val() )
		);
	},

	/**
	 * @inheritdoc
	 */
	focus: function() {
		if( this.isInEditMode() ) {
			this.$text.children( '.' + this.widgetFullName + '-input' ).focus();
		} else {
			this.element.focus();
		}
	}

} );

}( jQuery, mediaWiki, wikibase ) );
