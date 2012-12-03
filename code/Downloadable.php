<?php
/**
 * Decorates {@link AccountPage} to allow for products to be downloaded.
 */
class Downloadable_AccountPage extends Extension {

	static $allowed_actions = array (
    'downloadProduct' => 'VIEW_ORDER'
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
	function downloadProduct(SS_HTTPRequest $request) {

	  $memberID = Member::currentUserID();
	  if (!Member::currentUserID()) {
      return Security::permissionFailure($this->owner, 'You must be logged in to view this page.');
    }
	  
	  //TODO can only download product if order has been paid for

	  $controller = Controller::curr();

	  $item = DataObject::get_by_id('Item', $request->requestVar('ItemID'));
	  if ($item && $item->exists()) {
	    
	    $product = $item->Product();
	    
	    if ($product->File() && $product->File()->exists()) {
	      
	      if ($downloadLocation = $product->downloadLocation()) {
    	    $item->DownloadCount = $item->DownloadCount + 1;
    	    $item->write();

    	    $controller->redirect($downloadLocation);
    	    return;
    	  }
	    }
	  }

	  //TODO set an error message
	  $controller->redirectBack();
	}
}

class Downloadable_Item extends DataExtension {

	static $db = array(
		'DownloadCount' => 'Int'
	);

	static $has_many = array(
		'LicenseKeys' => 'Downloadable_LicenseKey'
	);

	/**
	 * Return the link that should be used for downloading the 
	 * virtual product represented by this item.
	 * 
	 * @return Mixed URL to download or false
	 */
	function DownloadLink() {

	  if ($this->owner->DownloadCount < $this->getDownloadLimit()) {
	    
	    //If order is not paid do not provide access to download
	    $order = $this->owner->Order();
	    if (!$order->getPaid()) {
	      return false;
	    }
	  
  	  if ($accountPage = DataObject::get_one('AccountPage')) {
  	    return $accountPage->Link() . 'downloadproduct/?ItemID='.$this->owner->ID;
  	  }
  	  else {
  	    return false;
  	  }
	  }
	  else {
	    return false;
	  }
	}
	
	/**
	 * Number of times this item can be downloaded for this order
	 * 
	 * @return Int
	 */
	function getDownloadLimit() {
	  return Downloadable_Product::$downloadLimit * $this->owner->Quantity;
	}
	
	/**
	 * Calculate remaining number of downloads for this item
	 * 
	 * @return Int
	 */
	function RemainingDownloadLimit() {
	  return $this->getDownloadLimit() - $this->owner->DownloadCount;
	}
}

class Downloadable_LicenseKey extends DataObject {
  
  /**
   * Storing the LicenseKey
   * 
   * @var Array
   */
  public static $db = array(
    'LicenseKey' => 'Varchar',
    'Number' => 'Int'
  );
  
  /**
   * License keys are associated to {@link Order}s and {@link Item}s.
   * 
   * @var Array
   */
  public static $has_one = array(
    'Order' => 'Order',
    'Item' => 'Item'
  );

  /**
   * Generate unique license key before each write.
   * 
   * @see DataObject::onBeforeWrite()
   */
  function onBeforeWrite() {
    parent::onBeforeWrite();
    $this->LicenseKey = $this->generateLicenseKey();
  }
	
  /**
   * Generates a unique license key.
   * 
   * @return String
   */
	function generateLicenseKey() {
	  
    $keys = Downloadable_LicenseKey::get();
    $existingKeys = ($keys && $keys->exists()) ? $keys->map('ID', 'LicenseKey')->toArray() : array();
    $key = md5(date('y-m-d') . ' ' . rand (1, 9999999999));
    
    while (in_array($key, $existingKeys)) {
      $key = md5(date('y-m-d') . ' ' . rand (1, 9999999999));
    }
    return $key;
	}
}

class Downloadable_Order extends DataExtension {

	static $has_many = array(
		'LicenseKeys' => 'Downloadable_LicenseKey'
	);
  
  /**
   * Add fields for resetting download counts for virtual products.
   */
  function updateOrderCMSFields(FieldSet &$fields) {
    
    $downloads = $this->Downloads();
    if ($downloads && $downloads->exists()) {
      
      $fields->addFieldToTab('Root.Actions', new HeaderField('DownloadCount', 'Reset Download Counts', 3));
  		$fields->addFieldToTab('Root.Actions', new LiteralField(
  			'UpdateDownloadLimit', 
  			'<p>Reset the download count for items below, can be used to allow customers to download items more times.</p>'
  		));
  		foreach ($downloads as $item) {
  		  $fields->addFieldToTab('Root.Actions', new TextField(
  		  	'DownloadCountItem['.$item->ID.']', 
  		  	'Download Count for '.$item->Product()->Title.' (download limit = '.$item->getDownloadLimit() .')', 
  		    $item->DownloadCount
  		  ));
  		}
    }
    
    $fields->removeByName('LicenseKeys');
	}
	
	/**
	 * Reset download counts for items in an {@link Order}.
	 * 
	 * @see DataObjectDecorator::onBeforeWrite()
	 */
  function onBeforeWrite() {
    parent::onBeforeWrite();
    
    $curr = Controller::curr();
    
    if ($curr && $request = $curr->getRequest()) {
      $downloadCounts = $request->postVar('DownloadCountItem');
      
      if ($downloadCounts && is_array($downloadCounts)) foreach ($downloadCounts as $itemID => $newCount) {
        $item = DataObject::get_by_id('Item', $itemID);
  	    $item->DownloadCount = $newCount;
  	    $item->write();
      }
    }
	}

	/**
	 * Retrieving the downloadable virtual products for this order
	 * 
	 * @return ArrayList Items for this order that can be downloaded
	 */
	function Downloads() {
	  
	  //Get items for products with files attached
	  $virtualItems = new ArrayList();
	  $items = $this->owner->Items();

	  foreach ($items as $item) {
	    if ($item->Product()->File() && $item->Product()->File()->exists()) {
	      $virtualItems->push($item);
	    }
	  }
	  return $virtualItems;
	}
	
	/**
	 * Write license keys when {@link Order}s are processed.
	 */
	function onAfterPayment() {
	  
	  $keys = DataObject::get('Downloadable_LicenseKey', "\"Downloadable_LicenseKey\".\"OrderID\" = " . $this->owner->ID);

	  if (!$keys || !$keys->exists()) {
  	  $items = $this->owner->Items();
  	  if ($items && $items->exists()) foreach ($items as $item) {
  	    
  	    $product = $item->Product();
  	    if ($product && $product->File()->exists()) {
    	    $quantity = $item->Quantity;

    	    for ($i = 1; $i <= $quantity; $i++) {
    	      $licenseKey = new Downloadable_LicenseKey();
    	      $licenseKey->ItemID = $item->ID;
    	      $licenseKey->OrderID = $this->owner->ID;
    	      $licenseKey->Number = $i;
    	      $licenseKey->write();
    	    }
  	    }
  	  }
	  }
	}

}

class Downloadable_Product extends DataExtension {

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
   * Name of folder that generated files will be put into
   * 
   * @var String
   */
  public static $folderName = 'downloads';

  static $has_one = array(
		'File' => 'File'
	);
	
	/**
	 * Update the CMS with form fields for extra db fields above
	 * 
	 * @see DataObjectDecorator::updateCMSFields()
	 */
	function updateProductCMSFields(&$fields) {
		$uploadField = UploadField::create('File');
		$uploadField->setConfig('allowedMaxFileNumber', 1);
    $fields->addFieldToTab('Root.File', $uploadField);
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
	  $this->cleanup();
	  
	  //Get the file, copy to another location, return that new location of file
	  $file = $this->owner->File();
	  if ($file && $file->exists()) {
	    
	    $origin = $file->getFullPath();
	    $info = pathinfo($origin);
	    $newName = 'download_' . mt_rand(100000, 999999) .'_'. date('H-d-m-y') .'.'. $info['extension'];
	    $destination = $info['dirname'] .'/'. $newName;
	    
	    //Get relative path
	    $relOrigin = $file->getFilename();
	    $relInfo = pathinfo($relOrigin);
	    $relPath = $relInfo['dirname'] . '/' . $newName;

	    if (copy($origin, $destination)) {
        return Director::absoluteURL(Director::baseURL() . Director::makeRelative($relPath));
      }
	  }
	  return false;
	}
	
	/**
	 * Go through assets/ directory and cleanup download_* files older than download window.
	 * Could be used as a task/cron job.
	 */
	public function cleanup() {
	  
	  //go through assets/ dir and find download_* files
	  $dir = Director::baseFolder() . '/assets/';
	  $pattern = 'download_*';
	  $files = $this->find($dir, $pattern);

	  if ($files && is_array($files)) foreach ($files as $file) {
	    $filelastmodified = filemtime($file);

	    if($filelastmodified < strtotime('-'.self::$downloadWindow)) {
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
  function find($dir, $pattern){

    $dir = escapeshellcmd($dir);
    $files = shell_exec("find $dir -name '$pattern' -print");
    
    //Create array from the returned string (trim will strip the last newline)
    if ($files) $files = explode("\n", trim($files));
    
    return $files;
  }
}