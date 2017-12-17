# mail-admin
Web-based administration tool for e-mail address database used by postfix/dovecot as a data source. This application, written in PHP,
provides a graphical user-interface for a mail-server based on postfix and dovecot which uses a MySQL database as a datasource for
users, managed domains and aliases. The application currently assumes a mail-server setup according to the tutorial at [thomas-leister.de](https://legacy.thomas-leister.de/mailserver-ubuntu-server-dovecot-postfix-mysql/).

# Requirements
* PHP 7.1
* a MySQL database
* a web-server

I highly recommend to use some kind of authentication and authorization in order to access this application, as a it would allow you to directly
access the datasource used by your mailserver! Basically, you can use http basic auth of your webserver and any kind of authentication provided
by the [Symfony framework](https://symfony.com/doc/current/security.html).

# Installation
1. Clone the repository somewhere
2. Setup the database connection (by setting the environment variable `DATABASE_URL` to some kind of database connection string, e.g.: `mysql://db_user:db_password@127.0.0.1:3306/db_name`)
3. Setup your webserver to point to the location where you've put mail-admin

# Authentication
To use an Ldap server for authenticating users using basic auth, you can use these snippets as a starting point. Each user in the LDAP, which is a member of the `EMailAdministrator` group would be able to access `mail-admin` in this case.

config/packages/security.yaml:
````
security:
    providers:
        my_ldap:
            ldap:
                service: Symfony\Component\Ldap\Ldap
                base_dn: OU=users,DC=example,DC=com
                search_dn: CN=read-only-user,OU=users,DC=example,DC=com
                search_password: YourVeryStrongPassword
                default_roles: ROLE_USER
                uid_key: uid
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        main:
            http_basic_ldap:
                service: Symfony\Component\Ldap\Ldap
                dn_string: dc=example,dc=com
                query_string: (&(cn={username})(memberOf=cn=EMailAdministrator,ou=groups,dc=example,dc=com))

    access_control:
        - { path: ^/, roles: ROLE_USER }
````

config/services.yaml:
````
...

    Symfony\Component\Ldap\Ldap:
        arguments: ['@Symfony\Component\Ldap\Adapter\ExtLdap\Adapter']
    Symfony\Component\Ldap\Adapter\ExtLdap\Adapter:
        arguments:
            -   host: localhost
                port: 389
                options:
                    protocol_version: 3
                    referrals: false
````
