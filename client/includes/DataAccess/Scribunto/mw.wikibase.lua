--[[
	Registers and defines functions to access Wikibase through the Scribunto extension
	Provides Lua setupInterface

	@since 0.4

	@licence GNU GPL v2+
	@author Jens Ohlig < jens.ohlig@wikimedia.de >
	@author Marius Hoch < hoo@online.de >
	@author Bene* < benestar.wikimedia@gmail.com >
]]

local wikibase = {}
local util = require 'libraryUtil'
local checkType = util.checkType
local checkTypeMulti = util.checkTypeMulti

local cacheSize = 15 -- Size of the LRU cache being used to cache entities
local cacheOrder = {}
local entityCache = {}

-- Cache a given entity (can also be false, in case it doesn't exist).
--
-- @param entityId
-- @param entity
local cacheEntity = function( entityId, entity )
	if #cacheOrder == cacheSize then
		local entityIdToRemove = table.remove( cacheOrder, cacheSize )
		entityCache[ entityIdToRemove ] = nil
	end

	table.insert( cacheOrder, 1, entityId )
	entityCache[ entityId ] = entity
end

-- Retrieve an entity. Will return false in case it's known to not exist
-- and nil in case of a cache miss.
--
-- @param entityId
local getCachedEntity = function( entityId )
	if entityCache[ entityId ] ~= nil then
		for cacheOrderId, cacheOrderEntityId in pairs( cacheOrder ) do
			if cacheOrderEntityId == entityId then
				table.remove( cacheOrder, cacheOrderId )
				break
			end
		end
		table.insert( cacheOrder, 1, entityId )
	end

	return entityCache[ entityId ]
end

function wikibase.setupInterface()
	local php = mw_interface
	mw_interface = nil

	-- Caching variable for the entity id string belonging to the current page (nil if page is not linked to an entity)
	local pageEntityId = false

	-- Get the entity id for the current page. Cached
	local getEntityIdForCurrentPage = function()
		if pageEntityId == false then
			pageEntityId = php.getEntityId( tostring( mw.title.getCurrentTitle().prefixedText ) )
		end

		return pageEntityId
	end

	-- Get the entity id of the connected item, if id is nil. Cached.
	local getIdOfConnectedItemIfNil = function( id )
		if id == nil then
			return getEntityIdForCurrentPage()
		end

		return id
	end

	-- Get the mw.wikibase.entity object for a given id. Cached.
	local getEntityObject = function( id )
		if getCachedEntity( id ) == nil then
			local entity = php.getEntity( id )

			if id ~= getEntityIdForCurrentPage() then
				-- Accessing an arbitrary item is supposed to increment the expensive function count
				php.incrementExpensiveFunctionCount()
			end

			if type( entity ) ~= 'table' then
				cacheEntity( id, false )
				return nil
			end

			cacheEntity( id, entity )
		end

		if type( getCachedEntity( id ) ) == 'table' then
			return wikibase.entity.create(
				mw.clone( getCachedEntity( id ) ) -- Use a clone here, so that people can't modify the entity
			)
		else
			return nil
		end
	end

	-- Get the mw.wikibase.entity object for the current page or for the
	-- specified id.
	--
	-- @param {string} [id]
	wikibase.getEntity = function( id )
		checkTypeMulti( 'getEntity', 1, id, { 'string', 'nil' } )

		id = getIdOfConnectedItemIfNil( id )

		if id == nil then
			return nil
		end

		if not php.getSetting( 'allowArbitraryDataAccess' ) and id ~= getEntityIdForCurrentPage() then
			error( 'Access to arbitrary items has been disabled.', 2 )
		end

		return getEntityObject( id )
	end

	-- getEntityObject is an alias for getEntity as these used to be different.
	wikibase.getEntityObject = wikibase.getEntity

	-- Get the label for the given entity id, if specified, or of the
	-- connected entity, if exists. (in content language)
	--
	-- @param {string} [id]
	wikibase.label = function( id )
		checkTypeMulti( 'label', 1, id, { 'string', 'nil' } )

		id = getIdOfConnectedItemIfNil( id )

		if id == nil then
			return nil
		end

		return php.getLabel( id )
	end

	-- Get the description for the given entity id, if specified, or of the
	-- connected entity, if exists. (in content language)
	--
	-- @param {string} [id]
	wikibase.description = function( id )
		checkTypeMulti( 'description', 1, id, { 'string', 'nil' } )

		id = getIdOfConnectedItemIfNil( id )

		if id == nil then
			return nil
		end

		return php.getDescription( id )
	end

	-- Get the local sitelink title for the given entity id.
	--
	-- @param {string} id
	wikibase.sitelink = function( id )
		checkType( 'sitelink', 1, id, 'string' )

		return php.getSiteLinkPageName( id )
	end


	-- Render a Snak from its serialization
	--
	-- @param {table} snakSerialization
	wikibase.renderSnak = function( snakSerialization )
		checkType( 'renderSnak', 1, snakSerialization, 'table' )

		return php.renderSnak( snakSerialization )
	end

	-- Render a list of Snaks from their serialization
	--
	-- @param {table} snaksSerialization
	wikibase.renderSnaks = function( snaksSerialization )
		checkType( 'renderSnaks', 1, snaksSerialization, 'table' )

		return php.renderSnaks( snaksSerialization )
	end

	-- Returns a property id for the given label or id
	--
	-- @param {string} propertyLabelOrId
	wikibase.resolvePropertyId = function( propertyLabelOrId )
		checkType( 'resolvePropertyId', 1, propertyLabelOrId, 'string' )

		return php.resolvePropertyId( propertyLabelOrId )
	end

	-- Returns a table of the given property IDs ordered
	--
	-- @param {table} propertyIds
	wikibase.orderProperties = function( propertyIds )
		checkType( 'orderProperties', 1, propertyIds, 'table' )
		return php.orderProperties( propertyIds )
	end

	-- Returns an ordered table of serialized property IDs
	wikibase.getPropertyOrder = function()
		return php.getPropertyOrder()
	end

	mw = mw or {}
	mw.wikibase = wikibase
	package.loaded['mw.wikibase'] = wikibase
	wikibase.setupInterface = nil
end

return wikibase
