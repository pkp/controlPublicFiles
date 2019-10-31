{**
 * plugins/generic/controlPublicFiles/templates/settings.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Settings form for the controlPublicFiles plugin.
 *}
<script>
	$(function() {ldelim}
		$('#controlPublicFilesSettings').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

{translate key="plugins.generic.controlPublicFiles.settings.description"}

<form
	class="pkp_form"
	id="controlPublicFilesSettings"
	method="POST"
	action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}"
>
	<!-- Always add the csrf token to secure your form -->
	{csrf}

	{fbvFormArea}
		{fbvFormSection label="plugins.generic.controlPublicFiles.setting.disableAllUploads" for="disableAllUploads" list=true}
			{fbvElement
				type="checkbox"
				name="disableAllUploads"
				id="disableAllUploads"
				checked=$disableAllUploads
				value=true
				label="plugins.generic.controlPublicFiles.setting.disableAllUploads.description"
				translate="true"
			}
		{/fbvFormSection}
		{fbvFormSection label="plugins.generic.controlPublicFiles.setting.allowedFileTypes" description="plugins.generic.controlPublicFiles.setting.allowedFileTypes.description"}
			{fbvElement
				type="text"
				id="allowedFileTypes"
				value=$allowedFileTypes
			}
		{/fbvFormSection}
		{fbvFormSection label="plugins.generic.controlPublicFiles.setting.allowedDirSize" description="plugins.generic.controlPublicFiles.setting.allowedDirSize.description"}
			{fbvElement
				type="text"
				id="allowedDirSize"
				value=$allowedDirSize
			}
		{/fbvFormSection}
		{fbvFormSection label="plugins.generic.controlPublicFiles.setting.disableRoles" for="disableRoles" list=true}
			{foreach from=$roles key=$roleId item=$role}
				{if in_array($roleId, $disableRoles)}
					{assign var="checked" value=true}
				{else}
					{assign var="checked" value=false}
				{/if}
				{capture assign="label"}{translate key="plugins.generic.controlPublicFiles.setting.disableRoles.option" role=$role}{/capture}
				{fbvElement
					type="checkbox"
					name="disableRoles[]"
					id="disableRoles"
					checked=$checked
					value=$roleId
					label=$label
					translate=false
				}
			{/foreach}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons submitText="common.save"}
</form>
