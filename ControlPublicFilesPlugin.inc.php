<?php
/**
 * @file ControlPublicFilesPlugin.inc.php
 *
 * Copyright (c) 2017-2022 Simon Fraser University
 * Copyright (c) 2017-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ControlPublicFilesPlugin
 * @brief Plugin class for the ControlPublicFiles plugin.
 */

import('lib.pkp.classes.plugins.GenericPlugin');
class ControlPublicFilesPlugin extends GenericPlugin {

	/**
	 * @copydoc GenericPlugin::register()
	 */
	public function register($category, $path, $mainContextId = NULL) {
		$success = parent::register($category, $path);
		if ($success && $this->getEnabled()) {
			HookRegistry::register('API::uploadPublicFile::permissions', [$this, 'setPublicFilePermissions']);
			HookRegistry::register('newlibraryfileform::validate', [$this, 'validateLibraryFile']);
			HookRegistry::register('temporaryfiledao::_insertobject', [$this, 'validateTemporaryFile']);
		}
		return $success;
	}

	/**
	 * Provide a name for this plugin
	 *
	 * The name will appear in the Plugin Gallery where editors can
	 * install, enable and disable plugins.
	 *
	 * @return string
	 */
	public function getDisplayName() {
		return __('plugins.generic.controlPublicFiles.displayName');
	}

	/**
	 * Provide a description for this plugin
	 *
	 * The description will appear in the Plugin Gallery where editors can
	 * install, enable and disable plugins.
	 *
	 * @return string
	 */
	public function getDescription() {
		return __('plugins.generic.controlPublicFiles.description');
	}

	/**
	 * Add a settings action to the plugin's entry in the
	 * plugins list.
	 *
	 * @param Request $request
	 * @param array $actionArgs
	 * @return array
	 */
	public function getActions($request, $actionArgs) {

		// Get the existing actions
		$actions = parent::getActions($request, $actionArgs);

		// Only add the settings action when the plugin is enabled
		if (!$this->getEnabled()) {
			return $actions;
		}

		// Create a LinkAction that will make a request to the
		// plugin's `manage` method with the `settings` verb.
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$linkAction = new LinkAction(
			'settings',
			new AjaxModal(
				$router->url(
					$request,
					null,
					null,
					'manage',
					null,
					[
						'verb' => 'settings',
						'plugin' => $this->getName(),
						'category' => 'generic'
					]
				),
				$this->getDisplayName()
			),
			__('manager.plugins.settings'),
			null
		);

		// Add the LinkAction to the existing actions.
		// Make it the first action to be consistent with
		// other plugins.
		array_unshift($actions, $linkAction);

		return $actions;
	}

	/**
	 * Show and save the settings form when the settings action
	 * is clicked.
	 *
	 * @param array $args
	 * @param Request $request
	 * @return JSONMessage
	 */
	public function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':

				// Load the custom form
				$this->import('ControlPublicFilesSettingsForm');
				$form = new ControlPublicFilesSettingsForm($this);

				// Fetch the form the first time it loads, before
				// the user has tried to save it
				if (!$request->getUserVar('save')) {
					$form->initData();
					return new JSONMessage(true, $form->fetch($request));
				}

				// Validate and save the form data
				$form->readInputData();
				if ($form->validate()) {
					$form->execute();
					return new JSONMessage(true);
				}
		}
		return parent::manage($args, $request);
	}

	/**
	 * Modify whether the user is allowed to access the public file upload API
	 *
	 * @param string $hookName API::uploadPublicFile::permissions
	 * @param array $params [[
	 * 	@option string The directory where the user's files are going to be uploaded
	 * 	@option boolean Is the current user allowed to upload files?
	 * 	@option integer The max allowed size of the user's public upload directory in bytes
	 * 	@option array File types allowed. Default: ['gif', 'jpg', 'png']
	 *  @option Request
	 *  @option array List of roles (ROLE_ID_*) this user is assigned in the request context
	 * ]]
	 */
	public function setPublicFilePermissions($hookName, $params) {
		$userDir =& $params[0];
		$isUserAllowed =& $params[1];
		$allowedDirSize =& $params[2];
		$allowedFileTypes =& $params[3];
		$request = $params[4];
		$userRoles = $params[5];

		$disableAllUploads = $this->getSetting($request->getContext()->getId(), 'disableAllUploads');
		if ($disableAllUploads) {
			$isUserAllowed = false;
			return;
		}

		$disableRoles = (array) $this->getSetting($request->getContext()->getId(), 'disableRoles');
		if (empty(array_diff($userRoles, $disableRoles))) {
			$isUserAllowed = false;
			return;
		}

		$customDirSize = $this->getSetting($request->getContext()->getId(), 'allowedDirSize');
		if (!is_null($customDirSize) && strlen($customDirSize)) {
			$allowedDirSize = (int) $customDirSize;
		}

		$customFileTypes = $this->getSetting($request->getContext()->getId(), 'allowedFileTypes');
		if (!is_null($customFileTypes) && strlen($customFileTypes)) {
			$allowedFileTypes = explode(',', $customFileTypes);
		}
	}

	public function validateLibraryFile($hookName, $params) {
		$request = Application::get()->getRequest();
		$sessionManager = SessionManager::getManager();
		$session = $sessionManager->getUserSession();
		$invalidFileKey = 'invalid-upload_'. $request->getUser()->getId() . '_' . $request->getUserVar('submissionId');

		if ($session->getSessionVar($invalidFileKey)) {
			$session->unsetSessionVar($invalidFileKey);
			$form = $params[0];
			$form->addError('fileType', 'Invalid file type');
		}
	}

	public function validateTemporaryFile($hookName, $params) {
		$request = Application::get()->getRequest();
		if(array_key_exists('uploadedFile', $_FILES)) {
			$stillDontUpload = false;
			$mimeKeys = json_decode(file_get_contents('./plugins/generic/controlPublicFiles/mimes.json'), TRUE);
			$allowedFileTypes = explode(',', $this->getSetting($request->getContext()->getId(), 'allowedFileTypes'));
			$allowedMimes = [];
			foreach($allowedFileTypes as $allowedFileType) {
				if ($allowedFileType[0] !== '.') $allowedFileType = '.' . $allowedFileType;
				if(array_key_exists($allowedFileType, $mimeKeys)) {
					foreach($mimeKeys[$allowedFileType] as $mime) {
						$allowedMimes[] = $mime;
					}
				} else {
					error_log('unable to lookup filetype: ' . $allowedFileType);
					$path = $_FILES['uploadedFile']['name'];
					$ext = pathinfo($path, PATHINFO_EXTENSION);
					if (!in_array($ext, $allowedFileTypes)) $stillDontUpload = true;
				}
			}

			if(!in_array($_FILES['uploadedFile']['type'], $allowedMimes) || $stillDontUpload) {
				$sessionManager = SessionManager::getManager();
				$session = $sessionManager->getUserSession();
				$invalidFileKey = 'invalid-upload_'. $request->getUser()->getId() . '_' . $request->getUserVar('submissionId');
				$session->unsetSessionVar($invalidFileKey);
				$session->setSessionVar($invalidFileKey, true);
				error_log('set session ' . $invalidFileKey);
			}
		}
	}
}
