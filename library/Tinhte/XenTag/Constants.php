<?php

class Tinhte_XenTag_Constants
{
    const CONTENT_TYPE_FORUM = 'tinhte_xentag_forum';

    const DATA_REGISTRY_KEY_TAGS = 'Tinhte_XenTag_tags';
    const DATA_REGISTRY_TAGS_VERSION = 2;
    const DATA_REGISTRY_KEY_TRENDING = 'Tinhte_XenTag_trending';
    const DATA_REGISTRY_TRENDING_VERSION = 3;

    const FIELD_FORUM_TAGS = 'tinhte_xentag_tags';
    const FIELD_PAGE_TAGS = 'tinhte_xentag_tags';
    const FIELD_POST_TAGS = 'tinhte_xentag_tags';
    const FIELD_RESOURCE_TAGS = 'tags';
    const FIELD_THREAD_TAGS = 'tags';

    const GLOBALS_CONTROLLERADMIN_FORUM_SAVE = 'Tinhte_XenTag_XenForo_ControllerAdmin_Forum::actionSave';
    const GLOBALS_CONTROLLERADMIN_PAGE_SAVE = 'Tinhte_XenTag_XenForo_ControllerAdmin_Page::actionSave';
    const GLOBALS_CONTROLLERADMIN_TAG_SAVE = 'Tinhte_XenTag_XenForo_ControllerAdmin_Tag::actionSave';
    const GLOBALS_CONTROLLERPUBLIC_TAG_TAG = 'Tinhte_XenTag_XenForo_ControllerPublic_Tag::actionTag';
    const GLOBALS_CONTROLLERPUBLIC_THREAD_TAGS = 'Tinhte_XenTag_XenForo_ControllerPublic_Thread::actionTags';
    const GLOBALS_DEFERRED_THREAD_ACTION = 'Tinhte_XenTag_XenForo_Deferred_ThreadAction::execute';
    const GLOBALS_TAGGER_SAVE = 'Tinhte_XenTag_XenForo_TagHandler_Tagger::save';
    const GLOBALS_STAFF_TAGS_DURING_AC = 'Tinhte_XenTag_staffTagsDuringAutoComplete';

    const PERM_USER_WATCH = 'Tinhte_XenTag_watch';
    const PERM_USER_IS_STAFF = 'Tinhte_XenTag_isStaff';
    const PERM_USER_EDIT = 'Tinhte_XenTag_edit';

    const SEARCH_CONSTRAINT_TAGS = 'tag';
    const SEARCH_METADATA_TAGS = 'tag';
    const SEARCH_TYPE = 'tag';

    const THREAD_SEARCH_TAG = 'tinhte_xentag_tag';

}
