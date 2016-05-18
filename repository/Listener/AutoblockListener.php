<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee Standard Edition.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee Standard Edition is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee Standard Edition. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Event\Listener;

use BackBee\Renderer\Event\RendererEvent;
use BackBee\Controller\Exception\FrontControllerException;

use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Autoblock Listener
 *
 * @author      f.kroockmann <florian.kroockmann@lp-digital.fr>
 * @author      a.gobjila <alexandre.gobjila@lp-digital.fr>
 */
class AutoblockListener
{
    /**
     * @var BackBee\BBApplication
     */
    private static $application;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    private static $em;

    /**
     * @var BackBee\Renderer\Renderer
     */
    private static $renderer;

    public static function onRender(RendererEvent $event)
    {
        self::$renderer = $event->getRenderer();
        self::$application = self::$renderer->getApplication();
        self::$em = self::$application->getEntityManager();

        $content = $event->getTarget();
        $parentNodes = self::getParentNodes($content->getParamValue('parent_node')); 

        // Construct selector
        $nodesUids = array();
        foreach($parentNodes as $node) {
            $nodesUids[] = $node->getUid();
        }
        $selector = ['parentnode' => ($nodesUids !== null) ? $nodesUids : [null]];

        // Limits
        $start = (int) $content->getParamValue('start');
        $limitPerPage = (int) $content->getParamValue('limit');

        // Manage pagination
        if(in_array('multipage', $content->getParamValue('multipage'))) {
            $currentPage = (int) self::$application->getRequest()->query->get('page', 1);
            $currentPage = ($currentPage < 1)?1:$currentPage;

            $start = ($currentPage -1 ) * $limitPerPage;

            $pager = array(
                'current' => $currentPage,
                'limit' => $limitPerPage,
                'total' => null
            );
        } else {
            $pager = null;
        }

        // Get contents
        $contents = self::$em->getRepository('BackBee\ClassContent\AbstractClassContent')
                             ->getSelection(
                                 $selector,
                                 in_array('multipage', $content->getParamValue('multipage')),
                                 in_array('recursive', $content->getParamValue('recursive')),
                                 $start,
                                 $limitPerPage,
                                 true,
                                 false,
                                 (array) $content->getParamValue('content_to_show'),
                                 (int) $content->getParamValue('delta')
                             );

        $count = $contents instanceof Paginator ? $contents->count() : count($contents);
        $nbContents = $contents instanceof Paginator ? $contents->getIterator()->count() : $count;

        // Complete pagination vars
        if(!is_null($pager)) {
            $pager['total'] = (int) ceil($count / $limitPerPage);
            $pager['prev'] = ($currentPage <= 1)?null:$currentPage - 1;
            $pager['next'] = ($currentPage < $pager['total'])?$currentPage + 1:null;

            if($nbContents == 0 && $currentPage !=1) {
                throw new FrontControllerException("Page Not Found", FrontControllerException::NOT_FOUND);
            }
        }

        // Assign renderer vars
        self::$renderer->assign('contents', $contents);
        self::$renderer->assign('nbContents', $nbContents);
        self::$renderer->assign('parentNode', array_shift($parentNodes));
        self::$renderer->assign('pager', $pager);
    }

    public static function getParentNodes($parentNodeParam)
    {
        $parentNode = null;

        if (!empty($parentNodeParam)) {
            if (is_array($parentNodeParam) === true) {
                foreach ($parentNodeParam as $key => $parentNodeData) {
                    if (isset($parentNodeData['pageUid']) && !empty($parentNodeData['pageUid'])) {
                        $pageNode = self::$em->getRepository('BackBee\NestedNode\Page')->find($parentNodeData['pageUid']);
                        $parentNode[] = $pageNode;
                    }
                }
            }
        } else {
            $parentNode[] = self::$renderer->getCurrentPage();
        }

        return $parentNode;
    }
}
