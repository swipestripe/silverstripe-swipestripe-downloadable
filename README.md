# SwipeStripe Downloadable Module

## Maintainer Contact
SwipeStripe  
[Contact Us](http://swipestripe.com/support/contact-us)

## Requirements
* SilverStripe 3.1.*
* SwipeStripe 2.1.*

## Documentation
Add files to products that can be downloaded after an order is processed and paid for. 

The files are uploaded to assets/Uploads, when an order is placed files are created in the assets/Products/ directory with file extension .dwn so that they are not publicly accessible. 

At the time when files are downloaded by the customer temporary files are created in assets/Downloads/ with obfuscated names, these files are cleared after the customer has reached their download limit or extended the download window for the files.

## Installation Instructions
1. Place this directory in the root of your SilverStripe installation, rename the folder 'swipestripe-downloadable'.
2. Visit yoursite.com/dev/build?flush=1 to rebuild the database.
3. Update the AccountPage_order.ss template to include the downloads template e.g: <% include OrderDownloads %>

### Configuration
- The download limit is the number of times a customer can download a file, set to 3 by default.  
Downloadable_Item::$downloadLimit
- The download window is the amount of time a customer has to download a file, set to '1 day' by default.  
Downloadable_Item::$downloadWindow
- Various folders in /assets are used for file generation, these can be changed with statics:  
Downloadable_Item::$downloadFolder  
Downloadable_Item::$productFolder  
Downloadable_Item::$uploadFolder

## Usage Overview
1. Create a product in the CMS
2. Upload a file for that product
	- Save and publish the product after attaching the file in the admin
	- If the file is large, dump it in the assets/ folder and run /dev/tasks/FilesystemSyncTask and then select the file when editing the product.
3. When a customer purchases a product with a file attached they will have the option of downloading the file.

## License
	Copyright (c) 2011 - 2013, Frank Mullenger
	All rights reserved.

	Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

			* Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
			* Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the 
				documentation and/or other materials provided with the distribution.
			* Neither the name of SilverStripe nor the names of its contributors may be used to endorse or promote products derived from this software 
				without specific prior written permission.

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE 
	IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE 
	LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE 
	GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
	STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY 
	OF SUCH DAMAGE.
