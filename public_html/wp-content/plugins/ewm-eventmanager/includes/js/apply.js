/* JS for the EWM Event Manager */

// add tooltips
jQuery(document).ready( function() {

/*
  jQuery('.ewm_em_title > span').tTips();
  jQuery('.event_title').tTips();
*/  
  //start
  jQuery("#linkedDates").datepicker({
    minDate: new Date(1991, 1 - 1, 1),
    maxDate: new Date(2020, 12 - 1, 31),
    beforeShow: readLinked,
    onSelect: updateLinked,
    showOn: "both",
    buttonImage: "/wp-content/plugins/ewm-eventmanager/includes/js/ui/css/images/calendar.gif",
    buttonImageOnly: true });
  jQuery("#date-start-mm, #date-start").change(checkLinkedDays);

  // end
  jQuery("#endlinkedDates").datepicker({
    minDate: new Date(1991, 1 - 1, 1),
    maxDate: new Date(2020, 12 - 1, 31),
    beforeShow: endreadLinked,
    onSelect: endupdateLinked,
    showOn: "both",
    buttonImage: "/wp-content/plugins/ewm-eventmanager/includes/js/ui/css/images/calendar.gif",
    buttonImageOnly: true });
  jQuery("#date-end-mm, #date-end").change(endcheckLinkedDays);

})

// Prepare to show a date picker linked to three select controls 
function readLinked() { 
    jQuery("#linkedDates").val(jQuery("#date-start-mm").val() + "/" + 
        jQuery("#date-start-dd").val() + "/" + jQuery("#date-start").val()); 
    return {}; 
} 
 
// Update three select controls to match a date picker selection 
function updateLinked(date) { 
    jQuery("#date-start-mm").val(date.substring(0, 2)); 
    jQuery("#date-start-dd").val(date.substring(3, 5)); 
    jQuery("#date-start").val(date.substring(6, 10)); 
} 
 
// Prevent selection of invalid dates through the select controls 
function checkLinkedDays() { 
    var daysInMonth = 32 - new Date(jQuery("#date-start").val(), 
        jQuery("#date-start-mm").val() - 1, 32).getDate(); 
    jQuery("#date-start-dd option").attr("disabled", ""); 
    jQuery("#date-start-dd option:gt(" + (daysInMonth - 1) +")").attr("disabled", "disabled"); 
    if (jQuery("#date-start-dd").val() > daysInMonth) { 
        jQuery("#date-start-dd").val(daysInMonth); 
    } 
} 






// Prepare to show a date picker linked to three select controls 
function endreadLinked() { 
    jQuery("#endlinkedDates").val(jQuery("#date-end-mm").val() + "/" + 
        jQuery("#date-end-dd").val() + "/" + jQuery("#date-end").val()); 
    return {}; 
} 
 
// Update three select controls to match a date picker selection 
function endupdateLinked(date) { 
    jQuery("#date-end-mm").val(date.substring(0, 2)); 
    jQuery("#date-end-dd").val(date.substring(3, 5)); 
    jQuery("#date-end").val(date.substring(6, 10)); 
} 
 
// Prevent selection of invalid dates through the select controls 
function endcheckLinkedDays() { 
    var daysInMonth = 32 - new Date(jQuery("#date-end").val(), 
        jQuery("#date-end-mm").val() - 1, 32).getDate(); 
    jQuery("#date-end-dd option").attr("disabled", ""); 
    jQuery("#date-end-dd option:gt(" + (daysInMonth - 1) +")").attr("disabled", "disabled"); 
    if (jQuery("#date-end-dd").val() > daysInMonth) { 
        jQuery("#date-end-dd").val(daysInMonth); 
    } 
} 
