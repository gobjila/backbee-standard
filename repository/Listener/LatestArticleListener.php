<?php

namespace BackBee\Event\Listener;

use BackBee\Event\Event;
use BackBee\ClassContent\Article\LatestArticle;

/**
 * LatestArticle Listener
 *
 * @author      a.gobjila <alexandre.gobjila.@lp-digital.fr>
 */
class LatestArticleListener
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

    public static function onRender(Event $event)
    {
        $content = $event->getTarget();

        if (!($event->getTarget() instanceof LatestArticle)) {
            return;
        }

        self::$renderer = $event->getEventArgs();
        self::$application = self::$renderer->getApplication();
        self::$em = self::$application->getEntityManager();

        $excludes = array();

        $page = self::$renderer->getCurrentPage();

        // Get main Article
        if($page->isRoot() && $page->getLayout()->getLabel() == 'Article') {
            $mainArticle = null;

            $firstContents = $page->getContentSet()->first()->getData();
            foreach ($firstContents as $key => $item) {
                if($item instanceof \BackBee\ClassContent\Article\Article) {
                    $mainArticle = $item;
                    break;
                }
            }
        } else {
            $mainArticle = self::$em
                ->getRepository('BackBee\ClassContent\Article\Article')
                ->findOneBy(['_mainnode' => $page]);    
        }

        // Get Main Article and Related for exclusion
        if($mainArticle) {
            $excludes[] = $mainArticle->getUid();

            // Get the Related articles for Main Article
            if(!empty($mainArticle->related)) {
                foreach($mainArticle->related->getData() as $position => $relatedContent) {
                    $relatedArticle = $relatedContent->first();
                    if($relatedArticle !== null) { 
                        $excludes[] = $relatedArticle->getUid();
                    }
                }
            }
        }

        $originalLimit = (int) $content->getParamValue('limit');

        // Recalculate limit according with exclusions
        $content->setParam('limit', $originalLimit + count($excludes));

        $parentNodes = self::getParentNodes($content->getParamValue('parent_node')); 
        
        // Random mode
        if(in_array('random', $content->getParamValue('random'))) {

            // Count all Articles
            $totalLimit = self::$em->getRepository('BackBee\ClassContent\AbstractClassContent')
                                ->countContentsByClassname(['Article\Article']);

            // Set new params for random
            $content->setParam('limit', $totalLimit);

            // Get contents from Autoblock
            AutoblockListener::onRender($event);
            $contents = self::$renderer->contents;

            // Keep only selected keys
            if(!empty($contents)) {
                $randKeys = array_rand($contents, $originalLimit + count($excludes));
                $contents = array_values(array_intersect_key($contents, array_flip($randKeys)));
            }

        } else {
            // Get contents from Autoblock
            AutoblockListener::onRender($event);
            $contents = self::$renderer->contents;
        }
        $event->stopPropagation();

        // Reset params
        $content->setParam('limit', $originalLimit);

        // Exclude articles
        foreach ($contents as $key => $article) {
            if(in_array($article->getUid(), $excludes)) {
                unset($contents[$key]);
            }
        }

        $contents = array_slice($contents, 0, $originalLimit);

        // Assign renderer vars
        self::$renderer->assign('contents', $contents);
        self::$renderer->assign('parentNode', array_shift($parentNodes));
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
            $parentNode[] = self::$renderer->getCurrentPage();
        }

        return $parentNode;
    }
}