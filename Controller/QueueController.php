<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2026 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Controller;

use Twig\Environment;

use Andaris\ResqueWebUiBundle\Adapter\ResqueConfigurator;
use Andaris\ResqueWebUiBundle\Dto\QueueFactory;

class QueueController extends AbstractController
{
    /**
     * @var QueueFactory
     */
    private $queueFactory;

    /**
     * Constructor.
     *
     * @param Environment        $twig
     * @param ResqueConfigurator $configurator
     * @param QueueFactory       $queueFactory
     */
    public function __construct(
        Environment $twig,
        ResqueConfigurator $configurator,
        QueueFactory $queueFactory
    ) {
        parent::__construct($twig, $configurator);

        $this->queueFactory = $queueFactory;
    }

    public function indexAction()
    {
        $queues = $this->queueFactory->createAll();

        return $this->render('@AndarisResqueWebUi/Queue/index.html.twig', ['queues' => $queues]);
    }
}
