import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import {
	BridgePermissionsRepository,
	MissingPermissionsError,
	PageNotEditable,
	UnknownReason,
} from '@/definitions/data-access/BridgePermissionsRepository';
import PageEditPermissionErrorsRepository, {
	PermissionError,
	PermissionErrorType,
	PermissionErrorUnknown,
} from '@/definitions/data-access/PageEditPermissionErrorsRepository';

export default class CombiningPermissionsRepository implements BridgePermissionsRepository {

	private readonly repoRepository: PageEditPermissionErrorsRepository;
	private readonly clientRepository: PageEditPermissionErrorsRepository;

	public constructor(
		repoRepository: PageEditPermissionErrorsRepository,
		clientRepository: PageEditPermissionErrorsRepository,
	) {
		this.repoRepository = repoRepository;
		this.clientRepository = clientRepository;
	}

	public async canUseBridgeForItemAndPage(
		repoItemTitle: string,
		clientPageTitle: string,
	): Promise<MissingPermissionsError[]> {
		const [ repoErrors, clientErrors ] = await Promise.all( [
			this.repoRepository.getPermissionErrors( repoItemTitle ),
			this.clientRepository.getPermissionErrors( clientPageTitle ),
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
