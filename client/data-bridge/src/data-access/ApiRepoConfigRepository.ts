import Api, {
	ApiQueryResponseBody,
} from '@/definitions/data-access/Api';
import WikibaseRepoConfigRepository, {
	WikibaseRepoConfiguration,
} from '@/definitions/data-access/WikibaseRepoConfigRepository';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';

interface ApiQueryDataBridgeConfigBody extends ApiQueryResponseBody {
	wbdatabridgeconfig: WikibaseRepoConfiguration;
}

export default class ApiRepoConfigRepository implements WikibaseRepoConfigRepository {
	private readonly api: Api;

	public constructor( api: Api ) {
		this.api = api;
	}

	public async getRepoConfiguration(): Promise<WikibaseRepoConfiguration> {
		const response = await this.api.get( {
			action: 'query',
			meta: new Set( [ 'wbdatabridgeconfig' ] ),
			formatversion: 2,
			errorformat: 'raw',
		} );
		if ( this.responseWarnsAboutDisabledRepoConfiguration( response ) ) {
			throw new TechnicalProblem( 'Result indicates repo API is disabled (see dataBridgeEnabled).' );
		}

		if ( !this.isWellFormedResponse( response.query ) ) {
			throw new TechnicalProblem( 'Result not well formed.' );
		}

		return response.query.wbdatabridgeconfig;
	}

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	private responseWarnsAboutDisabledRepoConfiguration( response: any ): boolean {
		return Array.isArray( response.warnings ) &&
			response.warnings.some( ( warning: any ) => { // eslint-disable-line @typescript-eslint/no-explicit-any
				return warning.code === 'unrecognizedvalues' && warning.module === 'query';
			} );
	}

	private isWellFormedResponse( response: ApiQueryResponseBody ): response is ApiQueryDataBridgeConfigBody {
		try {
			return typeof ( response as ApiQueryDataBridgeConfigBody )
				.wbdatabridgeconfig.dataTypeLimits.string.maxLength === 'number';
		} catch ( e ) {
			return false;
		}
	}

}
