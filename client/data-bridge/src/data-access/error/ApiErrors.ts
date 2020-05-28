import { ApiError } from '@/definitions/data-access/Api';

export default class ApiErrors extends Error {
	public readonly errors: readonly ApiError[];

	public constructor( errors: readonly ApiError[] ) {
		super( errors[ 0 ].code );
		this.errors = errors;
	}

}
