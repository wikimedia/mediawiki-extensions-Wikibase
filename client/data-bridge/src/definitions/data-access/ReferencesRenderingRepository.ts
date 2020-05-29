import { Reference } from '@wmde/wikibase-datamodel-types';

/**
 * A repository to render reference JSON blobs into HTML strings
 */
export default interface ReferencesRenderingRepository {
	getRenderedReferences( references: readonly Reference[] ): Promise<string[]>;
}
