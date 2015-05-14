--[[
	Registers and defines functions to access Wikibase through the Scribunto extension
	Provides Lua setupInterface

	@since 0.4

	@licence GNU GPL v2+
	@author Jens Ohlig < jens.ohlig@wikimedia.de >
	@author Marius Hoch < hoo@online.de >
]]

local wikibase = {}
local util = require 'libraryUtil'
local checkType = util.checkType
local checkTypeMulti = util.checkTypeMulti

function wikibase.setupInterface()
	local php = mw_interface
	mw_interface = nil

	-- Caching variable for the entity tables as obtained from PHP
	local entities = {}
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
			id = getEntityIdForCurrentPage()
		end

		return id
	end

	-- Get the mw.wikibase.entity object for a given id. Cached.
	local getEntityObject = function( id )
		if entities[ id ] == nil then
			local entity = php.getEntity( id )

			if id ~= getEntityIdForCurrentPage() then
				-- Accessing an arbitrary item is supposed to increment the expensive function count
				php.incrementExpensiveFunctionCount()
			end

			if type( entity ) ~= 'table' then
				entities[ id ] = false
				return nil
			end

			entities[ id ] = entity
		end

		if type( entities[ id ] ) == 'table' then
			return wikibase.entity.create(
				mw.clone( entities[ id ] ) -- Use a clone here, so that people can't modify the entity
			)
		else
			return nil
		end
	end

	-- Get the mw.wikibase.entity object for the current page
	wikibase.getEntity = function( id )
		id = getIdOfConnectedItemIfNil( id )
		checkTypeMulti( 'getEntity', 1, id, { 'string', 'nil' } )

		if not php.getSetting( 'allowArbitraryDataAccess' ) and id ~= getEntityIdForCurrentPage() then
			error( 'Access to arbitrary items has been disabled.', 2 )
		end

		if id == nil then
			return nil
		else
			return getEntityObject( id )
		end
	end

	-- getEntityObject is an alias for getEntity as these used to be different.
	wikibase.getEntityObject = wikibase.getEntity

	-- Get the label for the given entity id (in content language)
	--
	-- @param id
	wikibase.label = function( id )
		id = getIdOfConnectedItemIfNil( id )
		checkType( 'label', 1, id, 'string' )

		return php.getLabel( id )
	end

	-- Get the description for the given entity id (in content language)
	--
	-- @param id
	wikibase.description = function( id )
		id = getIdOfConnectedItemIfNil( id )
		checkType( 'description', 1, id, 'string' )

		return php.getDescription( id )
	end

	-- Get the local sitelink title for the given entity id (if one exists)
	--
	-- @param id
	wikibase.sitelink = function( id )
		id = getIdOfConnectedItemIfNil( id )
		checkType( 'sitelink', 1, id, 'string' )

		return php.getSiteLinkPageName( id )
	end


	-- Render a Snak from its serialization
	--
	-- @param snakSerialization
	wikibase.renderSnak = function( snakSerialization )
		checkType( 'renderSnak', 1, snakSerialization, 'table' )

		return php.renderSnak( snakSerialization )
	end

	-- Render a list of Snaks from their serialization
	--
	-- @param snaksSerialization
	wikibase.renderSnaks = function( snaksSerialization )
		checkType( 'renderSnaks', 1, snaksSerialization, 'table' )

		return php.renderSnaks( snaksSerialization )
	end

	mw = mw or {}
	mw.wikibase = wikibase
	package.loaded['mw.wikibase'] = wikibase
	wikibase.setupInterface = nil
end

return wikibase
