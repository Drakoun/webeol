Dolibarr Module Template (aka My Module)
========================================

This is a full featured module template for Dolibarr.
It's a tool for module developpers to kickstart their project and give an hands-on sample of which features Dolibarr has to offer for module development.

If you're not a module developper you have no use for this.

Documentation
-------------

[Module tutorial](http://wiki.dolibarr.org/index.php/Module_development)

[Dolibarr development](http://wiki.dolibarr.org/index.php/Developer_documentation)

### Translations

Dolibarr uses [Transifex](http://transifex.com) to manage it's translations.

This template also contains a sample configuration for Transifex managed translations under the hidden .tx directory.

For more informations, see the [translator's documentation](http://wiki.dolibarr.org/index.php/Translator_documentation).

The Transifex project for this module is available at http://transifex.com/projects/p/dolibarr-module-template

Install
-------

- Make sure Dolibarr (>= 3.3.x) is already installed and configured on your server.

- In your Dolibarr installation directory, edit the htdocs/conf/conf.php file

- Find the following lines:

        //$=dolibarr_main_url_root_alt ...
        //$=dolibarr_main_document_root_alt ...

- Uncomment these lines (delete the leading "//") and assign a sensible value according to your Dolibarr installation

    For example :

    - UNIX:

            $dolibarr_main_url_root = 'http://localhost/Dolibarr/htdocs';
            $dolibarr_main_document_root = '/var/www/Dolibarr/htdocs';
            $dolibarr_main_url_root_alt = '/custom';
            $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';

    - Windows:

            $dolibarr_main_url_root = 'http://localhost/Dolibarr/htdocs';
            $dolibarr_main_document_root = 'C:/My Web Sites/Dolibarr/htdocs';
            $dolibarr_main_url_root_alt = '/custom';
            $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';

    For more information about the conf.php file take a look at the conf.php.example file.

*Note that for Dolibarr versions before 3.5, the $dolibarr\_main\_url\_root\_alt has to be an absolute path*

- Clone the repository in $dolibarr\_main\_document\_root\_alt/mymodule

*(You may have to create the custom directory first if it doesn't exist yet.)*

    git clone --recursive git@github.com:rdoursenaud/dolibarr-module-template.git mymodule

*The template now uses a git submodule to fetch the PHP Markdown library.*

If your git version is less than 1.6.5, the --recursive parameter won't work.

Please use this instead to fetch the latest version:

    git clone git@github.com:rdoursenaud/dolibarr-module-template.git mymodule
    cd mymodule
    git submodule update --init

- From your browser:

    - log in as a Dolibarr administrator

    - under "Setup" -> "Other setup", set "MAIN\_FEATURES\_LEVEL" to "2"

    - go to "Setup" -> "Modules"

    - the module is under one of the tabs

    - you should now be able to enable the new module and start coding ;)

Contributions
-------------

Feel free to contribute and report defects at <http://github.com/rdoursenaud/dolibarr-module-template>

Licenses
--------

### Main code

GPLv3 or (at your option) any later version.

See COPYING for more information.

### Other Licenses

Uses [Michel Fortin's PHP Markdown](http://michelf.ca/projets/php-markdown/) Licensed under BSD to display this README in the module's about page.
