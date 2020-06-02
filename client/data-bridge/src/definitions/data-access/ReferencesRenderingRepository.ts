import Reference from '@/datamodel/Reference';

/**
 * A repository to render reference JSON blobs into HTML strings
 */
export default interface ReferencesRenderingRepository {
	getRenderedReferences( references: readonly Reference[] ): Promise<string[]>;
}
