--[[
	Registers and defines functions to access Wikibase through the Scribunto extension
	Provides Lua setupInterface

	@since 0.4

	@licence GNU GPL v2+
	@author Jens Ohlig < jens.ohlig@wikimedia.de >
	@author Marius Hoch < hoo@online.de >
]]

local wikibase = {}

function wikibase.setupInterface()
	local php = mw_interface
	mw_interface = nil

	-- Caching variable for the wikibase.entity objects
	local entities = {}
	-- Caching variable for the entity id string belonging to the current page (nil if page is not linked to an entity)
	local pageEntityId = false

	-- Get the mw.wikibase.entity object for a given id. Cached.
	local getEntityObject = function( id )
		if entities[ id ] == nil then
			local entity = php.getEntity( id, false )

			if type( entity ) ~= 'table' then
				entities[ id ] = false
				return nil
			end

			entities[ id ] = wikibase.entity.create( entity )
		end

		if type( entities[ id ] ) == 'table' then
			return entities[ id ]
		else
			return nil
		end
	end

	-- Get the entity id for the current page. Cached
	local getEntityIdForCurrentPage = function()
		if pageEntityId == false then
			pageEntityId = php.getEntityId( tostring( mw.title.getCurrentTitle().prefixedText ) )
		end

		return pageEntityId
	end

	-- @DEPRECATED, uses a legacy plain Lua table holding the entity
	wikibase.getEntity = function()
		local id = getEntityIdForCurrentPage()

		if id == nil then
			return nil
		end

		return php.getEntity( id, true )
	end

	-- Get the mw.wikibase.entity object for the current page
	wikibase.getEntityObject = function( id )
		if id ~= nil and type( id ) ~= 'string' then
			error( 'id must be either of type string or nil, ' .. type( id ) .. ' given', 2 )
		end

		if id == nil then
			id = getEntityIdForCurrentPage()
		end

		if not php.getSetting( 'allowArbitraryDataAccess' ) and id ~= getEntityIdForCurrentPage() then
			error( 'Access to arbitrary items has been disabled.', 2 )
		end

		if id == nil then
			return nil
		else
			return getEntityObject( id )
		end
	end

	-- Get the label for the given entity id (in content language)
	--
	-- @param id
	wikibase.label = function( id )
		if type( id ) ~= 'string' then
			error( 'id must be of type string, ' .. type( id ) .. ' given', 2 )
		end

		return php.getLabel( id )
	end

	-- Get the local sitelink title for the given entity id (if one exists)
	--
	-- @param id
	wikibase.sitelink = function( id )
		if type( id ) ~= 'string' then
			error( 'id must be of type string, ' .. type( id ) .. ' given', 2 )
		end

		return php.getSiteLinkPageName( id )
	end

	mw = mw or {}
	mw.wikibase = wikibase
	package.loaded['mw.wikibase'] = wikibase
	wikibase.setupInterface = nil
end

return wikibase
