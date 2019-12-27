# Entity ID Counters

Entity IDs are created based on counters maintained by Wikibase in the @ref md_docs_sql_wb_id_counters table.
 - Some extensions also use this table, such as Lexeme.
 - Other extensions such as EntitySchema choose not to use the table (instead creating their own).

You can view the table spec at @ref md_docs_sql_wb_id_counters

### Code

The [IdGenerator] interface is generally used when interacting with this storage.
 - [SqlIdGenerator] is the oldest ID generator implementation that works for all DB types.
 - [UpsertSqlIdGenerator] is a newer 'better' generator that only works for MySql.

[IdGenerator]: @ref Wikibase::IdGenerator
[SqlIdGenerator]: @ref Wikibase::SqlIdGenerator
[UpsertSqlIdGenerator]: @ref Wikibase::Repo::Store::Sql::UpsertSqlIdGenerator
