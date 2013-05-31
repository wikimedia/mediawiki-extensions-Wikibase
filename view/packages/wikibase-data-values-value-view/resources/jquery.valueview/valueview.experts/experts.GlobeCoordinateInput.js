/**
 * @file
 * @ingroup ValueView
 * @licence GNU GPL v2+
 *
 * @author H. Snater < mediawiki@snater.com >
 */
// TODO: Remove mediaWiki dependency
( function( dv, vp, $, vv, globeCoordinate, mw ) {
	'use strict';

	var GlobeCoordinate = globeCoordinate.GlobeCoordinate,
		globeCoordinateSettings = globeCoordinate.settings;

	var PARENT = vv.Expert;

	/**
	 * Valueview expert handling input of globe coordinate values.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.Expert
	 */
	vv.experts.GlobeCoordinateInput = vv.expert( 'globecoordinateinput', PARENT, {
		/**
		 * The the input element's node.
		 * @type {jQuery}
		 */
		$input: null,

		/**
		 * Caches a new value (or null for no value) set by _setRawValue() until draw() displaying
		 * the new value has been called. The use of this, basically, is a structural improvement
		 * which allows moving setting the displayed value to the draw() method which is supposed to
		 * handle all visual manners.
		 * @type {globeCoordinate.globeCoordinate|null|false}
		 */
		_newValue: null,

		/**
		 * The preview widget.
		 * @type {jQuery.valueview.preview}
		 */
		preview: null,

		/**
		 * Container node for precision input and label.
		 * @type {jQuery}
		 */
		$precisionContainer: null,

		/**
		 * Node of the widget used to specify the precision.
		 * @type {jQuery}
		 */
		$precision: null,

		/**
		 * @see jQuery.valueview.Expert._init
		 */
		_init: function() {
			var self = this;

			this.$precisionContainer = $( '<div/>' )
			.addClass( this.uiBaseClass + '-precisioncontainer' )
			.append( $( '<div/>' ).text( mw.msg( 'valueview-expert-globecoordinateinput-precision' ) ) );

			var precisionValues = [];
			$.each( globeCoordinateSettings.precisions, function( i, precisionDefinition ) {
				var label = ( precisionDefinition.text )
					? precisionDefinition.text
					: precisionDefinition.level;

				precisionValues.push( { value: precisionDefinition.level, label: label } );
			} );

			this.$precision = $( '<div/>' )
				.addClass( this.uiBaseClass + '-precision' )
				.listrotator( {
					values: precisionValues.reverse(),
					deferInit: true
				} )
				.on(
				'listrotatorauto.' + this.uiBaseClass + ' listrotatorselected.' + this.uiBaseClass,
				function( event ) {
					var overwrite = {};

					if( event.type === 'listrotatorauto' ) {
						overwrite.precision = undefined;
					}

					var value = self._updateValue( overwrite );

					if( event.type === 'listrotatorauto' ) {
						$( this ).data( 'listrotator' ).rotate( value.getPrecision() );
					}
				}
			)
			.appendTo( this.$precisionContainer );

			var $toggler = $( '<a/>' )
			.addClass( this.uiBaseClass + '-advancedtoggler' )
			.text( mw.msg( 'valueview-expert-advancedadjustments' ) );

			this.$input = $( '<input/>', {
				type: 'text',
				'class': this.uiBaseClass + '-input valueview-input'
			} )
			.appendTo( this.$viewPort );

			var $preview = $( '<div/>' ).preview( { $input: this.$input } );
			this.preview = $preview.data( 'preview' );

			this.$input.eachchange( function( event, oldValue ) {
				var value = self.$input.data( 'globecoordinateinput' ).value();
				if( oldValue === '' && value === null || self.$input.val() === '' ) {
					self._updatePreview();
				}
			} )
			.globecoordinateinput()
			.inputextender( {
				content: [ $preview, $toggler, this.$precisionContainer ],
				initCallback: function() {
					self.$precision.data( 'listrotator' ).initWidths();
					self.$precisionContainer.css( 'display', 'none' );
					$toggler.toggler( { $subject: self.$precisionContainer } );
				}
			} )
			.on( 'globecoordinateinputupdate.' + this.uiBaseClass, function( event, value ) {
				if( value && value.isValid() ) {
					self.$precision.data( 'listrotator' ).rotate( value.getPrecision() );
				}
				self._newValue = false; // value, not yet handled by draw(), is outdated now
				self._viewNotifier.notify( 'change' );
				self._updatePreview();
			} );

		},

		/**
		 * @see jQuery.valueview.Expert.destroy
		 */
		destroy: function() {
			this.$precision.data( 'listrotator' ).destroy();
			this.$precision.remove();
			this.$precisionContainer.remove();

			var previewElement = this.preview.element;
			this.preview.destroy();
			previewElement.remove();

			this.$input.data( 'inputextender' ).destroy();
			this.$input.data( 'globecoordinateinput' ).destroy();
			this.$input.remove();

			PARENT.prototype.destroy.call( this );
		},

		/**
		 * Builds a globeCoordinate.GlobeCoordinate object from the widget's current input taking the
		 * precision into account if set manually.
		 *
		 * @param {Object} [overwrites] Values that should be used instead of the ones picked from
		 *        the input elements.
		 * @return {globeCoordinate.GlobeCoordinate}
		 */
		_updateValue: function( overwrites ) {
			overwrites = overwrites || {};

			var options = {},
				precision = ( overwrites.hasOwnProperty( 'precision' ) )
					? overwrites.precision
					: this.$precision.data( 'listrotator' ).value(),
				value;

			if( precision !== undefined ) {
				options.precision = precision;
			}

			value = new GlobeCoordinate( this.$input.val(), options );

			this._setRawValue( value );
			this._updatePreview();
			this._viewNotifier.notify( 'change' );

			return value;
		},

		/**
		 * Updates the preview.
		 */
		_updatePreview: function() {
			var rawValue = this._getRawValue();
			this.preview.update( ( rawValue ) ? rawValue.degreeText() : null );
		},

		/**
		 * @see jQuery.valueview.Expert.parser
		 */
		parser: function() {
			return new vp.GlobeCoordinateParser();
		},

		/**
		 * @see jQuery.valueview.Expert._getRawValue
		 *
		 * @return {globeCoordinate.GlobeCoordinate|null}
		 */
		_getRawValue: function() {
			return ( this._newValue !== false )
				? this._newValue
				: this.$input.data( 'globecoordinateinput' ).value();
		},

		/**
		 * @see jQuery.valueview.Expert._setRawValue
		 *
		 * @param {globeCoordinate.GlobeCoordinate|null} globeCoordinate
		 */
		_setRawValue: function( globeCoordinate ) {
			if( !( globeCoordinate instanceof GlobeCoordinate ) || !globeCoordinate.isValid() ) {
				globeCoordinate = null;
			}
			this._newValue = globeCoordinate;
		},

		/**
		 * @see jQuery.valueview.Expert.rawValueCompare
		 */
		rawValueCompare: function( globeCoordinate1, globeCoordinate2 ) {
			if( globeCoordinate2 === undefined ) {
				globeCoordinate2 = this._getRawValue();
			}

			if( globeCoordinate1 === null && globeCoordinate2 === null ) {
				return true;
			}

			if(
				!( globeCoordinate1 instanceof GlobeCoordinate )
				|| !( globeCoordinate2 instanceof GlobeCoordinate )
			) {
				return false;
			}

			return globeCoordinate1.equals( globeCoordinate2 );
		},

		/**
		 * @see jQuery.valueview.Expert.draw
		 */
		draw: function() {
			if( this._viewState.isDisabled() ) {
				this.$input.data( 'globecoordinateinput' ).disable();
			} else {
				this.$input.data( 'globecoordinateinput' ).enable();
			}

			if( this._newValue !== false ) {
				this.$input.data( 'globecoordinateinput' ).value( this._newValue );
				if( this._newValue !== null ) {
					this.$precision.data( 'listrotator' ).value( this._newValue.getPrecision() );
				}
				this._newValue = false;
				this._updatePreview();
			}
		},

		/**
		 * @see jQuery.valueview.Expert.focus
		 */
		focus: function() {
			this.$input.focusAt( 'end' );
		},

		/**
		 * @see jQuery.valueview.Expert.blur
		 */
		blur: function() {
			this.$input.blur();
		}
	} );

}( dataValues, valueParsers, jQuery, jQuery.valueview, globeCoordinate, mediaWiki ) );
