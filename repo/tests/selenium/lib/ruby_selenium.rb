# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# Base class for all page objects

require 'net/http'
require 'uri'
require 'json'
require 'yaml'

configs = YAML::load( File.open( 'configuration.yml' ) )
configs.each do |k,v|
  eval("#{k} = '#{v}'")
end

class RubySelenium

end
