import { ForeignApi } from '@/@types/mediawiki/MwWindow';
import WikibaseRepoConfigRepository, {
	WikibaseRepoConfiguration,
} from '@/definitions/data-access/WikibaseRepoConfigRepository';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';

interface WellFormedResponse {
	query: {
		wbdatabridgeconfig: WikibaseRepoConfiguration;
	};
}

export default class ForeignApiRepoConfigRepository implements WikibaseRepoConfigRepository {
	private readonly foreignApi: ForeignApi;

	public constructor( foreignApi: ForeignApi ) {
		this.foreignApi = foreignApi;
	}

	public getRepoConfiguration(): Promise<WikibaseRepoConfiguration> {
		return Promise.resolve( this.foreignApi.get( {
			action: 'query',
			meta: 'wbdatabridgeconfig',
			formatversion: 2,
			errorformat: 'none',
		} ) ).then( ( response: unknown ) => {
			if ( this.responseWarnsAboutDisabledRepoConfiguration( response ) ) {
				throw new TechnicalProblem( 'Result indicates repo API is disabled (see dataBridgeEnabled).' );
			}

			if ( !this.isWellFormedResponse( response ) ) {
				throw new TechnicalProblem( 'Result not well formed.' );
			}

			return response.query.wbdatabridgeconfig;
		}, ( error: JQuery.jqXHR ): never => {
			throw new JQueryTechnicalError( error );
		} );
	}

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	private responseWarnsAboutDisabledRepoConfiguration( response: any ): boolean {
		return Array.isArray( response.warnings ) &&
			response.warnings.some( ( warning: any ) => { // eslint-disable-line @typescript-eslint/no-explicit-any
				return warning.code === 'unrecognizedvalues' && warning.module === 'query';
			} );
	}

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	private isWellFormedResponse( response: any ): response is WellFormedResponse {
		try {
			return typeof response.query.wbdatabridgeconfig.dataTypeLimits.string.maxLength === 'number';
		} catch ( e ) {
			return false;
		}
	}

}
