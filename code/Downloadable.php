<?php

class Downloadable_Product extends DataExtension {


	public static $many_many = array(
    'Files' => 'File'
  );
	
	/**
	 * Update the CMS with form fields for extra db fields
	 * 
	 * @see DataObjectDecorator::updateCMSFields()
	 */
	function updateProductCMSFields(&$fields) {
  	$fields->addFieldToTab('Root.Files', UploadField::create('Files', '')
  		->setFolderName(Downloadable_Item::$uploadFolder)
  	);
	}
}

class Downloadabale_FileExtension extends DataExtension {

	public static $belongs_many_many = array(
    'ProductPage' => 'Page'
  );
}

class Downloadable_Item extends DataExtension {

	/**
   * Number of times the product can be downloaded
   * 
   * @var Int
   */
  public static $downloadLimit = 3;
  
  /**
   * Window of time product can be downloaded
   * Should be a relative unit (http://nz.php.net/manual/en/datetime.formats.relative.php)
   * 
   * @var String
   */
  public static $downloadWindow = '1 day';
  
  /**
   * Name of folder that generated files will be put into for public download
   * 
   * @var String
   */
  public static $downloadFolder = 'Downloads';

  public static $productFolder = 'Products';

  public static $uploadFolder = 'Uploads';

	static $has_many = array(
		'Files' => 'Downloadable_File'
	);

	function createFiles() {

		$files = $this->owner->Product()->Files();

		if ($files && $files->exists()) foreach ($files as $file) {

			$parentFolder = Folder::find_or_make(self::$productFolder);

			$origin = $file->getFullPath();
	    $destination = $parentFolder->getFullPath() . $file->Name . '.dwn';

	    if (copy($origin, $destination)) {

        $downloadable = new Downloadable_File();
        $downloadable->ParentID = $parentFolder ? $parentFolder->ID : 0;
        $downloadable->Name = $file->Name . '.dwn';
        $downloadable->Ext = pathinfo($file->Name, PATHINFO_EXTENSION);
        $downloadable->Title = $file->Title;
        $downloadable->FileName = $parentFolder->getRelativePath() . $file->Name . '.dwn';
        $downloadable->ItemID = $this->owner->ID;
        $downloadable->DownloadLimit = self::$downloadLimit * $this->owner->Quantity;
        $downloadable->write();
      }
		}
	}
}

class Downloadable_File extends File {

	static $db = array (
    'LicenceKey' => 'Text',
    'DownloadCount' => 'Int',
    'DownloadLimit' => 'Int',
    'Ext' => 'Varchar'
  );

	static $has_one = array (
    'Item' => 'Item'
  );

  static $defaults = array(
  	'DownloadCount' => 0
  );

  /**
   * Generate unique license key before each write.
   * 
   * @see DataObject::onBeforeWrite()
   */
  function onBeforeWrite() {
    parent::onBeforeWrite();

    if (!$this->isInDB()) {
    	$this->LicenceKey = $this->generateLicenceKey();
    }
  }
	
  /**
   * Generates a unique license key.
   * 
   * @return String
   */
	function generateLicenceKey() {
	  
    $files = Downloadable_File::get();

    $existingKeys = $files->column('LicenceKey');

    // $existingKeys = ($keys && $keys->exists()) ? $keys->map('ID', 'LicenceKey')->toArray() : array();
    $key = md5(date('y-m-d') . ' ' . rand (1, 9999999999));
    
    while (in_array($key, $existingKeys)) {
      $key = md5(date('y-m-d') . ' ' . rand (1, 9999999999));
    }
    return $key;
	}

  function DownloadLink() {

	  if ($this->DownloadCount < $this->DownloadLimit) {
	    
	    //If order is not paid do not provide access to download
	    $order = $this->Item()->Order();

	    if (!$order->getPaid()) {
	      return false;
	    }
	  
  	  if ($accountPage = DataObject::get_one('AccountPage')) {
  	    return $accountPage->Link() . 'download/?OrderID=' . $order->ID . '&FileID=' . $this->ID;
  	  }
  	  else {
  	    return false;
  	  }
	  }
	  else {
	    return false;
	  }
	}

	function RemainingDownloadLimit() {
	  $remaining = $this->DownloadLimit - $this->DownloadCount;
	  if ($remaining < 0) $remaining = 0;
	  return $remaining;
	}

	/**
	 * Copy the downloadable file to another location on the server and
	 * redirect browser to that location.
	 * 
	 * Files are removed from new location after a certain amount of time.
	 * 
	 * @see VirutalProductDecorator::downloadFolder
	 * @see VirtualProductCleanupTask
	 */
	function downloadLocation() {

	  //Quick cleanup of old files
	  $this->cleanupFiles();
	  
	  //Get the file, copy to another location, return that new location of file
	  $origin = $this->getFullPath();

	  $folderPath = ASSETS_PATH . "/" . Downloadable_Item::$downloadFolder;
		if(!file_exists($folderPath)){
			mkdir($folderPath, Filesystem::$folder_create_mask);
		}

    $destination = $folderPath .'/'. 'downloadable_' . mt_rand(100000, 999999) .'_'. date('H-d-m-y') .'.'. $this->Ext;

    if (copy($origin, $destination)) {
      return Director::absoluteURL(Director::baseURL() . Director::makeRelative($destination));
    }
	  return false;
	}
	
	/**
	 * Go through assets/ directory and cleanup downloadable_* files older than download window.
	 * Could be used as a task/cron job.
	 */
	public function cleanupFiles() {
	  
	  //go through assets/ dir and find downloadable_* files

	  $dir = ASSETS_PATH . "/";
	  $pattern = 'downloadable_*';
	  $files = $this->findFile($dir, $pattern);

	  if ($files && is_array($files)) foreach ($files as $file) {
	    $filelastmodified = filemtime($file);

	    if($filelastmodified < strtotime('-'.Downloadable_Item::$downloadWindow)) {
        unlink($file);
      }
	  }
	  return;
	}
	
  /**
   * Find files matching a pattern using unix "find" command.
   * Won't work on Windows unfortunately.
   * 
   * @author http://www.redips.net/php/find-files-with-php/
   * @param string $dir     - directory to start with
   * @param string $pattern - pattern to search
   * @return array containing all pattern-matched files
   */
  function findFile($dir, $pattern){

    $dir = escapeshellcmd($dir);
    $files = shell_exec("find $dir -name '$pattern' -print");
    
    //Create array from the returned string (trim will strip the last newline)
    if ($files) $files = explode("\n", trim($files));
    
    return $files;
  }

}

class Downloadable_Order extends DataExtension {

	function onAfterPayment() {

		$items = $this->owner->Items();
		if ($items && $items->exists()) foreach ($items as $item) {
			$item->createFiles();
		}
	}

	function Downloads() {

		//Get items for products with files attached
	  $downloads = new ArrayList();

	  $items = $this->owner->Items();
		if ($items && $items->exists()) foreach ($items as $item) {

	  	$files = $item->Files();
	  	if ($files->exists()) foreach ($files as $file) {
	  		$downloads->push($file);
	  	}
	  }
	  return $downloads;
	}

}

class Downloadable_AccountPage extends Extension {

	static $allowed_actions = array (
    'download' => 'VIEW_ORDER'
  );

	/**
	 * Redirect browser to the download location, increment number of times
	 * this item has been downloaded.
	 * 
	 * If the item has been downloaded too many times redirects back with 
	 * error message.
	 * 
	 * @param SS_HTTPRequest $request
	 */
	function download(SS_HTTPRequest $request) {

		$controller = Controller::curr();
	  $memberID = Member::currentUserID();

	  if (!$memberID) {
      return Security::permissionFailure($this->owner, 'You must be logged in to view this page.');
    }
	  
	  //Can only download if order has been paid for
	  $order = Order::get()
	  	->where("\"ID\" = " . $request->requestVar('OrderID'))
	  	->first();

	  if (!$order || !$order->exists() || !$order->getPaid() || $order->MemberID != $memberID) {
	  	return Security::permissionFailure($this->owner, 'You do not have access to download this file.');
	  }

	  $file = Downloadable_File::get()
	  	->where("\"File\".\"ID\" = " . $request->requestVar('FileID'))
	  	->first();

	  if ($file && $file->exists()) {

	  	if ($downloadLocation = $file->downloadLocation()) {

  	    $file->DownloadCount = $file->DownloadCount + 1;
  	    $file->write();

  	    $controller->redirect($downloadLocation);
  	    return;
  	  }
	  }

	  $controller->redirectBack();
	}
}

