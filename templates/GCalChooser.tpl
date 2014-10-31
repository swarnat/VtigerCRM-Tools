<p>Please select a calendar<br/>you want to use for sync.</p>
<select id="syncCalendarId">
    {foreach from=$calendars item=cal}
        <option value="{$cal.id}">{$cal.title}</option>
    {/foreach}
</select>
<br/>
<input type="button" class="btn btn-primary" onclick="GCal_ChooseCalendar();" value="choose calendar" onclick="" />

<script type="text/javascript">
    function GCal_ChooseCalendar() {
        var params = {
            module: 'SwVtTools',
            action: 'GCalSelection',
            calendarId:jQuery('#syncCalendarId').val()
        };

        AppConnector.request(params).then(function() {
            jQuery('#sync_button').trigger('click');
        });

    }
</script>