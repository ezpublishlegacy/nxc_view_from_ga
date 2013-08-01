nxc_view_from_ga
================

Update views count from google analytics

INSTALLING:

1. Create "Service account" at https://code.google.com/apis/console and enable "Analytics API" service, after creating account you need to download and include private key file.
1a. Add "service_email" as new user in you google analytics account.
2. Set client_id, service_email, path_to_key and profile_id for your Google Analytics account in settings file, section [General] -> variable GAData.
3. Specify classes and parent nodes list which should be updated.
4. Specify AttributeIdentifier, where visits count should be storred. This identifier should have type Integer for correct fetches.
5. Install cronjob "php runcronjobs.php -s siteadmin ga_views".

6. You can also specify IncludeCountsPattern array in settings file which allow to combine visits.  
   For example IncludeCountsPattern[gallery]=\(offset\).* will set visits count for all urls like "/gallery/test-gallery", "/gallery/test-gallery/(offset)/0", "/gallery/test-gallery/(offset)/1" etc.