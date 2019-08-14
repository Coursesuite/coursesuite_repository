CourseSuite Repository
======================

This repository is basically a file system repository folder for scorm courses, but integrates with CourseSuite's scorm building tools through its API. These simple scorm course building tools are browser-based and can publish back to your moodle site via this plugin. Access to apps is via a paid subscription or licence, which enables the use of the API.

Demo
----

https://vimeo.com/353531370/59a8861fc8

Requirements
------------

Your moodle site really should be running https.
Your server needs to be able to pass HTTP_AUTHORIZATION headers to PHP.
You should be running at least Moodle 3.2 or higher but it should be fine with older versions too

Configuration
-------------

When enabling the repository you need to enter your apikey and secret key (which can be managed here: https://www.coursesuite.ninja). This will verify and cache an access token and set some other options. To reset this data you need to use the 'uninstall' link on the manage repository screen, then add the repository again. It won't work without an API key.

If you require headers to be passed, your site administrator should be able to add these configs to the relevant web server configuration files:

apache = SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1

nginx  = fastcgi_param HTTP_AUTHORIZATION $http_authorization;

Usage
-----

The repository really only makes sense when adding scorm packages. In the scorm package edit screen use the file picker and select the CourseSuite repository. A number of blue buttons will appear at the top (depending on the apps your licence supports). To launch the web app, click a button. The file listing at the bottom shows courses you have published back to this server using the "Publish to LMS" button inside the app. Once you are done, just close the overlay using the X in the top right and your package should appear in the list. Published packages can be selected and added in the normal way.

Deleting published packages
---------------------------
Since the Moodle repository plugins can't actually delete files you will have to use your server admin foo powers to delete the zip files out of the moodledata/coursesuite folder by hand. Some kind of magical admin interface to this may come in a future revision.

Licence
-------

http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
