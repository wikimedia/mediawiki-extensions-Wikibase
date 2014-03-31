/**
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $, vv, GlobeCoordinate, Formatter ) {
	'use strict';

	var PARENT = vv.experts.StringValue;

	/**
	 * Valueview expert handling input of globe coordinate values.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.Expert
	 */
	vv.experts.GlobeCoordinateInput = vv.expert( 'GlobeCoordinateInput', PARENT, {
		/**
		 * Options.
		 * @type {Object}
		 */
		_options: {
			messages: {
				'valueview-expert-advancedadjustments': 'advanced adjustments',
				'valueview-expert-globecoordinateinput-precision': 'Precision'
			}
		},

		/**
		 * The preview widget.
		 * @type {jQuery.ui.preview}
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
		 * @see jQuery.valueview.experts.StringExpert._init
		 */
		_init: function() {
			var self = this;

			PARENT.prototype._init.call( this );
			this.$input.inputextender( {
				initCallback: function( $extension ) {
					self._initInputExtender( $extension );
					// $extension not yet in DOM, so draw() would not update rotators. Call draw
					// as soon as toggle has been done, from this point on the inputextender's
					// extension() is available in draw().
					self.$input.one( 'inputextenderaftertoggle', function( event ) {
						self.draw();
					} );
				},
				contentAnimationEvents: 'toggleranimation'
			} );

			this._initialDraw();
		},

		/**
		 * Initializes the input extender with the required content into the given DOM element.
		 *
		 * TODO: Split this up. Share code with similar experts (Time).
		 *
		 * @param {jQuery} $extension
		 */
		_initInputExtender: function( $extension ) {
			var self = this,
				precisionMsgKey = 'valueview-expert-globecoordinateinput-precision',
				precisionValues = [],
				listrotatorEvents = 'listrotatorauto listrotatorselected'
					.replace( /(\w+)/g, '$1.' + this.uiBaseClass );

			this.$precisionContainer = $( '<div/>' )
			.addClass( this.uiBaseClass + '-precisioncontainer' )
			.append( $( '<div/>' ).text( this._messageProvider.getMessage( precisionMsgKey ) ) );

			$.each( GlobeCoordinate.PRECISIONS, function( i, precision ) {
				var label = Formatter.PRECISIONTEXT( precision );
				precisionValues.push( {
					value: roundPrecision( precision ),
					label: label
				} );
			} );

			this.$precision = $( '<div/>' )
			.addClass( this.uiBaseClass + '-precision' )
			.listrotator( {
				values: precisionValues.reverse(),
				deferInit: true
			} )
			.on( listrotatorEvents, function( event, newPrecisionLevel ) {
				var currentValue = self.viewState().value();

				if( currentValue === null ) {
					// current rawValue must be invalid anyhow
					return;
				}

				var currentPrecision = roundPrecision(
						currentValue.getValue().getPrecision() );

				if( newPrecisionLevel === currentPrecision ) {
					// Listrotator has been rotated automatically or the value covering the new
					// precision has already been generated.
					return;
				}

				self._viewNotifier.notify( 'change' );
			} )
			.appendTo( this.$precisionContainer );

			var $toggler = $( '<a/>' )
			.addClass( this.uiBaseClass + '-advancedtoggler' )
			.text( this._messageProvider.getMessage( 'valueview-expert-advancedadjustments' ) );

			var messageProvider = null;
			if( mediaWiki && mediaWiki.msg && util && util.MessageProvider ) {
				messageProvider = new util.MessageProvider( {
					messageGetter: mediaWiki.msg,
					prefix: 'valueview-preview-'
				} );
			}

			var $preview = $( '<div/>' ).preview( {
				$input: this.$input,
				messageProvider: messageProvider
			} );
			this.preview = $preview.data( 'preview' );

			// Append everything since the following actions require the fully initialized DOM.
			$extension.append( [
				$preview,
				$toggler,
				this.$precisionContainer
			] );
			this.$precision.data( 'listrotator' ).initWidths();
			this.$precisionContainer.css( 'display', 'none' );
			$toggler.toggler( { $subject: this.$precisionContainer } );
		},

		/**
		 * @see jQuery.valueview.Expert.destroy
		 */
		destroy: function() {
			if( !this.$input ) {
				return; // destroyed already
			}

			if( this.preview ) {
				this.preview.destroy();
				this.preview.element.remove();
			}

			var listRotator = this.$precision && this.$precision.data( 'listrotator' );
			if( listRotator ) {
				listRotator.destroy();
			}

			var inputExtender = this.$input.data( 'inputextender' );
			if( inputExtender ) {
				if( inputExtender.$extension ) {
					inputExtender.$extension.find( this.uiBaseClass + '-advancedtoggler' )
						.toggler( 'destroy' );
				}
				inputExtender.destroy();
			}

			this.$precision = null;
			this.$precisionContainer = null;

			PARENT.prototype.destroy.call( this ); // empties viewport
		},

		/**
		 * @see jQuery.valueview.Expert.valueCharacteristics
		 */
		valueCharacteristics: function() {
			if( !this.$precision ) { // happens when called statically
				return {};
			}

			var options = {},
				precisionWidget = this.$precision.data( 'listrotator' );

			if( !precisionWidget.autoActive() ) {
				var precision = getPrecisionSetting( precisionWidget.value() );

				if( precision !== null ) {
					options.precision = precision;
				}
			}

			return options;
		},

		_initialDraw: function() {
			var geoValue = this.viewState().value();
			if( geoValue ) {
				var considerInputExtender = this.$input.data( 'inputextender' ).extensionIsVisible();

				if( considerInputExtender ) {
					this.$precision.data( 'listrotator' ).value(
						roundPrecision( geoValue.getValue().getPrecision() ) );
				}
			}
		},

		/**
		 * @see jQuery.valueview.Expert.draw
		 */
		draw: function() {
			PARENT.prototype.draw.call( this );

			var considerInputExtender = this.$input.data( 'inputextender' ).extensionIsVisible();
			if( considerInputExtender ) {
				this.preview.update( this.viewState().getTextValue() );
				var geoValue = this.viewState().value();
				if( geoValue && this.$precision.data( 'listrotator' ).autoActive() ) {
					this.$precision.data( 'listrotator' ).value(
						roundPrecision( geoValue.getValue().getPrecision() ) );
				}
			}
		},
	} );

	/**
	 * Rounds a given precision for being able to use it as internal "constant".
	 *
	 * TODO: Calculated numbers used for the precision (e.g. 1/60) may result in different values
	 *  in front- and back-end. Either use dedicated float numbers in front- and back-end or
	 *  integrate the rounding in GlobeCoordinate.
	 *
	 * @since 0.1
	 *
	 * @param {number} precision
	 * @return {number}
	 */
	function roundPrecision( precision ) {
		var precisions = GlobeCoordinate.PRECISIONS,
			highestPrecision = precisions[precisions.length - 1],
			multiplier = 1;

		// To make sure that not too much digits are cut off for the "constant" precisions to be
		// distinctive, we round with a multiplier that rounds the most precise precision to a
		// number greater than 1:
		while( highestPrecision * multiplier < 1 ) {
			multiplier *= 10;
		}

		return Math.round( precision * multiplier ) / multiplier;
	}

	/**
	 * Returns the original precision level for a rounded precision.
	 *
	 * @since 0.1
	 *
	 * @param {number} roundedPrecision
	 * @return {number|null}
	 */
	function getPrecisionSetting( roundedPrecision ) {
		var rounded,
			actualPrecision = null;

		$.each( GlobeCoordinate.PRECISIONS, function( i, precision ) {
			rounded = roundPrecision( precision );
			if( rounded === roundedPrecision ) {
				actualPrecision = precision;
				return false;
			}
		} );

		return actualPrecision;
	}

}( jQuery, jQuery.valueview, globeCoordinate.GlobeCoordinate, globeCoordinate.Formatter ) );
