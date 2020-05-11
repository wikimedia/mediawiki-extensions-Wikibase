import ApplicationError from '@/definitions/ApplicationError';

export default class SavingError extends Error {
	public readonly errors: ApplicationError[];

	public constructor( errors: ApplicationError[] ) {
		super( 'Saving failed.' );
		this.errors = errors;
	}
}
