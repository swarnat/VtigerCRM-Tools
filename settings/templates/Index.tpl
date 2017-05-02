<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
<div id="pageOverlay" onclick="closePageOverlay();" style='cursor:url("modules/SwVtTools/icons/cross-button.png"), auto;position:fixed;z-index:20000;top:0;left:0;display:none;height:100%;width:100%;background-image:url("modules/Workflow2/icons/modal.png");'><div id='pageOverlayContent' style='position:fixed;cursor:default;top:100px;margin:auto;left:50%;padding:10px;background-color:#ffffff;'>&nbsp;</div></div>
<div class="container-fluid" id="moduleManagerContents">

    <div class="widget_header row-fluid">
        <div class="span12">
            <h3>
                <b>
                    Stefan Warnat VtigerCRM Tools
                </b>
            </h3>
        </div>
    </div>
    <hr>
    {if $show_cron_warning eq true}
        <p class="alert alert-danger"><strong>ERROR:</strong><br/>It looks like you don't activate the Cronjob of this VtigerCRM System. <strong>Please activate!</strong> Otherwise lot's of functions won't work like expected.</p>
    {/if}
    <div class="pcss3t pcss3t-icons-bottom pcss3t-theme-5 pcss3t-height-auto">
        <input type="radio" name="pcss3t" checked  id="tab1" class="tab-content-first">
        <label for="tab1">General Settings<i class="fa fa-cogs" aria-hidden="true"></i></label>

        <input type="radio" name="pcss3t"  id="tab2" class="tab-content-2">
        <label for="tab2">Partial DetailView<i class="fa fa-columns" aria-hidden="true"></i></label>

        <input type="radio" name="pcss3t"  id="tab3" class="tab-content-3">
        <label for="tab3">Related Tabs<i class="fa fa-columns" aria-hidden="true"></i></label>

        <ul>
            <li class="tab-content tab-content-first typography">
                <div class="row-fluid">
                <div class="span6">

                    <fieldset class="vtToolBox">
                        <legend>Numbers with comma</legend>
                        {if $comma_numbers_enabled eq true}
                            You could enter numbers with the use of the comma. <button class="btn btn-warning pull-right" type="button" onclick="return SwVtTools.commaNumbers(false);">deactivate this function</button><br/>
                        {else}
                            You could <strong>NOT</strong> enter numbers with the use of the comma. <button class="btn btn-primary pull-right" type="button" onclick="return SwVtTools.commaNumbers(true);">activate this function</button><br/>
                        {/if}
                    </fieldset>
                    <fieldset class="vtToolBox">
                        <legend>create default Related Lists</legend>

                        <form method="POST" action="#">
                            <input type="hidden" name="tool_action" value="createRelation"/>
                            <input type="radio" name="reltype" value="get_dependents_list" /> Relation against UIType 10 field<br/>
                            <p style="margin-left: 5px;border-left:2px solid #ccc; padding-left:5px;"><strong>Example</strong>: You create a UIType 10 field within Invoices to Projects.<br/>Normally you don't see linked Invoices in a Project. This relation could be activated with this option.<br/><strong>This type require a UIType 10 field in the other direction!</strong></p>
                            <input type="radio" name="reltype" value="get_related_list" checked="checked" /> free Relation without a field<br/>
                            <p style="margin-left: 5px;border-left:2px solid #ccc; padding-left:5px;"><strong>Example</strong>: Works like the relation to documents. You could freely add a link in the direction of this relation. If you want to link one invoice to multiple projects, you couldn't use a UIType 10 field. You need to use this option!</strong></p>
                            <table>
                                <tr>
                                    <td>Create RelatedTab here:</td>
                                    <td><select name="tabid">
                                            {foreach from=$entityModules key=tabid item=module}
                                                <option value="{$tabid}">{$module.1}</option>
                                            {/foreach}
                                        </select></td>
                                </tr>
                                <tr>
                                    <td>Related module:</td>
                                    <td><select name="related_tabid">
                                            {foreach from=$entityModules key=tabid item=module}
                                                <option value="{$tabid}">{$module.1}</option>
                                            {/foreach}
                                        </select></td>
                                </tr>
                                <tr>
                                    <td>Label of Relation</td>
                                    <td><input type="text" name="label" value=""/></td>
                                </tr>
                                <tr>
                                    <td><input type="submit" name="submit" class="btn btn-primary" value="create Relation"> </td>
                                </tr>
                            </table>
                        </form>
                    </fieldset>

                    <fieldset class="vtToolBox">
                        <legend>Limit Reference Selection</legend>
                        <div id="ReferenceFilterList" data-url="index.php?module=SwVtTools&parent=Settings&view=ReferenceFilter"></div>
                        <select id="addreferencefield" name="addreferencefield" class="select2" style="margin:0;width:50%;min-width:300px;">
                            {foreach from=$referenceFields key=fieldname item=label}
                                <option value="{$fieldname}">{$label}</option>
                            {/foreach}
                        </select>
                        <input type="button" class="btn btn-default addReferenceFilter" value="Add Reference Filter" />
                        <div id="ReferenceFilterEditor" style="display:none;padding:5px;margin:5px;border:1px solid #ccc;overflow:hidden;">
                            <input type="hidden" id="ReferenceFilterId" value="" />
                            <textarea style="margin:0;width:100%;height:100px;" id="ReferenceFilterCondition"></textarea>
                            <input type="button" style="margin:0;" class="pull-right btn btn-success SaveReferenceFilter" value="Save Reference Filter" />
                        </div>
                    </fieldset>
                    <fieldset class="vtToolBox">
                        <legend>manage sidebar widgets</legend>
                        {if !empty($editSidebar)}
                            <script type="text/javascript">var MOD = {ldelim}LBL_SAVE:"Save", LBL_CANCEL:"Cancel"};workflowID = 0; workflowModuleName = '{$editSidebar.moduleName}';</script>
                            <form method="POST" action="#" style="border:1px solid #cccccc;padding:10px;margin:10px;">
                                <input type="hidden" name="tool_action" value="saveSidebar"/>

                                <input type="hidden" name="sidebar_id" value="{$editSidebar.id}">
                                <input type="hidden" name="sidebar_tabid" value="{$editSidebar.tabid}">

                                <label style="float:left;width:100px;">Active</label> &nbsp;&nbsp;&nbsp;<input type="checkbox" name="active" value="1" {if $editSidebar.active eq '1'}checked='checked'{/if}><br>
                                <label style="clear:both;float:left;width:100px;">Title</label><input type="text" name="title" value="{$editSidebar.title}"><br>
                                <label style="clear:both;float:left;width:100px;">Content</label><br>
                                <div class="clearfix"></div>
                                <input type="hidden" id="templateVarContainer" value="" />
                                <input type="button" class="btn btn-primary" value="{vtranslate('insert Fieldcontent', 'Settings:Workflow2')}" id="btn_insert_variable">

                                <textarea id="sidebarContent" name="content" class='ckeditor'>{$editSidebar.content|@stripslashes}</textarea>
                                <input type="submit" class="btn btn-primary" value="save Widget" />
                            </form>
                            <script type="text/javascript">
                                jQuery( document ).ready( function() {
                                    jQuery( 'textarea#sidebarContent' ).ckeditor();

                                    jQuery('#btn_insert_variable').on('click',function(e) {
                                        insertTemplateField('templateVarContainer','([source]: ([module]) [destination])', true, true,
                                                {
                                                    callback: function(text, param) {
                                                        var textarea = CKEDITOR.instances.sidebarContent;
                                                        textarea.insertHtml(text);
                                                    }
                                                }
                                        );
                                    });

                                });
                            </script>
                        {/if}
                        <form method="POST" action="#">
                            <input type="hidden" name="tool_action" value="createSidebar"/>
                            <select name="sidebar_module" style="margin:0;">
                                {foreach from=$entityModules key=tabid item=module}
                                    <option value="{$tabid}">{$module.1}</option>
                                {/foreach}
                            </select>
                            <input type="submit" class="btn btn-primary" value="create Widget" />
                        </form>
                        {if count($sidebars) > 0}
                            <table style="width:100%;">
                                {foreach from=$sidebars item=sidebar}
                                    <tr>
                                        <td><strong>{$sidebar.moduleName}</strong></td>
                                        <td style="color:{if $sidebar.active eq '1'}#119a01{else}#b8671b{/if}">{$sidebar.title}</td>
                                        <td>
                                            <a href="index.php?module=SwVtTools&view=Index&parent=Settings&editSidebar={$sidebar.id}" class="btn btn-primary">edit</a>
                                            <a href="index.php?module=SwVtTools&view=Index&parent=Settings&delSidebar={$sidebar.id}" class="btn btn-primary">delete</a>
                                        </td>
                                    </tr>
                                {/foreach}
                            </table>
                        {/if}
                    </fieldset>
                    <fieldset class="vtToolBox">
                        <legend>Switch User</legend>
                        <form method="POST" action="#">
                            <em>You will immediatelly logged in into the new User. You need to Relogin to get access to the Admin User.</em><br/><br/>
                            <input type="hidden" name="tool_action" value="switchUser"/>
                            <select name="user" style="margin:0;">
                                {foreach from=$availableUsers item=userItem}
                                    <option value="{$userItem.id}">{$userItem.user_name} - {$userItem.first_name} {$userItem.last_name}</option>
                                {/foreach}
                            </select>
                            <input type="submit" class="btn" value="Switch User" />
                        </form>
                    </fieldset>
                    {*<fieldset class="vtToolBox">
                            <legend>Patcher - File modifications made easy</legend>
                        <form method="POST" action="index.php" enctype="multipart/form-data">
                            <input type="hidden" name="module" value="SwVtTools" />
                            <input type="hidden" name="view" value="Patcher" />
                            <input type="hidden" name="parent" value="Settings" />
                            <em>Upload precreated files, which contain file modification you will apply to your vtiger.</em><br/>
                            <p>Step 1 - Patch file fill be tested in your system<br/>
                            Step 2 - Patch file will be applied</p>
                            <br/>
                            <input type="hidden" name="tool_action" value="patcher"/>
                            <input type="submit" class="btn btn-primary pull-right" value="start Step 1" />
                            <input type="file" required="required" name="patchfile" value="" />

                        </form>
                    </fieldset>*}
                </div> <!-- span6 -->

                <div class="span6">
                    <fieldset class="vtToolbox">
                        <iframe src="https://shop.stefanwarnat.de/modules" width="100%" height="200" frameborder=0 ALLOWTRANSPARENCY="true"></iframe>
                    </fieldset>
                    <!--<fieldset style="border: 1px solid #cccccc; padding:10px;">
                <legend>activate Comments</legend>
                <form method="POST" action="#">
                    <input type="hidden" name="tool_action" value="addModcomment"/>

                    Activate Comments in this module:
                    <select name="tabid" class="select2" style="width:180px;">
                        {foreach from=$entityModules key=tabid item=module}
                            <option value="{$tabid}">{$module.1}</option>
                        {/foreach}
                    </select>
                    <input type="submit" class="btn btn-primary" name="submit" value="Activate"/>
                </form>
            </fieldset>-->
                    <fieldset class="vtToolBox">
                        <legend>Google Calendar Sync</legend>
                        <p><strong>AutoSync:</strong></p>
                        {if $gcal_autosync eq true}
                            Every configured Google Calendar Sync will be automatically executed from Scheduler.
                            <button class="btn btn-warning" type="button" onclick="return SwVtTools.GCalSync(false);">deactivate this function</button>
                            <button class="btn btn-warning" type="button" onclick="return SwVtTools.GeneralOptions('gsync_test');">test cronjob</button>
                            <br/>
                        {else}
                            This function will automatically sync configured Google Calendar connections. <button class="btn btn-primary" type="button" onclick="return SwVtTools.GCalSync(true);">activate this function</button><br/>
                        {/if}

                        <p><strong>custom Google Calendar:</strong></p>
                        <ol>
                            <li>This will create the required database table to make use of this function: <button class="btn btn-primary" type="button" onclick="return SwVtTools.initGCalSync();">initialize database</button></li>
                            <li><a class="btn btn-default" style="color:#000;text-decoration:none;" href="index.php?module=SwVtTools&parent=Settings&&view=Patcher&hash=customcalendar">Start Filemodification (preview first)</a></li>
                        </ol>
                    </fieldset>
                    <fieldset class="vtToolBox">
                        <legend>Filter Im-/Export</legend>
                        <form method="POST" action="index.php?module=SwVtTools&action=ExportCustomView&parent=Settings" enctype="multipart/form-data">
                            <select name="filterId" class="chzn-select" style="width:350px;">
                                {foreach from=$customViews key=viewId item=filterName}
                                    <option value="{$viewId}">{$filterName}</option>
                                {/foreach}
                            </select>
                            <input type="submit" class="btn btn-primary" name="export" value="export filter" />
                        </form>
                        <hr/>
                        <form method="POST" action="#" enctype="multipart/form-data">
                            {if !isset($importKey)}
                                <input type="hidden" name="tool_action" value="cv_import1" />
                                Step 1: Import view: <input type="file" name="customview" />
                                <br/>
                                Select module to Import:
                                <select name="cvImportModule">
                                    <option value="">Original one from CustomView</option>
                                    {foreach from=$entityModules key=tabid item=module}
                                        <option value="{$module.0}">{$module.1}</option>
                                    {/foreach}
                                </select>
                                <input type="submit" class="btn btn-primary" name="import" value="import filter" />
                            {else}
                                <input type="hidden" name="tool_action" value="cv_import2" />
                                <input type="hidden" name="cvImportKey" value="{$importKey}" />
                                Step 2: Select corresponding fields
                                <div>
                                    <label style="display: inline-block;width:200px;font-weight: bold;">Filter Name</label>
                                    <input type="text" name="filterName" value="imported Filter" />
                                </div>
                                <br/>
                                {foreach from=$cvImportColumns item=column}
                                    <div>
                                        <label style="display: inline-block;width:200px;">{vtranslate($column.2, $column.1)}</label>
                                        <select name="column[{$column.0}]">
                                            {foreach from=$import_available_fields item=field}
                                                <option value="{$field->name}" {if $column.5 eq $field->name}selected="selected"{/if}>{$field->label}</option>
                                            {/foreach}
                                        </select>
                                    </div>
                                {/foreach}
                                <input type="submit" class="btn btn-success" name="import" value="Spalten anwenden" />
                            {/if}
                            {if $showCVImportError neq false}
                                <br/><strong style="color:red;">{$showCVImportError}</strong>
                            {/if}
                        </form>
                        <div></div>
                    </fieldset>
                    <!--<fieldset style="border: 1px solid #cccccc; padding:10px;">
                <legend>Make Filter to default for all Users</legend>
                <form method="POST" action="#">
                    <input type="hidden" name="tool_action" value="makeCvToDefault" />
                    <select multiple="multiple" name="filterIds[]" class="select2" style="width:350px;">
                        {foreach from=$customViews key=viewId item=filterName}
                            <option value="{$viewId}">{$filterName}</option>
                        {/foreach}
                    </select>
                    <br/>
                    Set for Users:<br/>
                    <select multiple="multiple" name="userIds[]" id="setDefaultCVuserIds" class="select2" style="width:350px;">
                        {foreach from=$availableUsers item=userItem}
                            <option value="{$userItem.id}">{$userItem.username} - {$userItem.first_name} {$userItem.last_name}</option>
                        {/foreach}
                    </select> <a href="#" onclick='$("#setDefaultCVuserIds > option").prop("selected","selected");$("#setDefaultCVuserIds").trigger("change");return false;'>Select all</a>&nbsp;&nbsp;&nbsp;
                    </select> <a href="#" onclick='$("#setDefaultCVuserIds > option").removeAttr("selected");$("#setDefaultCVuserIds").trigger("change");return false;'>Deselect all</a><br/>
                    <br/>
                    <input type="submit" class="btn btn-primary" name="export" value="make default" />
                </form>
                </fieldset>-->
                    <fieldset class="vtToolBox">
                        <legend>General Options</legend>
                        <table style="width:100%;">
                            <tr>
                                <td><strong>Enable ModComments in all Modules:</strong></td>
                                <td><button class="btn" type="button" onclick="return SwVtTools.GeneralOptions('enableModComments', true);">apply this</button></td>
                            </tr>
                            <tr>
                                <td colspan="2"><button class="btn" type="button" onclick="return SwVtTools.GeneralOptions('recreateUserPrivilegs', true);">Recreate all User Privilege Files</button></td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <select id="checkColumnsModule" style="margin:0;">
                                        {foreach from=$entityModules key=tabid item=module}
                                            <option value="{$module.0}">{$module.1}</option>
                                        {/foreach}
                                    </select>
                                    <button class="btn" type="button" onclick="return SwVtTools.GeneralOptions('checkModuleFields', {ldelim}moduleName:$('#checkColumnsModule').val(){rdelim}, true);">Check missing table columns for fields in Database</button>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"><button class="btn" type="button" onclick="return SwVtTools.GeneralOptions('removeMarketplaceBanner', true);">Remove Marketplace Banner from Dashboard</button></td>
                            </tr>
                            <!--<tr>
                                <td><strong>copy mailscanner mailbox:</strong></td>
                                <td><button class="btn btn-warning" type="button" onclick="return SwVtTools.GeneralOptions('initFilterSortOrder', true);">Initialize</button></td>
                            </tr>-->
                        </table>
                    </fieldset>

                    {if $SHOW_ADDITIONAL eq true}
                        <fieldset class="vtToolBox">
                            <legend>Additional Functions</legend>
                            <table style="width:100%;">
                                <tr>
                                    <td><strong>Remove all Records from Vtiger:</strong></td>
                                    <td><button class="btn" type="button" onclick="return SwVtTools.AdvancedOptions('clearVtiger', true);">apply this</button></td>
                                </tr>
                            </table>
                        </fieldset>
                    {/if}
                    {*<fieldset class="vtToolBox">
                        <legend>EventHandler Functions</legend>

                        {if $EventHandlerActive eq true}<span class="pull-right" style="color:green;font-weight;bold;">ACTIVE</span>{else}<span class="pull-right" style="color:red;font-weight;bold;">INACTIVE</span>{/if}
                        <p>The following features require the <a href="https://github.com/swarnat/vtigerCRM-EventHandler/tree/vtiger6">free EventHandler Module</a>.</p><br/>
                        <table style="width:100%;">
                            <tr>
                                <td><strong>Initialize sortable CustomViews:</strong></td>
                                <td><button class="btn" type="button" onclick="return SwVtTools.GeneralOptions('initFilterSortOrder', true);">Initialize</button></td>
                            </tr>
                        </table>
                    </fieldset>*}
                    <a class="btn pull-right" href="index.php?module=SwVtTools&parent=Settings&view=Upgrade">Check for Update</a>
                </div> <!-- span6 -->
    </div>
            </li>
            <li class="tab-content tab-content-2">
                {if $PartialDetailViewModificationRequired eq true}
                    <div class="alert alert-info">
                        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i> To use this feature, you need to execute this process to apply filemodifications: <a class="btn btn-default" href="index.php?module=SwVtTools&parent=Settings&&view=Patcher&hash=partialdetailview">Check modifications</a>
                    </div>
                {else}
                    <div class="alert alert-success">
                        <i class="fa fa-check-square" aria-hidden="true"></i> Required File modification found!
                    </div>
                {/if}
                <form method="POST" action="#">
                    <input type="hidden" name="tool_action" value="add_detailview_part" />
                    <p>Add Block to module for this module:
                        <select name="modulename" style="margin:0;">
                            {foreach from=$entityModules key=tabid item=module}
                                <option value="{$module.0}">{$module.1}</option>
                            {/foreach}
                        </select>
                        <input class="btn btn-default" type="submit" name="add_subview" value="Add Related Tab for special DetailView" />
                    </p>
                </form>
                <hr/>
                <form method="POST" action="#">
                {if !empty($detailviewTabs)}
                    <div class="alert alert-info">If you name a View "_default", you define the Main Detail View.</div>
                    <table class="table table-condensed">
                        <input type="hidden" name="tool_action" value="save_detailviewpart" />
                        {foreach from=$detailviewTabs item=tab}
                            <tr>
                                <td style="width:200px;"><i class="fa fa-trash trashPartialDetailView" data-id="{$tab.id}" aria-hidden="true"></i>&nbsp;&nbsp;{vtranslate($tab.modulename, $tab.modulename)}</td>
                                <td style="width:200px;">
                                    {if $tab.title neq '_default'}
                                        <input type="text" name="detailviewpart[{$tab.id}][title]" value="{$tab.title}" />
                                    {else}
                                        <input type="hidden" name="detailviewpart[{$tab.id}][title]" value="{$tab.title}" />
                                        Default View
                                    {/if}
                                </td>
                                <td><input type="hidden" class="Select2ForBlockSelection" data-module="{$tab.modulename}" name="detailviewpart[{$tab.id}][blockids]" value="{$tab.blockids}" /></td>
                            </tr>
                        {/foreach}
                    </table>
                {/if}
                    <button class="btn btn-primary" type="submit"><i class="fa fa-floppy-o" aria-hidden="true"></i> Save</button>

                </form>
            </li>
            <li class="tab-content tab-content-3">
                <div class="alert alert-info">This Category allows you to ReOrder the Related Tabs</div>
                <div class="alert alert-warn">If you configure a module here, you must add new tabs manually. They do <strong>NOT</strong> appear automatically if you install new modules.<br/>Also you need to resave this configuration, if you rename a related tab.</div>
                <form method="POST" action="#">
                    <input type="hidden" name="tool_action" value="add_reltab_order" />
                    <p>Add Order for this module:
                        <select name="modulename" style="margin:0;">
                            {foreach from=$entityModules key=tabid item=module}
                                <option value="{$module.0}">{$module.1}</option>
                            {/foreach}
                        </select>
                        <input class="btn btn-default" type="submit" name="add_subview" value="Add Related Tab for special DetailView" />
                    </p>
                </form>
                <hr/>

                <form method="POST" action="#">
                    <input type="hidden" name="tool_action" value="save_reltab_order" />
                    <table class="table table-condensed">
                        {foreach from=$relTabs item=tab}
                            <tr>
                                <td style="width:200px;"><i class="fa fa-trash trashPartialDetailView" data-id="{$tab.id}" aria-hidden="true"></i>&nbsp;&nbsp;{vtranslate($tab.modulename, $tab.modulename)}</td>
                                <td>
                                    <input type="hidden" class="Select2ForRelTabSelection" data-module="{$tab.modulename}" name="reltaborder[{$tab.modulename}][relations]" value="" />
                                </td>
                            </tr>
                        {/foreach}
                    </table>
                    <button class="btn btn-primary" type="submit"><i class="fa fa-floppy-o" aria-hidden="true"></i> Save</button>
                </form>
            </li>
        </ul>




    <br/>


</div>

<script type="text/javascript">
    var blocks = {$availableBlocks|json_encode};
    var blockIndex = {$blockIndex|json_encode};

    var relTabs = {$relTabs|json_encode};
    var relTabIndex = {$availableTabIndex|json_encode};
    var relTabAvailable = {$availableTabs|json_encode};
</script>