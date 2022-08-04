<?php
/**
 * @file plugins/generic/controlPublicFiles/ControlPublicFilesSettingsForm.inc.php
 *
 * Copyright (c) 2017-2019 Simon Fraser University
 * Copyright (c) 2017-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ControlPublicFilesPlugin
 * @ingroup plugins_generic_controlPublicFiles
 *
 * @brief Class for the settings form for the ControlPublicFilesPlugin
 */

import('lib.pkp.classes.form.Form');
class ControlPublicFilesSettingsForm extends Form {

	/** @var ControlPublicFilesPlugin  */
	public $plugin;

	/**
	 * @copydoc Form::__construct()
	 */
	public function __construct($plugin) {

		// Define the settings template and store a copy of the plugin object
		parent::__construct($plugin->getTemplateResource('settings.tpl'));
		$this->plugin = $plugin;

		// Always add POST and CSRF validation to secure your form.
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Load settings already saved in the database
	 *
	 * Settings are stored by context, so that each journal or press
	 * can have different settings.
	 */
	public function initData() {
		$contextId = Application::get()->getRequest()->getContext()->getId();
		$this->setData('allowedFileTypes', $this->plugin->getSetting($contextId, 'allowedFileTypes'));
		$this->setData('allowedDirSize', $this->plugin->getSetting($contextId, 'allowedDirSize'));
		$this->setData('disableAllUploads', $this->plugin->getSetting($contextId, 'disableAllUploads'));
		$this->setData('disableRoles', $this->plugin->getSetting($contextId, 'disableRoles'));
		parent::initData();
	}

	/**
	 * Load data that was submitted with the form
	 */
	public function readInputData() {
		$this->readUserVars(['allowedFileTypes', 'allowedDirSize', 'disableAllUploads', 'disableRoles']);
		parent::readInputData();
	}

	/**
	 * Fetch any additional data needed for your form.
	 *
	 * Data assigned to the form using $this->setData() during the
	 * initData() or readInputData() methods will be passed to the
	 * template.
	 *
	 * @return string
	 */
	public function fetch($request, $template = null, $display = false) {
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_DEFAULT,
			LOCALE_COMPONENT_APP_COMMON,
			LOCALE_COMPONENT_PKP_DEFAULT,
			LOCALE_COMPONENT_PKP_COMMON,
			LOCALE_COMPONENT_PKP_USER
		);

		// Pass the plugin name to the template so that it can be
		// used in the URL that the form is submitted to
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->plugin->getName());
		$templateMgr->assign('roles', [
			ROLE_ID_SITE_ADMIN => __('default.groups.plural.siteAdmin'),
			ROLE_ID_MANAGER => __('user.role.managers'),
			ROLE_ID_SUB_EDITOR => __('user.role.subEditors'),
			ROLE_ID_ASSISTANT => __('user.role.journalAssistants'),
			ROLE_ID_AUTHOR => __('user.role.authors'),
			ROLE_ID_REVIEWER => __('user.role.reviewers'),
			ROLE_ID_READER => __('default.groups.plural.reader'),
			ROLE_ID_SUBSCRIPTION_MANAGER => __('user.role.subscriptionManager'),
		]);

		return parent::fetch($request, $template, $display);
	}

	/**
	 * Save the settings
	 *
	 * @return null|mixed
	 */
	public function execute(...$functionArgs) {
		$contextId = Application::get()->getRequest()->getContext()->getId();
		$this->plugin->updateSetting($contextId, 'publicationStatement', $this->getData('publicationStatement'));
		$this->plugin->updateSetting($contextId, 'allowedFileTypes', $this->getData('allowedFileTypes'));
		$this->plugin->updateSetting($contextId, 'allowedDirSize', $this->getData('allowedDirSize'));
		$this->plugin->updateSetting($contextId, 'disableAllUploads', $this->getData('disableAllUploads'));
		$this->plugin->updateSetting($contextId, 'disableRoles', $this->getData('disableRoles'));

		// Tell the user that the save was successful.
		import('classes.notification.NotificationManager');
		$notificationMgr = new NotificationManager();
		$notificationMgr->createTrivialNotification(
			Application::get()->getRequest()->getUser()->getId(),
			NOTIFICATION_TYPE_SUCCESS,
			['contents' => __('common.changesSaved')]
		);

		return parent::execute(...$functionArgs);
	}
}
