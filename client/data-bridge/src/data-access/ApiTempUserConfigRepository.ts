import { ReadingApi } from '@/definitions/data-access/Api';
import { ApiQueryResponseBody } from '@/definitions/data-access/ApiQuery';
import TempUserConfigRepository, {
	TempUserConfiguration,
} from '@/definitions/data-access/TempUserConfigRepository';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';

interface ApiQueryTempUserConfigBody extends ApiQueryResponseBody {
	autocreatetempuser: TempUserConfiguration;
}

export default class ApiTempUserConfigRepository implements TempUserConfigRepository {
	private readonly api: ReadingApi;

	public constructor( api: ReadingApi ) {
		this.api = api;
	}

	public async getTempUserConfiguration(): Promise<TempUserConfiguration> {
		const response = await this.api.get( {
			action: 'query',
			meta: new Set( [ 'siteinfo' ] ),
			siprop: new Set( [ 'autocreatetempuser' ] ),
			errorformat: 'raw',
			formatversion: 2,
		} );

		if ( !this.isWellFormedResponse( response.query ) ) {
			throw new TechnicalProblem( 'Result not well formed.' );
		}

		return response.query.autocreatetempuser;
	}

	private isWellFormedResponse( response: ApiQueryResponseBody ): response is ApiQueryTempUserConfigBody {

		const responseToAssert = response as ApiQueryTempUserConfigBody;

		if ( typeof responseToAssert.autocreatetempuser !== 'object' ) {
			return false;
		}

		if ( typeof responseToAssert.autocreatetempuser.enabled !== 'boolean' ) {
			return false;
		}

		return true;
	}

}
