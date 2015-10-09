

LDAP Server Module

- Diagnosis before upgrade in upgrade-info.html

==========================================
Porting module on D8
==========================================

Files upgraded:
X ldap_servers.info.yml
X ldap_servers.install
X ldap_servers.module

Files unchanged:
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

TODO:
- https://www.drupal.org/files/ldap-upgrade-path-broken-1054616-06.patch
- remove: TO REMOVE notes