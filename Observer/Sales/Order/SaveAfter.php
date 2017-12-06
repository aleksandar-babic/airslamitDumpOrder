<?php
namespace Airslamit\DumpOrder\Observer\Sales\Order;

use Magento\Framework\Event\ObserverInterface;

class SaveAfter implements ObserverInterface
{
  protected $_workingDir;
  protected $_ordersDir;
  protected $_order;
  protected $_orderFilePath;
  protected $_csvGenerator;
  protected $_helper;

  /**
   * Constructor will setup working directory and order directory variables, create directory to keep orders CSV files if it does not already exist.
   * @param \Magento\Framework\Filesystem\DirectoryList $workingDir 
   * @return type
   */
  public function __construct(
    \Magento\Framework\Filesystem\DirectoryList $workingDir, 
    \Airslamit\DumpOrder\Model\CSVGenerator $csvGenerator,
    \Airslamit\DumpOrder\Helper\Data $helper
    )
  {
    $this->_workingDir = $workingDir;
    $this->_ordersDir = $this->_workingDir->getPath("var")."/orders/";
    $this->_csvGenerator = $csvGenerator;
    $this->_helper = $helper;

    if (!file_exists($this->_ordersDir)) {
      mkdir($this->_ordersDir, 0777, true);
    }
  }

  /**
   * Will observe event sales_order_save_after.
   * @param \Magento\Framework\Event\Observer $observer 
   * @return type
   */
  public function execute(\Magento\Framework\Event\Observer $observer)
  {
    $this->_order = $observer->getEvent()->getOrder();
    $isPrefixEnabled = $this->_helper->getGeneralConfig('enable');
    if ($isPrefixEnabled == "0") {
      $this->_orderFilePath = $this->_ordersDir."SalesOrderGeneric".$this->_order->getRealOrderId().".csv";  
    } else {
      $prefixIdValue = $this->_helper->getGeneralConfig('id_prefix');
      $customOrderId = $prefixIdValue.$this->_order->getRealOrderId();
      $this->_orderFilePath = $this->_ordersDir."SalesOrderGeneric".$customOrderId.".csv";
      $this->_csvGenerator->setCustomOrderId($customOrderId);
    }

    $this->_csvGenerator->setOrder($this->_order);
    $this->_csvGenerator->setFilePath($this->_orderFilePath);
    if(!file_exists($this->_orderFilePath)) {
      $this->_csvGenerator->generateCSVFile();
    }
  }
}
