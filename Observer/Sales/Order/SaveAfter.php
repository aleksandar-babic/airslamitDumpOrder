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

  /**
   * Constructor will setup working directory and order directory variables, create directory to keep orders CSV files if it does not already exist.
   * @param \Magento\Framework\Filesystem\DirectoryList $workingDir 
   * @return type
   */
  public function __construct(\Magento\Framework\Filesystem\DirectoryList $workingDir, \Airslamit\DumpOrder\Model\CSVGenerator $csvGenerator)
  {
    $this->_workingDir = $workingDir;
    $this->_ordersDir = $this->_workingDir->getPath("var")."/orders/";
    $this->_csvGenerator = $csvGenerator;

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
    $this->_orderFilePath = $this->_ordersDir."SalesOrderGeneric".$this->_order->getRealOrderId().".csv";
    $this->_csvGenerator->setOrder($this->_order);
    $this->_csvGenerator->setFilePath($this->_orderFilePath);
    $this->_csvGenerator->generateCSVFile();
  }
}