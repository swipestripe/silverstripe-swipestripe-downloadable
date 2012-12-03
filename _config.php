<?php
/**
 * Decorate core classes of SwipeStripe.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage virtualproduct
 * @version 1.0
 */
Object::add_extension('Product', 'Downloadable_Product');
Object::add_extension('Order', 'Downloadable_Order');
Object::add_extension('Item', 'Downloadable_Item');
Object::add_extension('AccountPage_Controller', 'Downloadable_AccountPage');
