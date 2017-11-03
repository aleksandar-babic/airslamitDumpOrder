<?php
namespace Airslamit\DumpOrder\Model;

class CSVGenerator {

	protected $_order;
	protected $_filePath;

  /**
   * Will return array of CSV headers that are needed for Fishbowl order import.
   * More details at https://www.fishbowlinventory.com/w/files/csv/importGenericSC.html
   * @return string[]
   */
  private function getCSVHeaders()
  {
    $headers = "OrderNumber,OrderDate,OrderTax,OrderShipping,OrderHandling,OrderDiscount,OrderNote,OrderShipMethod,OrderCustomerPO,OrderQBClass,OrderLG,ShipFirstName,ShipLastName,ShipAddress1,ShipAddress2,ShipCity,ShipState,ShipZip,ShipCountry,ShipEmail,ShipPhone,ShipFax,BillFirstName,BillLastName,BillAddress1,BillAddress2,BillCity,BillState,BillZip,BillCountry,BillEmail,BillPhone,BillFax,ItemType,ItemNumber,ItemPrice,ItemQuantity,ItemUOM,ItemTaxable,ItemQBClass,ItemNote,KitItem,ShowItem,PaymentProcess,PaymentTotal,Payment Method,PaymentTransactionID,PaymentMisc,PaymentExpirationDate";
    return explode(",", $headers);
  }

  /**
   * Will return array of first 11 values for fields that start with Order* 
   * @return string[]
   */
  private function getOrderData()
  {
    $dataArray = [];
    $dataArray['orderNumber'] = $this->_order->getRealOrderId();
    $dataArray['orderDate'] = date("m/d/Y", strtotime($this->_order->getCreatedAt()));
    $dataArray['orderTax'] = "";
    $dataArray['orderShipping'] = "";
    $dataArray['orderHandling'] = "";
    $dataArray['orderDiscount'] = "";
    $dataArray['orderNote'] = "";
    $dataArray['orderShipMethod'] = "";
    $dataArray['orderCustomerPO'] = $dataArray['orderNumber'];
    $dataArray['orderQBClass'] = "";
    $dataArray['orderLG'] = "";
    return $dataArray;
  }

  /**
   * Will return array of values for shipping fields.
   * @return string[]
   */
  private function getShippingData()
  {
    $shippingAddressObj = $this->_order->getShippingAddress();
    $shippingArray = [];
    $shippingArray['shipFirstName'] = $shippingAddressObj->getFirstname();
    $shippingArray['shipLastName'] = $shippingAddressObj->getLastName();
    $shippingAddresses = $shippingAddressObj->getStreet();
    $shippingArray['shipAddress1'] = $shippingAddresses[0];
    if(count($shippingAddresses) > 1){
      $shippingArray['shipAddress2'] = $shippingAddresses[1];
    } else {
      $shippingArray['shipAddress2'] = "";
    }
    $shippingArray['shipCity'] = $shippingAddressObj->getCity();
    $shippingArray['shipState'] = $shippingAddressObj->getRegionCode();
    $shippingArray['shipZip'] = $shippingAddressObj->getPostcode();
    $shippingArray['shipCountry'] = $shippingAddressObj->getCountryId();
    $shippingArray['shipEmail'] = $shippingAddressObj->getEmail();
    $shippingArray['shipPhone'] = $shippingAddressObj->getTelephone();
    $shippingArray['shipFax'] = $shippingAddressObj->getFax();
    return $shippingArray;
  }

  /**
   * Will return array of values for billing fields.
   * @return string[]
   */
  private function getBillingData()
  {
    $billingAddressObj = $this->_order->getBillingAddress();
    $billingArray = [];
    $billingArray['billFirstName'] = $billingAddressObj->getFirstname();
    $billingArray['billLastName'] = $billingAddressObj->getLastName();
    $billingAddresses = $billingAddressObj->getStreet();
    $billingArray['billAddress1'] = $billingAddresses[0];
    if(count($billingAddresses) > 1) {
      $billingArray['billAddress2'] = $billingAddresses[1];
    } else {
      $billingArray['billAddress2'] = "";
    }
    $billingArray['billCity'] = $billingAddressObj->getCity();
    $billingArray['billState'] = $billingAddressObj->getRegionCode();
    $billingArray['billZip'] = $billingAddressObj->getPostcode();
    $billingArray['billCountry'] = $billingAddressObj->getCountryId();
    $billingArray['billEmail'] = $billingAddressObj->getEmail();
    $billingArray['billPhone'] = $billingAddressObj->getTelephone();
    $billingArray['billFax'] = $billingAddressObj->getFax();
    return $billingArray;
  }

  /**
   * Will return array of values related to single item in order.
   * @param type $item 
   * @return string[]
   */
  private function getItemData($item)
  {
    $itemDataArray = [];
    $itemDataArray['itemType'] = "10";
    $itemDataArray['itemNumber'] = $item->getSku();
    $itemDataArray['itemPrice'] = $item->getPrice();
    $itemDataArray['itemQuantity'] = $item->getQtyOrdered();
    $itemDataArray['itemUOM'] = "";
    $itemDataArray['itemTaxable'] = "";
    $itemDataArray['itemQBClass'] = "";
    $itemDataArray['itemNote'] = "";
    $itemDataArray['kitItem'] = ""; //TODO work on this value
    $itemDataArray['showItem'] = "";
    return $itemDataArray;
  }

  /**
   * Will write dummy item row(example shipping row).
   * @param type $itemType 
   * @param type|string $itemNumber 
   * @param type|string $itemPrice 
   * @return string[]
   */
  private function getDummyItem($itemType, $itemNumber = "", $itemPrice = "")
  {
    return [$itemType,$itemNumber,$itemPrice,"","","","","","",""];
  }

  /**
   * Will write headers and all values to CSV file.
   * @param type $orderHeaders 
   * @param type $orderValues 
   * @return type
   */
  private function writeCSVWithHeaders($orderHeaders, $orderValues)
  {
    $handle = fopen($this->_filePath, "a+");
    fputcsv($handle, $orderHeaders);
    fputcsv($handle, $orderValues);
    fclose($handle);
  }

  /**
   * Will write CSV headers to filePath property.
   * @return type
   */
  private function writeHeaders()
  {
    $handle = fopen($this->_filePath, "a+");
    fputcsv($handle, $this->getCSVHeaders());
    fclose($handle);
  }

  /**
   * Will append data to existing CSV file.
   * @param type $values 
   * @return type
   */
  private function appendToCSVFile($values){
  	$handle = fopen($this->_filePath, "a+");
    fputcsv($handle, $values);
    fclose($handle);
  }

  /**
   * Will write headers, merge all values and call function that will write CSV file.
   * @return type
   */
  public function generateCSVFile()
  {
    //Getters for all meta values
    $orderDataArray = $this->getOrderData();
    $shippingDataArray = $this->getShippingData();
    $billingDataArray = $this->getBillingData();

    $this->writeHeaders(); //Will create order CSV file and write headers to it.

    //Will merge all meta data + item data and append it to CSV file.
    foreach ($this->_order->getAllItems() as $key => $item) {
      $itemDataArray = $this->getItemData($item);
    	$mergedArray = array_merge($orderDataArray, $shippingDataArray, $billingDataArray, $itemDataArray);
      $this->appendToCSVFile($mergedArray);
    }
    //Populating additonal 3 rows for shipping, subtotal and tax.
    $this->appendToCSVFile(array_merge($orderDataArray, $shippingDataArray, $billingDataArray, $this->getDummyItem("60", "shipping", "0")));
    $this->appendToCSVFile(array_merge($orderDataArray, $shippingDataArray, $billingDataArray, $this->getDummyItem("40")));
    $this->appendToCSVFile(array_merge($orderDataArray, $shippingDataArray, $billingDataArray, $this->getDummyItem("70", "Online SalesTax", "0")));
  }

  public function setOrder($order){
  	$this->_order = $order;
  }

  public function setFilePath($filePath){
  	$this->_filePath = $filePath;
  }
}