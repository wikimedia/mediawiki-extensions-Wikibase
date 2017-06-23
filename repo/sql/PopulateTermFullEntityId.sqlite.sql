-- This script does not work for custom entity types registered by extensions (e.g. mediainfo)
-- Use rebuildTermSqlIndex maintenance script (with --no-deduplication option) for more
-- sophisticated populating of term_full_entity_id column, including handling custom
-- entity types, batching with continuation, etc.

UPDATE /*_*/wb_terms
SET term_full_entity_id = 'Q' || term_entity_id
WHERE term_entity_type='item';

UPDATE /*_*/wb_terms
SET term_full_entity_id = 'P' || term_entity_id
WHERE term_entity_type='property';