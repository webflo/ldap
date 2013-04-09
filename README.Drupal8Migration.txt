
Steps:

1. Check in ldap 7.x-2.x-dev into drupal 8 branch for diffing
2. Run coder update module for automatic changes.
3. Examine all module hooks and see which have changed
4. Examine all other core api functions being used and see what has changed
5. Do conf yaml work.
6. Phase out any less than useful ldap functions such as ldap_servers_load_module




====================================
Post Migration Cleanup
Tokens: Consisent user and ldap entry tokens
Tokens: Leverage token module
