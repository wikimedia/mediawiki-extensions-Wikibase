# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# bootstrap code and helper functions

$LOAD_PATH.unshift(File.dirname(__FILE__))
$LOAD_PATH.unshift(File.join(File.dirname(__FILE__), ',,', 'lib'))

require 'rspec'
require 'rspec/expectations'
require 'yaml'
require 'watir-webdriver'
require 'page-object'
require 'page-object/page_factory'
require 'require_all'

require_all 'lib/pages'
configs = YAML::load( File.open( 'configuration.yml' ) )
$target_browser = configs['target_browser']
RSpec.configure do |config|
  config.include PageObject::PageFactory
  config.before(:all) do
    case $target_browser
    when "firefox"
      @browser = Watir::Browser.new :firefox
    when "chrome"
      @browser = Watir::Browser.new :chrome
    when "ie"
      @browser = Watir::Browser.new :ie
    when "opera"
      @browser = Watir::Browser.new :opera
    when "safari"
      @browser = Watir::Browser.new :safari
    else
      @browser = Watir::Browser.new :firefox
    end
  end

  config.after(:all) do
    @browser.close
  end

end

def ajax_wait
  # TODO: forced sleep for chrome & opera ('cause jQuery.active not working) is not that nice => investigate other possibilities
  if ($target_browser == "chrome") || ($target_browser == "opera") || ($target_browser == "ie")
    sleep 1
  end
  while (script = @browser.execute_script("return jQuery.active")) == 1 do
    sleep(1.0/3)
  end
  return true
end

# creates a random string
def generate_random_string(length=8)
  chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ'
  string = ''
  length.times { string << chars[rand(chars.size)] }
  return string
end

