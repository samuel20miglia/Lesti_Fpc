<?php
/**
 * Lesti_Fpc (http:gordonlesti.com/lestifpc)
 *
 * PHP version 5
 *
 * @link      https://github.com/GordonLesti/Lesti_Fpc
 * @package   Lesti_Fpc
 * @author    Gordon Lesti <info@gordonlesti.com>
 * @copyright Copyright (c) 2013-2016 Gordon Lesti (http://gordonlesti.com)
 * @license   http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
namespace Lesti\Fpc\Controller\Product;

/**
 * Class Lesti_Fpc_Catalog_ProductController
 */
class ProductController extends \Magento\Framework\App\Action\Action
{

    /**
     *
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    protected $product;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $product)
    {
        $this->request = $request;
        $this->eventManager = $eventManager;
        $this->product = $product;
        parent::__construct($context);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \Magento\Framework\App\ActionInterface::execute()
     */
    public function execute()
    {
        $productId = (int) $this->request->getParam('id');
        $product = $this->product->load($productId);
        if ($product->getId()) {
            $this->eventManager->dispatch('catalog_controller_product_view', array(
                'product' => $product
            ));
        }
    }
}
