import { ApiError } from '@/definitions/data-access/Api';

export default class ApiErrors extends Error {
	private readonly errors: ApiError[];

	public constructor( errors: ApiError[] ) {
		super( errors[ 0 ].code );
		this.errors = errors;
	}

}
