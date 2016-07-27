/**
 * Common function of ajax loader 
 * Mostly used in Grid
 * 
 */
function showAjaxLoader(element)
{
    var spinner = '<div id="spinner"></div>';
    $(element).append(spinner);
    $("#spinner").show();
}

function hideAjaxLoader(element)
{
    $("#spinner").hide();
    $(element).remove("#spinner");
}