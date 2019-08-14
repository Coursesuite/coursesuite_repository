CourseSuite Repository
======================

This repository allows you to build simple scorm courses in a browser-based tool. Access to apps is via a paid subscription or licence, which enables the use of the API.

Requirements
------------

Your moodle site really should be running https.
Your server needs to be able to pass HTTP_AUTHORIZATION headers to PHP.

Configuration
-------------

When enabling the repository you need to enter your apikey and secret key (which can be managed here: https://www.coursesuite.ninja). This will verify and cache an access token and set some other options. To reset this data you need to use the 'uninstall' link on the manage repository screen, then add the repository again.

If you require headers to be passed, your site administrator should be able to add these configs to the relevant web server configuration files:

apache = SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1

nginx  = fastcgi_param HTTP_AUTHORIZATION $http_authorization;

Usage
-----

The repository really only makes sense when adding scorm packages. In the scorm package edit screen use the file picker and select the CourseSuite repository. A number of blue buttons will appear at the top (depending on the apps your licence supports). To launch the web app, click a button. The file listing at the bottom shows courses you have published back to this server using the "Publish to LMS" button inside the app. Published packages can be selected in the normal way.

A usage video can be found here: https://vimeo.com/<not-found>

Licence
-------

http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
