<?php
/**
 * @package        PatchTester
 * @copyright      Copyright (C) 2011 Ian MacLennan, Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Methods supporting pull requests.
 *
 * @package        PatchTester
 */
class PatchtesterModelPull extends JModelLegacy
{
	/**
	 * @var  JHttp
	 */
	protected $transport;

	/**
	 * Constructor
	 *
	 * @param   array  $config  An array of configuration options (name, state, dbo, table_path, ignore_request).
	 *
	 * @since   12.2
	 * @throws  Exception
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		// Set up the JHttp object
		$options = new JRegistry;
		$options->set('userAgent', 'JPatchTester/1.0');
		$options->set('timeout', 120);

		$this->transport = JHttpFactory::getHttp($options, 'curl');
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since       1.6
	 */
	protected function populateState()
	{
		// Load the parameters.
		$params = JComponentHelper::getParams('com_patchtester');
		$this->setState('params', $params);
		$this->setState('github_user', $params->get('org', 'joomla'));
		$this->setState('github_repo', $params->get('repo', 'joomla-cms'));

		parent::populateState();
	}

	protected function parsePatch($patch)
	{
		$state = 0;
		$files = array();

		$lines = explode("\n", $patch);

		foreach ($lines AS $line)
		{
			switch ($state)
			{
				case 0:
					if (strpos($line, 'diff --git') === 0)
					{
						$state = 1;
					}
					$file = new stdClass;
					$file->action = 'modified';
					break;

				case 1:
					if (strpos($line, 'index') === 0)
					{
						$file->index = substr($line, 6);
					}

					if (strpos($line, '---') === 0)
					{
						$file->old = substr($line, 6);
					}

					if (strpos($line, '+++') === 0)
					{
						$file->new = substr($line, 6);
					}

					if (strpos($line, 'new file mode') === 0)
					{
						$file->action = 'added';
					}

					if (strpos($line, 'deleted file mode') === 0)
					{
						$file->action = 'deleted';
					}

					if (strpos($line, '@@') === 0)
					{
						$state = 0;
						$files[] = $file;
					}
					break;
			}
		}
		return $files;
	}

	public function apply($id)
	{
		jimport('joomla.filesystem.file');
		//@todo Use the JCurl class
//		require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/curl.php';

		$table = JTable::getInstance('tests', 'PatchTesterTable');
		$github = new JGithub;
		$pull = $github->pulls->get($this->getState('github_user'), $this->getState('github_repo'), $id);

		if (is_null($pull->head->repo))
		{
			throw new Exception(JText::_('COM_PATCHTESTER_REPO_IS_GONE'));
		}

		$patch = $this->transport->get($pull->diff_url)->body;

		$files = $this->parsePatch($patch);

		foreach ($files as $file)
		{
			if ($file->action == 'deleted' && !file_exists(JPATH_ROOT . '/' . $file->old))
			{
				throw new Exception(sprintf(JText::_('COM_PATCHTESTER_FILE_DELETED_DOES_NOT_EXIST_S'), $file->old));
			}
			if ($file->action == 'added' || $file->action == 'modified')
			{

				// if the backup file already exists, we can't apply the patch
				if (file_exists(JPATH_COMPONENT . '/backups/' . md5($file->new) . '.txt'))
				{
					throw new Exception(sprintf(JText::_('COM_PATCHTESTER_CONFLICT_S'), $file->new));
				}

				if ($file->action == 'modified' && !file_exists(JPATH_ROOT . '/' . $file->old))
				{
					throw new Exception(sprintf(JText::_('COM_PATCHTESTER_FILE_MODIFIED_DOES_NOT_EXIST_S'), $file->old));
				}

				$url = 'https://raw.github.com/' . $pull->head->user->login . '/' . $pull->head->repo->name . '/' .
					$pull->head->ref . '/' . $file->new;

				$file->body = $this->transport->get($url)->body;
			}
		}

		// at this point, we have ensured that we have all the new files and there are no conflicts

		foreach ($files as $file)
		{
			// we only create a backup if the file already exists
			if ($file->action == 'deleted' || (file_exists(JPATH_ROOT . '/' . $file->new) && $file->action == 'modified'))
			{
				if (!JFile::copy(JPath::clean(JPATH_ROOT . '/' . $file->old), JPATH_COMPONENT . '/backups/' . md5($file->old) . '.txt'))
				{
					throw new Exception(sprintf('Can not copy file %s to %s'
						, JPATH_ROOT . '/' . $file->old, JPATH_COMPONENT . '/backups/' . md5($file->old) . '.txt'));
				}
			}

			switch ($file->action)
			{
				case 'modified':
				case 'added':
					if (!JFile::write(JPath::clean(JPATH_ROOT . '/' . $file->new), $file->body))
					{
						throw new Exception(sprintf('Can not write the file: %s', JPATH_ROOT . '/' . $file->new));
					}
					break;

				case 'deleted':
					if (!JFile::delete(JPATH::clean(JPATH_ROOT . '/' . $file->old)))
					{
						throw new Exception(sprintf('Can not delete the file: %s', JPATH_ROOT . '/' . $file->old));
					}
					break;
			}
		}

		$table->pull_id = $pull->number;
		$table->data = json_encode($files);
		$table->patched_by = JFactory::getUser()->id;
		$table->applied = 1;
		$version = new JVersion;
		$table->applied_version = $version->getShortVersion();

		if (!$table->store())
		{
			throw new Exception($table->getError());
		}

		return true;
	}

	public function revert($id)
	{
		jimport('joomla.filesystem.file');

		$table = JTable::getInstance('tests', 'PatchTesterTable');

		$table->load($id);

		// we don't want to restore files from an older version
		$version = new JVersion;

		if ($table->applied_version != $version->getShortVersion())
		{
			$table->delete();

			return $this;
		}

		$files = json_decode($table->data);

		if (!$files)
		{
			throw new Exception(sprintf(JText::_('%s - Error retrieving table data (%s)')
				, __METHOD__, htmlentities($table->data)));
		}

		foreach ($files as $file)
		{
			switch ($file->action)
			{
				case 'deleted':
				case 'modified':
					if (!JFile::copy(
						JPATH_COMPONENT . '/backups/' . md5($file->old) . '.txt'
						, JPATH_ROOT . '/' . $file->old)
					)
					{
						throw new Exception(sprintf(
							JText::_('Can not copy file %s to %s')
							, JPATH_COMPONENT . '/backups/' . md5($file->old) . '.txt'
							, JPATH_ROOT . '/' . $file->old));
					}

					if (!JFile::delete(JPATH_COMPONENT . '/backups/' . md5($file->old) . '.txt'))
					{
						throw new Exception(sprintf(
							JText::_('Can not delete the file: %s')
							, JPATH_COMPONENT . '/backups/' . md5($file->old) . '.txt'));
					}
					break;

				case 'added':
					if (!JFile::delete(JPath::clean(JPATH_ROOT . '/' . $file->new)))
					{
						throw new Exception(sprintf(
							JText::_('Can not delete the file: %s')
							, JPATH_ROOT . '/' . $file->new));
					}
					break;
			}
		}

		$table->delete();

		return true;
	}

}
