--[[
	Registers and defines functions to handle Wikibase Entities through the Scribunto extension.

	@since 0.5

	@license GNU GPL v2+
	@author Marius Hoch < hoo@online.de >
]]

local php = mw_interface
local entity = {}
local metatable = {}
local methodtable = {}

metatable.__index = methodtable

local function verifyStringNumNil( val, name )
	if val ~= nil and type( val ) ~= 'string' and type( val ) ~= 'number' then
		error( name .. ' must be either of type string, number or nil' )
	end
end

-- Claim ranks (Claim::RANK_* in PHP)
entity.claimRanks = {
	RANK_TRUTH = 3,
	RANK_PREFERRED = 2,
	RANK_NORMAL = 1,
	RANK_DEPRECATED = 0
}

-- Create new entity object from given data
--
-- @param data
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
-- @param langCode
methodtable.getLabel = function( entity, langCode )
	verifyStringNumNil( langCode, 'langCode' )

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
-- @param globalSiteId
methodtable.getSitelink = function( entity, globalSiteId )
	verifyStringNumNil( globalSiteId, 'globalSiteId' )

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
-- @param propertyId
-- @param acceptableRanks
methodtable.formatPropertyValues = function( entity, propertyId, acceptableRanks )
	acceptableRanks = acceptableRanks or nil

	local formatted = php.formatPropertyValues(
		entity.id,
		propertyId,
		acceptableRanks
	)

	local label = mw.wikibase.label( propertyId )
	if label == nil then
		-- Make the label fallback on the entity id for convenience/ consistency
		label = entity.id
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
