/**
 * Generate a lorem ipsum text to use in your story
 *
 * @param repetitions number Number of time the sentence should be repeated
 * @param wordBoundary string The character to use between words (e.g. if you want to prevent wrapping)
 * @return {string}
 */
export default function ( repetitions = 1, wordBoundary = ' ' ) {
	return Array( repetitions )
		.fill( 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.' ) // eslint-disable-line max-len
		.join( ' ' )
		.replace( / /g, wordBoundary );
}
