<?php
/**
 * @defgroup plugins_generic_controlPublicFiles
 */
/**
 * @file plugins/generic/controlPublicFiles/index.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_controlPublicFiles
 * @brief Wrapper for the Control Public Files plugin.
 *
 */
require_once('ControlPublicFilesPlugin.inc.php');
return new ControlPublicFilesPlugin();