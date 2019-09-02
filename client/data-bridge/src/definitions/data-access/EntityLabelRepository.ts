import Term from '@/datamodel/Term';

/**
 * Repository to get the label of specific entity in a specific language.
 * If no label in this specific language is known, the language fallback
 * chain will be use to determine one - i.e. the Term's language does not
 * necessarily represent the actual language of that value in the backend.
 */
export default interface EntityLabelRepository {
	getLabel( id: string ): Promise<Term>;
}
