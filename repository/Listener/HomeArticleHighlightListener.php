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
use BackBee\ClassContent\Article\Article;
use BackBee\NestedNode\Page;

/**
 * HomeArticleHighlight Listener
 *
 * @author a.gobjila <alexandre.gobjila@lp-digital.fr>
 */
class HomeArticleHighlightListener
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

        $contents = array();
        foreach ($parentNodes as $key => $page) {
            $article = self::getMainArticle($page);
            $contents[] = $article;
            break;
        }

        self::$renderer->assign('contents', $contents);
    }


    private static function getParentNodes($parentNodeParam)
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
            // Get latest Article page
            $layout = self::$em->getRepository('BackBee\Site\Layout')->findOneBy(array('_label' => 'Article'));

            $page = self::$em->getRepository('BackBee\NestedNode\Page')
                    ->createQueryBuilder('p')
                    ->andIsOnline()
                    ->andWhere('p._layout = :layout')
                    ->setParameter('layout', $layout)
                    ->orderBy('p._modified', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();

            $parentNode[] = $page;
        }

        return $parentNode;
    }


    private static function getMainArticle(Page $page)
    {
        $mainArticle = null;

        if($page->getLayout()->getLabel() == 'Article') {
            $mainArticle = null;

            $firstContents = $page->getContentSet()->first()->getData();
            foreach ($firstContents as $key => $item) {
                if($item instanceof Article) {
                    $mainArticle = $item;
                    break;
                }
            }
        }

        return $mainArticle;
    }


}
