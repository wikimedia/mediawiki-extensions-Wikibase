import ApplicationError from '@/definitions/ApplicationError';

export default class SavingError extends Error {
	public readonly errors: readonly ApplicationError[];

	public constructor( errors: readonly ApplicationError[] ) {
		super( 'Saving failed.' );
		this.errors = errors;
	}
}
