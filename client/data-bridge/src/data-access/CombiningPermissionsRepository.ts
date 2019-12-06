import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import PageEditPermissionErrorsRepository, {
	PermissionError,
	PermissionErrorType,
	PermissionErrorUnknown,
} from '@/definitions/data-access/PageEditPermissionErrorsRepository';
import {
	MissingPermissionsError,
	PageEditPermissionsRepository,
	PageNotEditable,
	UnknownReason,
} from '@/definitions/data-access/PageEditPermissionsRepository';

export default class CombiningPermissionsRepository implements PageEditPermissionsRepository {

	private readonly repoRepository: PageEditPermissionErrorsRepository;
	private readonly repoPageTitle: string;
	private readonly clientRepository: PageEditPermissionErrorsRepository;
	private readonly clientPageTitle: string;

	public constructor(
		repoRepository: PageEditPermissionErrorsRepository,
		repoPageTitle: string,
		clientRepository: PageEditPermissionErrorsRepository,
		clientPageTitle: string,
	) {
		this.repoRepository = repoRepository;
		this.repoPageTitle = repoPageTitle;
		this.clientRepository = clientRepository;
		this.clientPageTitle = clientPageTitle;
	}

	public async isUserAllowedToEditPage(): Promise<MissingPermissionsError[]> {
		const [ repoErrors, clientErrors ] = await Promise.all( [
			this.repoRepository.getPermissionErrors( this.repoPageTitle ),
			this.clientRepository.getPermissionErrors( this.clientPageTitle ),
		] );
		const errors: MissingPermissionsError[] = [];

		errors.push( ...repoErrors.map( this.repoErrorToMissingPermissionsError, this ) );
		errors.push( ...clientErrors.map( this.clientErrorToMissingPermissionsError, this ) );

		return errors;
	}

	private repoErrorToMissingPermissionsError( repoError: PermissionError ): MissingPermissionsError {
		switch ( repoError.type ) {
			case PermissionErrorType.PROTECTED_PAGE:
				return {
					type: repoError.semiProtected ?
						PageNotEditable.ITEM_SEMI_PROTECTED :
						PageNotEditable.ITEM_FULLY_PROTECTED,
					info: {
						right: repoError.right,
					},
				};
			case PermissionErrorType.UNKNOWN:
				return this.unknownPermissionErrorToMissingPermissionsError( repoError );
		}
	}

	private clientErrorToMissingPermissionsError( clientError: PermissionError ): MissingPermissionsError {
		switch ( clientError.type ) {
			case PermissionErrorType.PROTECTED_PAGE:
				throw new TechnicalProblem( 'Data Bridge should never have been opened on this protected page!' );
			case PermissionErrorType.UNKNOWN:
				return this.unknownPermissionErrorToMissingPermissionsError( clientError );
		}
	}

	private unknownPermissionErrorToMissingPermissionsError( error: PermissionErrorUnknown ): UnknownReason {
		return {
			type: PageNotEditable.UNKNOWN,
			info: {
				code: error.code,
				messageKey: error.messageKey,
				messageParams: error.messageParams,
			},
		};
	}
}
