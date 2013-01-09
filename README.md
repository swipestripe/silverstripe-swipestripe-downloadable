Swipe Stripe Virtual Product Module
===================================

Copyright and license
---------------------
**Please note: This is NOT free software**

This software is free to try, but before it can be deployed to a live webserver a license must be
purchased.

[Licenses can be bought from here](http://swipestripe.com).

Maintainer Contact
------------------
SwipeStripe  
[Contact Us](http://swipestripe.com/support/contact-us)

Requirements
------------
* SilverStripe 3.*
* Swipe Stripe 2.*

Documentation
-------------
This extension to SwipeStripe supports selling virtual products online that are intended to be downloaded.

Installation Instructions
-------------------------
1. Place this directory in the root of your SilverStripe installation, rename the folder 'swipestripe-downloadable'.
2. Visit yoursite.com/dev/build?flush=1 to rebuild the database.

Usage Overview
--------------
1. Create a product in the CMS
2. Upload a file for that product
3. If the file is large, dump it in the assets/ folder and run /dev/tasks/FilesystemSyncTask and then select the file when editing the product.
