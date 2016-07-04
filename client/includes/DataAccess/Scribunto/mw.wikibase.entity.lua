--[[
	Registers and defines functions to handle Wikibase Entities through the Scribunto extension.

	@since 0.5

	@license GNU GPL v2+
	@author Marius Hoch < hoo@online.de >
	@author Bene* < benestar.wikimedia@gmail.com >
]]

local php = mw_interface
local entity = {}
local metatable = {}
local methodtable = {}
local util = require 'libraryUtil'
local checkType = util.checkType
local checkTypeMulti = util.checkTypeMulti

metatable.__index = methodtable

-- Claim ranks (Claim::RANK_* in PHP)
entity.claimRanks = {
	RANK_TRUTH = 3,
	RANK_PREFERRED = 2,
	RANK_NORMAL = 1,
	RANK_DEPRECATED = 0
}

-- Create new entity object from given data
--
-- @param {table} data
entity.create = function( data )
	if type( data ) ~= 'table' or type( data.schemaVersion ) ~= 'number' then
		error( 'The entity data must be a table obtained via mw.wikibase.getEntityObject' )
	end

	if data.schemaVersion < 2 then
		error( 'mw.wikibase.entity must not be constructed using legacy data' )
	end

	local entity = data
	setmetatable( entity, metatable )

	return entity
end

-- Get the label for a given language code
--
-- @param {string|number} [langCode]
methodtable.getLabel = function( entity, langCode )
	checkTypeMulti( 'getLabel', 1, langCode, { 'string', 'number', 'nil' } )

	langCode = langCode or mw.language.getContentLanguage():getCode()

	if langCode == nil then
		return nil
	end

	if entity.labels == nil then
		return nil
	end

	local label = entity.labels[langCode]

	if label == nil then
		return nil
	end

	return label.value
end

-- Get the sitelink title linking to the given site id
--
-- @param {string|number} [globalSiteId]
methodtable.getSitelink = function( entity, globalSiteId )
	checkTypeMulti( 'getSitelink', 1, globalSiteId, { 'string', 'number', 'nil' } )

	if entity.sitelinks == nil then
		return nil
	end

	globalSiteId = globalSiteId or php.getGlobalSiteId()

	if globalSiteId == nil then
		return nil
	end

	local sitelink = entity.sitelinks[globalSiteId]

	if sitelink == nil then
		return nil
	end

	return sitelink.title
end

-- Get the best statements with the given property id
--
-- @param {string} propertyId
methodtable.getBestStatements = function( entity, propertyId )
	if entity.claims == nil or not entity.claims[propertyId] then
		return {}
	end

	local statements = {}
	local bestRank = 'normal'

	for k, statement in pairs( entity.claims[propertyId] ) do
		if statement.rank == bestRank then
			statements[#statements + 1] = statement
		elseif statement.rank == 'preferred' then
			statements = { statement }
			bestRank = 'preferred'
		end
	end

	return statements
end

-- Get a table with all property ids attached to the entity.
methodtable.getProperties = function( entity )
	if entity.claims == nil then
		return {}
	end

	-- Get the keys (property ids)
	local properties = {}

	local n = 0
	for k, v in pairs( entity.claims ) do
		n = n + 1
		properties[n] = k
	end

	return properties
end

-- Get the formatted value of the claims with the given property id
--
-- @param {string} propertyLabelOrId
-- @param {table} [acceptableRanks]
methodtable.formatPropertyValues = function( entity, propertyLabelOrId, acceptableRanks )
	checkType( 'formatPropertyValues', 1, propertyLabelOrId, 'string' )
	checkTypeMulti( 'formatPropertyValues', 2, acceptableRanks, { 'table', 'nil' } )

	acceptableRanks = acceptableRanks or nil

	local formatted = php.formatPropertyValues(
		entity.id,
		propertyLabelOrId,
		acceptableRanks
	)

	local label
	if propertyLabelOrId:match( '^P%d+$' ) then
		label = mw.wikibase.label( propertyLabelOrId )
	end

	if label == nil then
		-- Make the label fallback on the entity id for convenience/ consistency
		label = propertyLabelOrId
	end

	return {
		value = formatted,
		label = label
	}
end

mw.wikibase.entity = entity
package.loaded['mw.wikibase.entity'] = entity
mw_interface = nil

return entity
