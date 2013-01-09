<?php
Object::add_extension('Product', 'Downloadable_Product');
Object::add_extension('Item', 'Downloadable_Item');
Object::add_extension('Order', 'Downloadable_Order');
Object::add_extension('AccountPage_Controller', 'Downloadable_AccountPage');
Object::add_extension('File', 'Downloadabale_FileExtension');

File::$allowed_extensions = array(
	'','html','htm','xhtml','js','css',
	'bmp','png','gif','jpg','jpeg','ico','pcx','tif','tiff',
	'au','mid','midi','mpa','mp3','ogg','m4a','ra','wma','wav','cda',
	'avi','mpg','mpeg','asf','wmv','m4v','mov','mkv','mp4','swf','flv','ram','rm',
	'doc','docx','txt','rtf','xls','xlsx','pages',
	'ppt','pptx','pps','csv',
	'cab','arj','tar','zip','zipx','sit','sitx','gz','tgz','bz2','ace','arc','pkg','dmg','hqx','jar',
	'xml','pdf', 'dwn'
);