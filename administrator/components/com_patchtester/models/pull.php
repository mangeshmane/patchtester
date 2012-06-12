 * @package        PatchTester
 * @copyright      Copyright (C) 2011 Ian MacLennan, Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 * @package        PatchTester
		$this->setState('github_user', $params->get('org', 'joomla'));
		$this->setState('github_repo', $params->get('repo', 'joomla-cms'));
		$lines = explode("\n", $patch);

		foreach ($lines AS $line)
		{
					if (strpos($line, 'diff --git') === 0)
					{
					if (strpos($line, 'index') === 0)
					{
					if (strpos($line, '---') === 0)
					{
					if (strpos($line, '+++') === 0)
					{
					if (strpos($line, 'new file mode') === 0)
					{
					if (strpos($line, 'deleted file mode') === 0)
					{
					if (strpos($line, '@@') === 0)
					{
		//@todo Use the JCurl class
		require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/curl.php';
		$github = new JGithub;
		if (is_null($pull->head->repo))
		{
			throw new Exception(JText::_('COM_PATCHTESTER_REPO_IS_GONE'));
		$patch = PTCurl::getAdapter($pull->diff_url)
			->fetch()->body;
		$files = $this->parsePatch($patch);
		foreach ($files as $file)
		{
			if ($file->action == 'deleted' && !file_exists(JPATH_ROOT . '/' . $file->old))
			{
				throw new Exception(sprintf(JText::_('COM_PATCHTESTER_FILE_DELETED_DOES_NOT_EXIST_S'), $file->old));
			}
			if ($file->action == 'added' || $file->action == 'modified')
			{
				if (file_exists(JPATH_COMPONENT . '/backups/' . md5($file->new) . '.txt'))
				{
					throw new Exception(sprintf(JText::_('COM_PATCHTESTER_CONFLICT_S'), $file->new));
				if ($file->action == 'modified' && !file_exists(JPATH_ROOT . '/' . $file->old))
				{
					throw new Exception(sprintf(JText::_('COM_PATCHTESTER_FILE_MODIFIED_DOES_NOT_EXIST_S'), $file->old));
				$url = 'https://raw.github.com/' . $pull->head->user->login . '/' . $pull->head->repo->name . '/' .
					$pull->head->ref . '/' . $file->new;

				$file->body = PTCurl::getAdapter($url)
					->fetch()->body;
		foreach ($files as $file)
			if ($file->action == 'deleted' || (file_exists(JPATH_ROOT . '/' . $file->new) && $file->action == 'modified'))
			{
				if (!JFile::copy(JPath::clean(JPATH_ROOT . '/' . $file->old), JPATH_COMPONENT . '/backups/' . md5($file->old) . '.txt'))
				{
					throw new Exception(sprintf('Can not copy file %s to %s'
						, JPATH_ROOT . '/' . $file->old, JPATH_COMPONENT . '/backups/' . md5($file->old) . '.txt'));
				}
					if (!JFile::write(JPath::clean(JPATH_ROOT . '/' . $file->new), $file->body))
					{
						throw new Exception(sprintf('Can not write the file: %s', JPATH_ROOT . '/' . $file->new));
					}
					if (!JFile::delete(JPATH::clean(JPATH_ROOT . '/' . $file->old)))
					{
						throw new Exception(sprintf('Can not delete the file: %s', JPATH_ROOT . '/' . $file->old));
					}

		if (!$table->store())
		{
			throw new Exception($table->getError());

		return true;

		if ($table->applied_version != $version->getShortVersion())
		{
			/*
			*/
			$table->delete();
		if (!$files)
		{
			throw new Exception(sprintf('%s - Error retrieving table data (%s)'
				, __METHOD__, htmlentities($table->data)));
		}

		foreach ($files as $file)
		{
			switch ($file->action)
			{
					if (!JFile::copy(
						JPATH_COMPONENT . '/backups/' . md5($file->old) . '.txt'
						, JPATH_ROOT . '/' . $file->old)
					)
					{
						throw new Exception(sprintf('Can not copy file %s to %s'
							, JPATH_COMPONENT . '/backups/' . md5($file->old) . '.txt'
							, JPATH_ROOT . '/' . $file->old));
					}

					if (!JFile::delete(JPATH_COMPONENT . '/backups/' . md5($file->old) . '.txt'))
					{
						throw new Exception(sprintf('Can not delete the file: %s'
							, JPATH_COMPONENT . '/backups/' . md5($file->old) . '.txt'));
					}
					if (!JFile::delete(JPath::clean(JPATH_ROOT . '/' . $file->new)))
					{
						throw new Exception(sprintf('Can not delete the file: %s', JPATH_ROOT . '/' . $file->new));
					}
		/*
		*/
		$table->delete();