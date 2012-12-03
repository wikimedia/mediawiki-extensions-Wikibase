/**
 * Registration of global JavaScript template function.
 *
 * @since 0.2
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( mw, $, undefined ) {
	'use strict';

	/**
	 * Returns a template filled with the specified parameters, similar to wfTemplate().
	 * @see mw.message
	 *
	 * @param {String} key Key of the template to get.
	 * @param {String|jQuery|Array} parameter_1 First argument in a list of variadic arguments, each
	 *        a parameter for $N replacement in templates. Instead of making use of variadic
	 *        arguments, an array may be passed as first parameter.
	 * @return {jQuery}
	 */
	mw.template = function( key, parameter_1 /* [, parameter_2] */ ) {
		var i,
			params = [],
			template, parsedTemplate,
			strippedTemplate, strippedParsedTemplate,
			$wrappedTemplate,
			tempParams = [],
			delayedParams = [];

		if ( parameter_1 !== undefined ) {
			if ( $.isArray( parameter_1 ) ) {
				params = parameter_1;
			} else { // support variadic arguments
				params = Array.prototype.slice.call( arguments );
				params.shift();
			}
		}

		// pre-parse the template inserting strings and placeholder nodes for jQuery objects
		// jQuery objects will be appended after the template has been parsed to not lose any
		// references
		for ( i = 0; i < params.length; i++ ) {
			if ( typeof params[i] === 'string' ) {
				// insert strings into the template directly
				tempParams.push( mw.html.escape( params[i] ) );
			} else if ( params[i] instanceof jQuery ) {
				// construct temporary placeholder nodes
				// (using an actual invalid class name to not interfere with any other node)
				var nodeName = params[i][0].nodeName.toLowerCase();
				tempParams.push( '<' + nodeName + ' class="--mwTemplate"></' + nodeName + '>' );
				delayedParams.push( params[i] );
			} else {
				throw new Error( 'mw.template: Wrong parameter type. Pass either String or jQuery.' );
			}
		}

		template = new mw.Message( mw.templates.store, key, tempParams );

		// wrap template inside a html container to be able to easily access all temporary nodes and
		// insert any jQuery objects
		$wrappedTemplate = $( '<html/>' ).html( template.plain() );

		// The following lines check if the HTML to be created is actually valid. If not, an error
		// is thrown making the developer aware of the conflict rather than passing anything broken

		// html element automatically creates a body tag for certain elements that fit right into
		// the body - not for tags like <tr>
		parsedTemplate = $wrappedTemplate.html();
		if ( $wrappedTemplate.children( 'body').length ) {
			parsedTemplate = $wrappedTemplate.children( 'body').html();
		}

		/**
		 * Helper function to strip HTML tags that may be generated automatically like <tbody>
		 * as well as all node attributes.
		 *
		 * @param {String} string
		 * @return {String}
		 */
		function strip( string ) {
			var tagsToIgnore = [ 't(?:head|body|foot)' ];
			$.each( tagsToIgnore, function( i, tag ) {
				var re = new RegExp( '<\\/?' + tag + '[^>]*>', 'g' );
				string = string.replace( re, '' );
			} );

			// strip white space between tags as well since it might cause interference
			string = string.replace( />\s+</g, '><');

			// Strip all attributes since they are not necessary for validating the HTML and would
			// cause interference in Firefox which re-converts &lt; and &gt; back to < and > when
			// parsing by setting through $.html().
			// Additionally, rip off any XML notation since jQuery will parse to HTML.
			string = string.replace( /(<\S+)(?:[^<>"']+(?:(["'])[^\2]*\2)?)*?\/?(>)/ig, '$1$3' );

			return string;
		}
		strippedTemplate = strip( template.plain() );
		strippedParsedTemplate = strip( parsedTemplate );

		// nodes or text got lost while being parsed which indicates that the generated HTML would
		// be invalid
		if ( strippedTemplate !== strippedParsedTemplate ) {
			throw new Error( 'mw.template: Tried to generate invalid HTML for template "' + key + '"' );
		}

		// replace temporary nodes with actual jQuery nodes
		$wrappedTemplate.find( '.--mwTemplate' ).each( function( i, node ) {
			$( this ).replaceWith( delayedParams[i] );
		} );

		if ( $wrappedTemplate.children( 'body' ).length ) {
			return $wrappedTemplate.children( 'body' ).contents();
		} else {
			return $wrappedTemplate.contents();
		}
	};

	/**
	 * Fetches a template and fills it with specified parameters. The template has to have a single
	 * root DOM element. All of its child nodes will then be appended to the jQuery object's DOM
	 * nodes.
	 * @see mw.template
	 *
	 * @since 0.3
	 *
	 * @param {String} template
	 * @param {String|jQuery|Array} parameter_1 First argument in a list of variadic arguments, each
	 *        a parameter for $N replacement in templates. Instead of making use of variadic
	 *        arguments, an array may be passed as first parameter.
	 * @return {jQuery}
	 */
	$.fn.applyTemplate = function( template, parameter_1 /* [, parameter_2] */ ) {
		var $template = mw.template.apply( null, arguments );

		if( $template.length !== 1 ) {
			throw new Error( 'Can not apply a template with more or less than one root node.' );
		}

		// copy template's root node children and classes to given root
		this.addClass( $template.prop( 'class' ) );

		// copy dir attribute if set
		if ( $template.prop( 'dir' ) !== '' ) {
			this.prop( 'dir', $template.prop( 'dir' ) );
		}

		$template.children().appendTo( this.empty() );

		return this;
	};

}( mediaWiki, jQuery ) );
