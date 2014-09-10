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

    <div>
        <strong>Informations:</strong>&nbsp;&nbsp;&nbsp;
        <a target="_blank" href="http://vtiger.stefanwarnat.de/">International Blog</a>&nbsp;&nbsp;&nbsp;
        <a target="_blank"  href="http://www.stefanwarnat.de/">German Blog</a><br /><br />
        <a href=""
    </div>
    <br />
    {if $comma_numbers_enabled eq true}
        You could enter numbers with the use of the comma. <button class="btn btn-warning" type="button" onclick="return SwVtTools.commaNumbers(false);">deactivate this function</button><br/>
    {else}
        You could <strong>NOT</strong> enter numbers with the use of the comma. <button class="btn btn-primary" type="button" onclick="return SwVtTools.commaNumbers(true);">activate this function</button><br/>
    {/if}
</div>