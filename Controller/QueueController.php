<?php
/**
 * PHP-Resque Web UI.
 *
 * @copyright (c) 2017-2020 Team ALPS
 * @author Andreas Erhard <developer@andaris.at>
 */

namespace Andaris\ResqueWebUiBundle\Controller;

use Twig_Environment;

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
     * @param Twig_Environment   $twig
     * @param ResqueConfigurator $configurator
     * @param QueueFactory       $queueFactory
     */
    public function __construct(
        Twig_Environment $twig,
        ResqueConfigurator $configurator,
        QueueFactory $queueFactory
    ) {
        parent::__construct($twig, $configurator);

        $this->queueFactory = $queueFactory;
    }

    public function indexAction()
    {
        $queues = $this->queueFactory->createAll();

        return $this->render('AndarisResqueWebUiBundle:Queue:index.html.twig', ['queues' => $queues]);
    }
}
