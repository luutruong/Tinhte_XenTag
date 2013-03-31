<?php

class Tinhte_XenTag_XenForo_Importer_vBulletin extends XFCP_Tinhte_XenTag_XenForo_Importer_vBulletin {
	
	public function getSteps() {
		$steps = parent::getSteps();
		
		$steps = array_merge($steps,array(
			'tinhteXenTagTags' => array(
				'title' => 'Import Tags',
				'depends' => array('threads')
			),
		));

		return $steps;
	}
	
	public function stepTinhteXenTagTags($start, array $options) {
		$options = array_merge(array(
			'limit' => 100,
			'max' => false
		), $options);

		$sDb = $this->_sourceDb;
		$prefix = $this->_prefix;

		/* @var $model XenForo_Model_Import */
		$model = $this->_importModel;

		if ($options['max'] === false)
		{
			$options['max'] = $sDb->fetchOne('
				SELECT MAX(threadid)
				FROM ' . $prefix . 'thread
			');
		}

		$tags = $sDb->fetchAll(
			'
				SELECT tag.*, tagcontent.*
				FROM ' . $prefix . 'tagcontent AS tagcontent
				INNER JOIN ' . $prefix . 'tag AS tag ON (tag.tagid = tagcontent.tagid)
				WHERE
					tagcontent.contenttypeid = 2
					AND tagcontent.contentid > ' . $sDb->quote($start) . '
					AND tagcontent.contentid < ' . $sDb->quote($start + $options['limit']) . '
			'
		);
		if (!$tags) {
			return true;
		}

		$next = 0;
		$total = 0;

		$threadIdMap = $model->getThreadIdsMapFromArray($tags, 'contentid');
		
		$threadTags = array();
		foreach (array_keys($tags) as $key) {
			$threadId = $tags[$key]['contentid'];
			
			if (!isset($threadTags[$threadId])) $threadTags[$threadId] = array();
			
			$threadTags[$threadId][] = $tags[$key]['tagtext'];
			
			unset($tags[$key]); // free memory asap
		}

		XenForo_Db::beginTransaction();
		
		foreach ($threadTags as $threadId => $tags) {
			$next = max($next, $threadId);
			
			$newThreadId = $this->_mapLookUp($threadIdMap, $threadId);
			if (empty($newThreadId)) {
				// new thread not found? Hmm
				continue;
			}
			
			$dw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
			$dw->setImportMode(true);
			$dw->setExistingData($newThreadId);
			$dw->Tinhte_XenTag_setTags($tags);
			$dw->save();
			
			$dw->Tinhte_XenTag_updateTagsInDatabase(); // manually call this because _postSave() won't be called in import mode
			
			$total++;
		}

		XenForo_Db::commit();

		$this->_session->incrementStepImportTotal($total);

		return array($next, $options, $this->_getProgressOutput($next, $options['max']));
	}
	
}