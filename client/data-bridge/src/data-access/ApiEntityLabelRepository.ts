import {
	convertNoSuchEntityError,
	getApiEntity,
} from '@/data-access/ApiWbgetentities';
import { ReadingApi } from '@/definitions/data-access/Api';
import { EntityWithLabels } from '@/definitions/data-access/ApiWbgetentities';
import EntityLabelRepository from '@/definitions/data-access/EntityLabelRepository';
import Term from '@/datamodel/Term';
import EntityWithoutLabelInLanguageException from '@/data-access/error/EntityWithoutLabelInLanguageException';

export default class ApiEntityLabelRepository implements EntityLabelRepository {
	private readonly forLanguageCode: string;
	private readonly api: ReadingApi;

	public constructor( forLanguageCode: string, api: ReadingApi ) {
		this.forLanguageCode = forLanguageCode;
		this.api = api;
	}

	public async getLabel( entityId: string ): Promise<Term> {
		const response = await this.api.get( {
			action: 'wbgetentities',
			props: new Set( [ 'labels' ] ),
			ids: new Set( [ entityId ] ),
			languages: new Set( [ this.forLanguageCode ] ),
			languagefallback: true,
			errorformat: 'raw',
			formatversion: 2,
		} ).catch( convertNoSuchEntityError );
		const entity = getApiEntity( response, entityId ) as EntityWithLabels;

		if ( !( this.forLanguageCode in entity.labels ) ) {
			throw new EntityWithoutLabelInLanguageException(
				`Could not find label for language '${this.forLanguageCode}'.`,
			);
		}
		const label = entity.labels[ this.forLanguageCode ];

		return {
			value: label.value,
			language: label.language,
		};
	}
}
