import jqXHR = JQuery.jqXHR;

export default class JQueryTechnicalError extends Error {
	private error: jqXHR;

	public constructor( error: jqXHR ) {
		super( 'request error' );
		this.error = error;
	}
}
