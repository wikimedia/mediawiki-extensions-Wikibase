# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for login page

class LoginPage
  include PageObject

  page_url WIKI_URL + 'index.php?title=Special:UserLogin'
  text_field(:username, :id => 'wpName1')
  text_field(:password, :id => 'wpPassword1')
  button(:login, :id => 'wpLoginAttempt')

  def login_with(username, password)
    self.username = username
    self.password = password
    login
  end
end
