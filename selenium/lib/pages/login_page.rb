# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# base page object for MW login page

class LoginPage
  include PageObject

  text_field(:username, :id => 'wpName1')
  text_field(:password, :id => 'wpPassword1')
  button(:login, :id => 'wpLoginAttempt')
  link(:logout, :xpath => "//li[@id='pt-logout']/a")

  def login_with(username, password)
    self.username = username
    self.password = password
    login
  end

  def logout_user
    if logout?
      logout
    end
  end
end
