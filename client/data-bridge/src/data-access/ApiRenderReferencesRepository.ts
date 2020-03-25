import Api, { ApiResponse } from '@/definitions/data-access/Api';
import Reference from '@/datamodel/Reference';
import ReferencesRenderingRepository from '@/definitions/data-access/ReferencesRenderingRepository';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';

interface FormatReferenceResponse {
	wbformatreference: {
		html: string;
	};
}

export default class ApiRenderReferencesRepository implements ReferencesRenderingRepository {
	private readonly api: Api;
	private readonly language: string;

	public constructor( api: Api, language: string ) {
		this.api = api;
		this.language = language;
	}

	public getRenderedReferences( references: Reference[] ): Promise<string[]> {
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
