<?php

class Tinhte_XenTag_XenForo_Model_Tag extends XFCP_Tinhte_XenTag_XenForo_Model_Tag
{
    protected $_Tinhte_XenTag_queriedTags = null;
    protected $_Tinhte_XenTag_checkLimitQueryResultOrderBy = null;

    public function getTagListForEdit($contentType, $contentId, $editOthers, $userId = null)
    {
        $this->Tinhte_XenTag_cacheQueriedTags(true);
        $result = parent::getTagListForEdit($contentType, $contentId, $editOthers, $userId);

        $visitor = XenForo_Visitor::getInstance();
        if (!$visitor->hasPermission('general', Tinhte_XenTag_Constants::PERM_USER_IS_STAFF)) {
            $queriedTags = $this->Tinhte_XenTag_getQueriedTags();

            foreach (array_keys($result['editable']) as $i) {
                $isStaffTag = false;

                foreach ($queriedTags as $tag) {
                    if ($result['editable'][$i] === $tag['tag']
                        && !empty($tag['tinhte_xentag_staff'])
                    ) {
                        $isStaffTag = true;
                    }
                }

                if ($isStaffTag) {
                    $result['uneditable'][] = $result['editable'][$i];
                    unset($result['editable'][$i]);
                }
            }
        }

        if (!!Tinhte_XenTag_Option::get('keepOrder')) {
            $queriedTags = $this->Tinhte_XenTag_getQueriedTags();
            $editableTagTexts = array();

            foreach ($result['editable'] as $editableTagText) {
                $editableTagFound = false;

                foreach ($queriedTags as $tag) {
                    if ($editableTagText === $tag['tag']) {
                        $editableTagTexts[$tag['tag_content_id']] = $tag['tag'];
                        $editableTagFound = true;
                    }
                }

                if (!$editableTagFound) {
                    $editableTagTexts[$editableTagText] = $editableTagText;
                }
            }

            ksort($editableTagTexts);
            $result['editable'] = array_values($editableTagTexts);
        }

        $this->Tinhte_XenTag_cacheQueriedTags(false);

        return $result;
    }

    public function getTags(array $tags, &$notFound = array())
    {
        $result = parent::getTags($tags, $notFound);

        if (is_array($this->_Tinhte_XenTag_queriedTags)) {
            $this->_Tinhte_XenTag_queriedTags += $result;
        }

        return $result;
    }

    public function getTagByUrl($tagUrl)
    {
        $tag = parent::getTagByUrl($tagUrl);

        if (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_TAG_TAG])) {
            /** @var Tinhte_XenTag_XenForo_ControllerPublic_Tag $controller */
            $controller = $GLOBALS[Tinhte_XenTag_Constants::GLOBALS_CONTROLLERPUBLIC_TAG_TAG];
            $controller->Tinhte_XenTag_actionTag($tagUrl, $tag);
        }

        return $tag;
    }


    public function getTagsForContent($contentType, $contentId)
    {
        $result = parent::getTagsForContent($contentType, $contentId);

        if (is_array($this->_Tinhte_XenTag_queriedTags)) {
            $this->_Tinhte_XenTag_queriedTags += $result;
        }

        return $result;
    }

    public function Tinhte_XenTag_cacheQueriedTags($enabled)
    {
        if ($enabled) {
            $this->_Tinhte_XenTag_queriedTags = array();
        } else {
            $this->_Tinhte_XenTag_queriedTags = null;
        }
    }

    public function Tinhte_XenTag_getQueriedTags()
    {
        if (is_array($this->_Tinhte_XenTag_queriedTags)) {
            return $this->_Tinhte_XenTag_queriedTags;
        } else {
            return array();
        }
    }

    public function Tinhte_XenTag_canWatchTag(
        /** @noinspection PhpUnusedParameterInspection */
        array $tag,
        &$errorPhraseKey = '',
        array $viewingUser = null
    ) {
        $this->standardizeViewingUserReference($viewingUser);

        if (empty($viewingUser['user_id'])) {
            return false;
        }

        return !!XenForo_Permission::hasPermission($viewingUser['permissions'], 'general',
            Tinhte_XenTag_Constants::PERM_USER_WATCH);
    }

    public function Tinhte_XenTag_canEditTag(
        /** @noinspection PhpUnusedParameterInspection */
        array $tag,
        &$errorPhraseKey = '',
        array $viewingUser = null
    ) {
        $this->standardizeViewingUserReference($viewingUser);

        if (empty($viewingUser['user_id'])) {
            return false;
        }

        return !!XenForo_Permission::hasPermission($viewingUser['permissions'], 'general',
            Tinhte_XenTag_Constants::PERM_USER_EDIT);
    }

    public function Tinhte_XenTag_getTagsByIds(array $ids)
    {
        if (count($ids) === 1) {
            $tag = $this->getTagById(reset($ids));

            if (!empty($tag)) {
                return array($tag['tag_id'] => $tag);
            }
        } else {
            return $this->fetchAllKeyed('
                SELECT *
                FROM xf_tag
                WHERE tag_id IN (' . $this->_getDb()->quote($ids) . ')
            ', 'tag_id');
        }

        return array();
    }

    public function Tinhte_XenTag_getTagsFromCache()
    {
        $cache = $this->_getDataRegistryModel()->get(Tinhte_XenTag_Constants::DATA_REGISTRY_KEY_TAGS);

        if (!isset($cache['version'])
            || $cache['version'] < Tinhte_XenTag_Constants::DATA_REGISTRY_TAGS_VERSION
            || !isset($cache['time'])
            || XenForo_Application::$time - $cache['time'] > 3600
        ) {
            // cache not found or expired
            $tags = $this->Tinhte_XenTag_rebuildTagsCache();
        } else {
            $tags = $cache['tags'];
        }

        return $tags;
    }

    public function Tinhte_XenTag_rebuildTagsCache()
    {
        $limit = 1000;
        $max = intval(Tinhte_XenTag_Option::get('autoTagGlobalMax'));
        if ($max > 0) {
            $limit = $max;
        }

        $tags = $this->getTagsForCloud($limit);
        $this->_getDataRegistryModel()->set(Tinhte_XenTag_Constants::DATA_REGISTRY_KEY_TAGS, array(
            'tags' => $tags,
            'time' => XenForo_Application::$time,
            'version' => Tinhte_XenTag_Constants::DATA_REGISTRY_TAGS_VERSION,
        ));

        return $tags;
    }

    public function getContentTagCache($contentType, $contentId)
    {
        $cache = parent::getContentTagCache($contentType, $contentId);

        if (isset($GLOBALS[Tinhte_XenTag_Constants::GLOBALS_TAGGER_SAVE])) {
            /** @var Tinhte_XenTag_XenForo_TagHandler_Tagger $tagger */
            $tagger = $GLOBALS[Tinhte_XenTag_Constants::GLOBALS_TAGGER_SAVE];
            $cache = $tagger->Tinhte_XenTag_getContentTagCacheOnSave($this, $cache);
        }

        return $cache;
    }

    public function getContentIdsByTagId($tagId, $limit, $visibleOnly = true)
    {
        $contentIds = parent::getContentIdsByTagId($tagId, $limit, $visibleOnly);

        $this->Tinhte_XenTag_prioritizeContentIds($tagId, $limit, $contentIds);

        return $contentIds;
    }

    public function Tinhte_XenTag_prioritizeContentIds($tagId, $limit, array &$contentIds)
    {
        /** @var Tinhte_XenTag_Model_Search $searchModel */
        $searchModel = $this->getModelFromCache('Tinhte_XenTag_Model_Search');

        $prioritizedContents = $searchModel->getPrioritizedContents();
        if (empty($prioritizedContents)) {
            // nothing to do
            return;
        }

        $dbResults = $this->_getDb()->query('
            SELECT tag_content_id, content_type, content_id
            FROM xf_tag_content
            WHERE tag_id = ?
                AND visible = 1
                AND content_type IN (' . $this->_getDb()->quote(array_keys($prioritizedContents)) . ')
            ORDER BY content_date DESC
		', $tagId);
        $prioritizedContentIds = array();
        while ($dbResult = $dbResults->fetch()) {
            $prioritizedContentIds[$dbResult['tag_content_id']] = array(
                $dbResult['content_type'],
                $dbResult['content_id']
            );
        }
        if (empty($prioritizedContentIds)) {
            // no prioritized results could be found, do nothing
            return;
        }

        $searchModel->sortResults($prioritizedContents, $contentIds, $prioritizedContentIds);

        if (count($contentIds) > $limit) {
            $contentIds = array_slice($contentIds, 0, $limit);
        }
    }

    public function autoCompleteTag($tag, $limit = 10)
    {
        $tags = parent::autoCompleteTag($tag, $limit);

        if (Tinhte_XenTag_Option::get('inwordAc')) {
            $tags = array_merge($tags, $this->fetchAllKeyed($this->limitQueryResults('
                SELECT *
                FROM xf_tag
                WHERE tag LIKE ' . XenForo_Db::quoteLike(' ' . $tag, 'lr') . '
                    AND (use_count > 0 OR permanent = 1)
                ORDER BY tag
            ', $limit), 'tag_id'));
        }

        $visitor = XenForo_Visitor::getInstance();
        $isStaff = $visitor->hasPermission('general', Tinhte_XenTag_Constants::PERM_USER_IS_STAFF);
        foreach (array_keys($tags) as $tagId) {
            if (!empty($tags[$tagId]['tinhte_xentag_staff'])) {
                if ($isStaff) {
                    $GLOBALS[Tinhte_XenTag_Constants::GLOBALS_STAFF_TAGS_DURING_AC][$tagId] = $tags[$tagId];
                } else {
                    unset($tags[$tagId]);
                    continue;
                }
            }
        }

        return $tags;
    }

    public function Tinhte_XenTag_getTrendingTags($cutoff, $limit, $cutoffCreated = 0)
    {
        if ($limit === 0) {
            return array();
        }

        $tags = $this->fetchAllKeyed($this->limitQueryResults('
            SELECT tag.*, COUNT(tag_content.tag_content_id) AS use_count
            FROM `xf_tag_content` AS tag_content
            INNER JOIN `xf_tag` AS tag ON tag.tag_id = tag_content.tag_id
            WHERE tag_content.add_date > ?
                ' . ($cutoffCreated > 0 ? ('AND tag.tinhte_xentag_create_date > ' . intval($cutoffCreated)) : '') . '
            GROUP BY tag_content.tag_id
            ORDER BY use_count DESC
        ', $limit), 'tag_id', $cutoff);

        return $tags;
    }

    public function Tinhte_XenTag_getTrendingThreadTags($cutoff, $limit, $cutoffCreated = 0, array $forumIds = array())
    {
        if ($limit === 0) {
            return array();
        }

        $tags = $this->fetchAllKeyed($this->limitQueryResults('
            SELECT tag.*, COUNT(tag_content.tag_content_id) AS use_count
            FROM `xf_tag_content` AS tag_content
            INNER JOIN `xf_tag` AS tag ON tag.tag_id = tag_content.tag_id
            ' . (count($forumIds) > 0 ? ('INNER JOIN `xf_thread` AS thread ON thread.thread_id = tag_content.content_id') : '') . '
            WHERE tag_content.content_type = "thread"
                AND tag_content.add_date > ?
                ' . ($cutoffCreated > 0 ? ('AND tag.tinhte_xentag_create_date > ' . intval($cutoffCreated)) : '') . '
                ' . (count($forumIds) > 0 ? ('AND thread.node_id IN (' . $this->_getDb()->quote($forumIds) . ')') : '') . '
            GROUP BY tag_content.tag_id
            ORDER BY use_count DESC
        ', $limit), 'tag_id', $cutoff);

        return $tags;
    }

    public function Tinhte_XenTag_rebuildTrendingCache(array $options)
    {
        $tags = $this->Tinhte_XenTag_getTrendingTags(
            XenForo_Application::$time - $options['days'] * 86400,
            $options['max'],
            XenForo_Application::$time - $options['daysCreated'] * 86400);

        $this->_getDataRegistryModel()->set(Tinhte_XenTag_Constants::DATA_REGISTRY_KEY_TRENDING, array(
            'tags' => $tags,
            'time' => XenForo_Application::$time,
            'version' => Tinhte_XenTag_Constants::DATA_REGISTRY_TRENDING_VERSION,
            'optionsSerialized' => serialize($options),
        ));

        return $tags;
    }

    public function Tinhte_XenTag_getTrendingFromCache()
    {
        $cache = $this->_getDataRegistryModel()->get(Tinhte_XenTag_Constants::DATA_REGISTRY_KEY_TRENDING);
        $options = array(
            'days' => Tinhte_XenTag_Option::get('trendingDays'),
            'daysCreated' => Tinhte_XenTag_Option::get('trendingDaysCreated'),
            'max' => Tinhte_XenTag_Option::get('trendingMax'),
        );

        if (!isset($cache['version'])
            || $cache['version'] < Tinhte_XenTag_Constants::DATA_REGISTRY_TRENDING_VERSION
            || !isset($cache['time'])
            || XenForo_Application::$time - $cache['time'] > Tinhte_XenTag_Option::get('trendingTtl') * 86400
            || !isset($cache['optionsSerialized'])
            || $cache['optionsSerialized'] !== serialize($options)
            || (!empty($_REQUEST['_Tinhte_XenTag_noCache']) && XenForo_Visitor::getInstance()->isSuperAdmin())
        ) {
            // cache not found or expired
            $tags = $this->Tinhte_XenTag_rebuildTrendingCache($options);
        } else {
            $tags = $cache['tags'];
        }

        return $tags;
    }

    public function getTagList($containing = null, array $fetchOptions = array())
    {
        if (isset($fetchOptions['order'])) {
            switch ($fetchOptions['order']) {
                case 'tinhte_xentag_create_date':
                case 'tinhte_xentag_view_count':
                    $this->_Tinhte_XenTag_checkLimitQueryResultOrderBy = $fetchOptions['order'];
                    break;
            }
        }

        $tagList = parent::getTagList($containing, $fetchOptions);
        $this->_Tinhte_XenTag_checkLimitQueryResultOrderBy = null;

        return $tagList;
    }

    public function limitQueryResults($query, $limit, $offset = 0)
    {
        if (is_string($this->_Tinhte_XenTag_checkLimitQueryResultOrderBy)) {
            $query = str_replace('ORDER BY tag', sprintf('ORDER BY %s DESC',
                $this->_Tinhte_XenTag_checkLimitQueryResultOrderBy), $query);
        }

        return parent::limitQueryResults($query, $limit, $offset);
    }

    public function prepareApiDataForTag(array $tag)
    {
        $data = call_user_func(array('parent', 'prepareApiDataForTag'), $tag);

        if (!isset($data['links'])) {
            $data['links'] = array();
        }
        if (is_array($data['links'])) {
            $data['links']['followers'] = bdApi_Data_Helper_Core::safeBuildApiLink('tags/followers', $tag);
        }

        if (!isset($data['permissions'])) {
            $data['permissions'] = array();
        }
        if (is_array($data['permissions'])) {
            $data['permissions']['follow'] = $this->Tinhte_XenTag_canWatchTag($tag);

            if ($data['permissions']['follow']) {
                /** @var Tinhte_XenTag_Model_TagWatch $tagWatchModel */
                $tagWatchModel = $this->getModelFromCache('Tinhte_XenTag_Model_TagWatch');
                $tagWatch = $tagWatchModel->getUserTagWatchByIds(XenForo_Visitor::getUserId(), $tag['tag_id']);
                $data['tag_is_followed'] = !!$tagWatch;
            }
        }

        return $data;
    }
}