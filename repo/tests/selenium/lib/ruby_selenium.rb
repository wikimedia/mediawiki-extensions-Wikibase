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
