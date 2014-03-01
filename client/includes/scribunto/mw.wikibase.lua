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

	-- Caching variable for the wikibase.entity object belonging to the current page
	local entity = false

	local getEntityObject = function( id )
		local entity = php.getEntity( id, false )
		if type( entity ) ~= 'table' then
			return nil
		end

		return wikibase.entity.create( entity )
	end

	-- @DEPRECATED, uses a legacy plain Lua table holding the entity
	wikibase.getEntity = function()
		local id = php.getEntityId( tostring( mw.title.getCurrentTitle().prefixedText ) )

		if id == nil then
			return nil
		end

		return php.getEntity( id, true )
	end

	-- Get the mw.wikibase.entity object for the current page
	wikibase.getEntityObject = function()
		if entity ~= false then
			return entity
		end

		local id = php.getEntityId( tostring( mw.title.getCurrentTitle().prefixedText ) )

		if id == nil then
			entity = nil
		else
			entity = getEntityObject( id )
		end

		return entity
	end

	-- Get the label for the given entity id (in content language)
	--
	-- @param id
	wikibase.label = function( id )
		local entity = getEntityObject( id )

		if entity == nil then
			return nil
		end

		return entity:getLabel()
	end

	-- Get the local sitelink title for the given entity id (if one exists)
	--
	-- @param id
	wikibase.sitelink = function( id )
		local entity = getEntityObject( id )

		if entity == nil then
			return nil
		end

		return entity:getSitelink()
	end

	mw = mw or {}
	mw.wikibase = wikibase
	package.loaded['mw.wikibase'] = wikibase
	wikibase.setupInterface = nil
end

return wikibase
