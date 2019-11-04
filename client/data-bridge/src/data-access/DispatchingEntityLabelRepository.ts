import EntityLabelRepository from '@/definitions/data-access/EntityLabelRepository';
import Term from '@/datamodel/Term';
import EntityWithoutLabelInLanguageException from '@/data-access/error/EntityWithoutLabelInLanguageException';
import EntityInfoDispatcher from '@/definitions/data-access/EntityInfoDispatcher';

export default class DispatchingEntityLabelRepository implements EntityLabelRepository {
	private readonly forLanguageCode: string;
	private readonly requestDispatcher: EntityInfoDispatcher;

	public constructor( forLanguageCode: string, requestDispatcher: EntityInfoDispatcher ) {
		this.forLanguageCode = forLanguageCode;
		this.requestDispatcher = requestDispatcher;
	}

	public async getLabel( entityId: string ): Promise<Term> {
		const entities = await this.requestDispatcher.dispatchEntitiesInfoRequest( {
			props: [ 'labels' ],
			ids: [ entityId ],
			otherParams: {
				languages: this.forLanguageCode,
				languagefallback: 1,
			},
		} );

		const entity = entities[ entityId ];

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
