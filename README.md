nxc_view_from_ga
================

Update views count from google analytics

INSTALLING:

1. Set user_name, password and profileID for your Google Analytics account in settings file, section [General] -> variable GAData.
2. Specify classes list which should be updated.
3. Specify AttributeIdentifier, where visits count should be storred. This identifier should have type Integer for correct fetches.
4. Install cronjob "php runcronjobs.php -s siteadmin ga_views".

5. You can also specify IncludeCountsPattern array in settings file which allow to combine visits.  
   For example IncludeCountsPattern[gallery]=\(offset\).* will set visits count for all urls like "/gallery/test-gallery", "/gallery/test-gallery/(offset)/0", "/gallery/test-gallery/(offset)/1" etc.