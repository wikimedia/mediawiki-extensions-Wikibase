import { Reference } from '@wmde/wikibase-datamodel-types';
import { ApiResponse, ReadingApi } from '@/definitions/data-access/Api';
import ReferencesRenderingRepository from '@/definitions/data-access/ReferencesRenderingRepository';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';

interface FormatReferenceResponse {
	wbformatreference: {
		html: string;
	};
}

export default class ApiRenderReferencesRepository implements ReferencesRenderingRepository {
	private readonly api: ReadingApi;
	private readonly language: string;

	public constructor( api: ReadingApi, language: string ) {
		this.api = api;
		this.language = language;
	}

	public getRenderedReferences( references: readonly Reference[] ): Promise<string[]> {
		return Promise.all(
			references.map( ( reference ) => this.renderSingleReference( reference ) ),
		);
	}

	private async renderSingleReference( reference: Reference ): Promise<string> {
		const response = await this.api.get( {
			action: 'wbformatreference',
			reference: JSON.stringify( reference ),
			style: 'internal-data-bridge',
			outputformat: 'html',
			errorformat: 'raw',
			formatversion: 2,
			uselang: this.language,
		} );

		if ( !this.isWellFormedFormatReferenceResponse( response ) ) {
			throw new TechnicalProblem( 'Reference formatting server response not well formed.' );
		}

		return response.wbformatreference.html;
	}

	private isWellFormedFormatReferenceResponse( response: ApiResponse ): response is FormatReferenceResponse {
		return typeof ( response as FormatReferenceResponse )?.wbformatreference?.html === 'string';
	}
}
