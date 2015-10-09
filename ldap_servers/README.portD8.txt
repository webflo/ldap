

LDAP Server Module

- Diagnosis before upgrade in upgrade-info.html

==========================================
Porting module on D8
==========================================

Files upgraded:
X ldap_servers.info.yml
X ldap_servers.install
X ldap_servers.module
X ldap_servers.encryption.inc
X ldap_servers.functions.inc
X ldap_servers.settings.inc
X ldap_servers.test_form.inc

Files unchanged:
X ldap_servers.api.php
X ldap_servers.admin.css

Files created:
X ldap_servers.routing.yml
X ldap_servers.services.yml
X ldap_servers.links.menu.yml
X ldap_servers.links.task.yml


REMARKS
- ldap_servers.install: call ldap_user and ldap_authentication config so dependency ?

- ldap_servers.module: l() line 361 might be the wrong path
- ldap_servers.module: ctools_export_load_object_reset() line 622 need to find D8 replacement
- ldap_servers.module: _theme() line 821 doesn't seems to be the best D8 replacement solution

- ldap_servers.admin.inc: _theme() line 33, 132, 208 doesn't seems to be the best D8 replacement solution (@FIXME)

- ldap_servers.settings.inc: _theme() line 23 doesn't seems to be the best D8 replacement solution (@FIXME)

- ldap_servers.test_form.inc _theme() line 38, 111, 137, 309, 320, 347 doesn't seems to be the best D8 replacement solution (@FIXME)

config : encryption & ldap_servers_encryption are potentially the same and should be merged

TODO:
- https://www.drupal.org/files/ldap-upgrade-path-broken-1054616-06.patch
- remove: TO REMOVE notes
- Update config/settings to get rid of useless
- Update config/schema to get all variables in the mapping
- replace "ldap_servers_require_ssl_for_credentails" by "require_ssl_for_credentails"
- replace "ldap_servers_encryption" by "encryption"






